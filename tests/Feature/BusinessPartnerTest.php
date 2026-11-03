<?php

namespace Tests\Feature;

use App\Livewire\Admin\BusinessPartnerManager;
use App\Models\Branch;
use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\Role;
use App\Models\SecondProcessReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessPartnerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $this->adminRole->id,
        ]);
    }

    public function test_classify_category_logic(): void
    {
        // 1. Deterministic classification via Group Code
        $this->assertEquals(MasterBusinessPartner::CATEGORY_CUSTOMER, MasterBusinessPartner::classifyCategory('100'));
        $this->assertEquals(MasterBusinessPartner::CATEGORY_VENDOR, MasterBusinessPartner::classifyCategory('101'));
        $this->assertEquals(MasterBusinessPartner::CATEGORY_VENDOR, MasterBusinessPartner::classifyCategory('102'));

        // 2. Fallback prefix logic when group code is omitted
        $this->assertEquals(MasterBusinessPartner::CATEGORY_CUSTOMER, MasterBusinessPartner::classifyCategory('', 'D0000001'));
        $this->assertEquals(MasterBusinessPartner::CATEGORY_CUSTOMER, MasterBusinessPartner::classifyCategory('', 'KD000001'));
        $this->assertEquals(MasterBusinessPartner::CATEGORY_VENDOR, MasterBusinessPartner::classifyCategory('', 'VML0000001'));
    }

    public function test_business_partner_manager_page_is_accessible_to_authenticated_users(): void
    {
        $this->get(route('admin.business-partner-manager'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->user)
            ->get(route('admin.business-partner-manager'))
            ->assertOk()
            ->assertSeeLivewire('admin.business-partner-manager');
    }

    public function test_business_partner_upload_and_auto_sync_to_customer_delivery(): void
    {
        $filePath = base_path('LIST BUSINESS PARTNER.xls');
        if (! file_exists($filePath)) {
            $this->markTestSkipped('LIST BUSINESS PARTNER.xls not found in base path.');
        }

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'LIST BUSINESS PARTNER.xls',
            file_get_contents($filePath)
        );

        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->set('file', $uploadedFile)
            ->call('uploadFile')
            ->assertHasNoErrors()
            ->assertSee('Berhasil mengimpor');

        // Check master_business_partners table count (1635 valid records, 2 blank name rows skipped)
        $this->assertEquals(1635, MasterBusinessPartner::count());
        $this->assertEquals(267, MasterBusinessPartner::customers()->count());
        $this->assertEquals(1368, MasterBusinessPartner::vendors()->count());

        // Check Group Code breakdown
        $this->assertEquals(267, MasterBusinessPartner::where('group_code', '100')->count());
        $this->assertEquals(1336, MasterBusinessPartner::where('group_code', '101')->count());
        $this->assertEquals(32, MasterBusinessPartner::where('group_code', '102')->count());

        // Check customer with sales employee (B00000001)
        $daijo = MasterBusinessPartner::where('bp_code', 'B00000001')->first();
        $this->assertNotNull($daijo);
        $this->assertEquals('DAIJO INDUSTRIAL PT.', $daijo->bp_name);
        $this->assertEquals('100', $daijo->group_code);
        $this->assertEquals('CUSTOMER', $daijo->category);
        $this->assertEquals('ANDRIANI  Ext 131 email: andriani@daijo.co.id', $daijo->sales_employee);

        // Check customer with normalized "-No Sales Employee-" to NULL (A0000001)
        $taisei = MasterBusinessPartner::where('bp_code', 'A0000001')->first();
        $this->assertNotNull($taisei);
        $this->assertNull($taisei->sales_employee);

        // Check customer with foreign name alias (D0000002)
        $aski = MasterBusinessPartner::where('bp_code', 'D0000002')->first();
        $this->assertNotNull($aski);
        $this->assertEquals('ASKI', $aski->foreign_name);
        $this->assertEquals('MOULD', $aski->type);

        // Check automatic sync of customer to master_customer_delivery
        $syncedDaijo = MasterCustomerDelivery::where('customer_code', 'B00000001')->first();
        $this->assertNotNull($syncedDaijo);
        $this->assertEquals('DAIJO INDUSTRIAL PT.', $syncedDaijo->customer_name);

        // Vendor should NOT be synced to master_customer_delivery
        $this->assertFalse(MasterCustomerDelivery::where('customer_code', 'VML0000115')->exists());
    }

    public function test_search_customers_in_second_process_report(): void
    {
        $jktBranch = \App\Models\Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta Plant', 'is_active' => true]
        );

        $krwBranch = \App\Models\Branch::firstOrCreate(
            ['code' => 'KRW'],
            ['name' => 'Karawang Plant', 'is_active' => true]
        );

        // Jakarta customer with Automotive industry (SHOULD be returned)
        MasterBusinessPartner::create([
            'bp_code' => 'KD000022',
            'bp_name' => 'ALGERINDO PRIMA NUSANTARA PT.',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'PRODUCT',
            'foreign_name' => 'ALGERINDO',
            'branch_id' => $jktBranch->id,
            'industry' => MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
        ]);

        // Vendor (should NOT be returned)
        MasterBusinessPartner::create([
            'bp_code' => 'VML0000115',
            'bp_name' => 'ASTRA HONDA MOTOR PT.',
            'group_code' => '101',
            'category' => MasterBusinessPartner::CATEGORY_VENDOR,
            'type' => 'MOULD',
            'branch_id' => $jktBranch->id,
        ]);

        // Customer in Karawang branch (should NOT be returned)
        MasterBusinessPartner::create([
            'bp_code' => 'KD-KRW-01',
            'bp_name' => 'ALGERINDO KARAWANG PT.',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'PRODUCT',
            'branch_id' => $krwBranch->id,
            'industry' => MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
        ]);

        // Customer in Moulding industry (should NOT be returned)
        MasterBusinessPartner::create([
            'bp_code' => 'KD-MOLD-01',
            'bp_name' => 'ALGERINDO MOULDING PT.',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'PRODUCT',
            'branch_id' => $jktBranch->id,
            'industry' => MasterBusinessPartner::INDUSTRY_MOULDING,
        ]);

        // 1. Search by customer name or code returns Jakarta non-Moulding customer
        $response = $this->actingAs($this->user)
            ->getJson(route('second-process-reports.search-customers', ['query' => 'ALGERINDO']))
            ->assertOk()
            ->json();

        $this->assertCount(1, $response);
        $this->assertEquals('ALGERINDO PRIMA NUSANTARA PT.', $response[0]['customer_name']);
        $this->assertEquals('KD000022', $response[0]['customer_code']);

        // 2. Vendor should NOT appear in search customers
        $vendorSearch = $this->actingAs($this->user)
            ->getJson(route('second-process-reports.search-customers', ['query' => 'VML0000115']))
            ->assertOk()
            ->json();

        $this->assertCount(0, $vendorSearch);

        // 3. Karawang customer should NOT appear
        $krwSearch = $this->actingAs($this->user)
            ->getJson(route('second-process-reports.search-customers', ['query' => 'KARAWANG']))
            ->assertOk()
            ->json();

        $this->assertCount(0, $krwSearch);

        // 4. Moulding industry customer should NOT appear
        $moldSearch = $this->actingAs($this->user)
            ->getJson(route('second-process-reports.search-customers', ['query' => 'MOULDING']))
            ->assertOk()
            ->json();

        $this->assertCount(0, $moldSearch);
    }

    public function test_second_process_report_normalizes_and_validates_customer_with_business_partner(): void
    {
        MasterBusinessPartner::create([
            'bp_code' => 'KD000022',
            'bp_name' => 'ALGERINDO PRIMA NUSANTARA PT.',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'PRODUCT',
        ]);

        $payload = [
            'date' => '2026-10-08',
            'unit_line' => 'Line 1',
            'shift' => '1',
            'process_prod' => 'Painting',
            'part_number' => 'PART-100',
            'part_name' => 'Test Part',
            'model' => 'Model X',
            'status' => 'draft',
            'customer' => 'KD000022', // Using customer code
        ];

        // Should normalize 'KD000022' -> 'ALGERINDO PRIMA NUSANTARA PT.' and succeed
        $response = $this->actingAs($this->user)
            ->post(route('second-process-reports.store'), $payload);

        $response->assertSessionHasNoErrors();

        $report = SecondProcessReport::latest('id')->first();
        $this->assertNotNull($report);
        $this->assertEquals('ALGERINDO PRIMA NUSANTARA PT.', $report->customer);

        // Submitting with unknown customer should fail validation
        $invalidPayload = array_merge($payload, [
            'customer' => 'UNKNOWN_CUSTOMER_123',
        ]);

        $invalidResponse = $this->actingAs($this->user)
            ->post(route('second-process-reports.store'), $invalidPayload);

        $invalidResponse->assertSessionHasErrors('customer');
    }

    public function test_industry_scopes_and_is_automotive_helper(): void
    {
        $honda = MasterBusinessPartner::create([
            'bp_code' => 'D0000001',
            'bp_name' => 'ASTRA HONDA MOTOR PT.',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
        ]);

        $toshiba = MasterBusinessPartner::create([
            'bp_code' => 'CT000001',
            'bp_name' => 'TOSHIBA',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_ELECTRONICS,
        ]);

        $moulding = MasterBusinessPartner::create([
            'bp_code' => 'M0000001',
            'bp_name' => 'HONDA LOCK INDONESIA PT.',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'MOULD',
            'industry' => MasterBusinessPartner::INDUSTRY_MOULDING,
        ]);

        $general = MasterBusinessPartner::create([
            'bp_code' => 'GEN001',
            'bp_name' => 'GENERAL PACKAGING PT.',
            'category' => MasterBusinessPartner::CATEGORY_VENDOR,
            'industry' => MasterBusinessPartner::INDUSTRY_GENERAL,
        ]);

        $this->assertTrue($honda->is_automotive);
        $this->assertFalse($toshiba->is_automotive);
        $this->assertFalse($moulding->is_automotive);
        $this->assertTrue($moulding->is_moulding);
        $this->assertFalse($general->is_automotive);

        $this->assertEquals(1, MasterBusinessPartner::automotive()->count());
        $this->assertEquals(1, MasterBusinessPartner::electronics()->count());
        $this->assertEquals(1, MasterBusinessPartner::moulding()->count());
        $this->assertEquals(1, MasterBusinessPartner::industry('GENERAL')->count());

        // Test smart detector
        // 1. Moulding rule (type or foreign_name has mould/moulding/mold)
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_MOULDING, MasterBusinessPartner::detectIndustry('ASTRA HONDA MOTOR PT.', '', 'ANDRIANI  Ext 131 email: andriani@daijo.co.id', 'MOULD'));
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_MOULDING, MasterBusinessPartner::detectIndustry('TENMA INDONESIA PT.', 'MOULDING', 'ANDRIANI  Ext 131 email: andriani@daijo.co.id'));
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_MOULDING, MasterBusinessPartner::detectIndustry('SHARP ELECTRONICS INDONESIA PT.', 'SHARP MOLD'));

        // 2. Automotive rule (sales employee Andriani or Anik)
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_AUTOMOTIVE, MasterBusinessPartner::detectIndustry('TOYOTA BOSHOKU INDONESIA PT.', '', 'ANDRIANI  Ext 131 email: andriani@daijo.co.id'));
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_AUTOMOTIVE, MasterBusinessPartner::detectIndustry('YANFENG AUTOMOTIVE INTERIOR SYSTEMS', '', 'ANIK Ext 155 email: anik@daijo.co.id'));

        // 3. Electronics rule (keyword matching)
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_ELECTRONICS, MasterBusinessPartner::detectIndustry('SHARP ELECTRONICS INDONESIA PT.'));
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_ELECTRONICS, MasterBusinessPartner::detectIndustry('HARTONO ISTANA TEKNOLOGI PT.'));

        // 4. General fallback
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_GENERAL, MasterBusinessPartner::detectIndustry('MITRA KARYA PACKAGING CV.'));
        $this->assertEquals(MasterBusinessPartner::INDUSTRY_GENERAL, MasterBusinessPartner::detectIndustry('TOYOTA BOSHOKU INDONESIA PT.', '', 'BUDI Ext 123 email: budi@daijo.co.id'));
    }

    public function test_admin_can_update_industry_inline(): void
    {
        $bp = MasterBusinessPartner::create([
            'bp_code' => 'TEST001',
            'bp_name' => 'SAMPLE CUSTOMER PT.',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_GENERAL,
        ]);

        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->call('updateIndustry', $bp->id, 'MOULDING');

        $this->assertEquals(MasterBusinessPartner::INDUSTRY_MOULDING, $bp->fresh()->industry);
    }

    public function test_admin_can_update_branch_inline(): void
    {
        $branch = \App\Models\Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta Plant', 'is_active' => true]
        );

        $bp = MasterBusinessPartner::create([
            'bp_code' => 'TEST002',
            'bp_name' => 'SAMPLE VENDOR PT.',
            'category' => MasterBusinessPartner::CATEGORY_VENDOR,
            'industry' => MasterBusinessPartner::INDUSTRY_GENERAL,
        ]);

        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->call('updateBranch', $bp->id, $branch->id);

        $this->assertEquals($branch->id, $bp->fresh()->branch_id);

        // Can unassign branch back to null
        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->call('updateBranch', $bp->id, '');

        $this->assertNull($bp->fresh()->branch_id);
    }

    public function test_mes_exclusive_tags_industry_and_branch_persist_on_reupload(): void
    {
        $jktBranch = \App\Models\Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta Plant', 'is_active' => true]
        );

        // Pre-existing BP with manually assigned MES-exclusive tags
        $existingBp = MasterBusinessPartner::create([
            'bp_code' => 'BP-PERSIST-01',
            'bp_name' => 'Old Company Name PT',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'industry' => MasterBusinessPartner::INDUSTRY_AUTOMOTIVE, // Tagged in MES
            'branch_id' => $jktBranch->id, // Tagged in MES
            'type' => 'PRODUCT',
        ]);

        // Generate temporary Excel file with updated name for existing BP and a new BP
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['#', 'CardCode', 'CardName', 'ForeignName', 'Type', 'GroupCode', 'SalesEmployee'],
            ['1', 'BP-PERSIST-01', 'Updated SAP Company Name PT', 'PERSIST ALIAS', 'PRODUCT', '100', 'NO SALES EMPLOYEE'],
            ['2', 'BP-BRAND-NEW', 'Brand New Customer PT', 'NEW ALIAS', 'PRODUCT', '100', 'ANDRIANI Ext 131'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_bp_persist_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        try {
            $uploadedFile = UploadedFile::fake()->createWithContent(
                'test_bp_persist.xlsx',
                file_get_contents($tempPath)
            );

            Livewire::actingAs($this->user)
                ->test(BusinessPartnerManager::class)
                ->set('file', $uploadedFile)
                ->call('uploadFile')
                ->assertHasNoErrors()
                ->assertSee('Tag eksklusif MES (Sektor Industri & Plant) pada data existing tetap dipertahankan');

            $refreshed = $existingBp->fresh();

            // 1. SAP data fields are updated
            $this->assertEquals('Updated SAP Company Name PT', $refreshed->bp_name);
            $this->assertEquals('PERSIST ALIAS', $refreshed->foreign_name);

            // 2. MES-exclusive tags are strictly PERSISTED
            $this->assertEquals(MasterBusinessPartner::INDUSTRY_AUTOMOTIVE, $refreshed->industry);
            $this->assertEquals($jktBranch->id, $refreshed->branch_id);

            // 3. Brand new BP gets detected industry
            $newBp = MasterBusinessPartner::where('bp_code', 'BP-BRAND-NEW')->first();
            $this->assertNotNull($newBp);
            $this->assertEquals(MasterBusinessPartner::INDUSTRY_AUTOMOTIVE, $newBp->industry); // Auto-detected by Andriani
            $this->assertNull($newBp->branch_id);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_branch_filter_in_business_partner_manager(): void
    {
        $jkt = \App\Models\Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta Plant', 'is_active' => true]);
        $krw = \App\Models\Branch::firstOrCreate(['code' => 'KRW'], ['name' => 'Karawang Plant', 'is_active' => true]);

        MasterBusinessPartner::create([
            'bp_code' => 'BP-JKT-01',
            'bp_name' => 'Jakarta Customer PT',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'branch_id' => $jkt->id,
        ]);

        MasterBusinessPartner::create([
            'bp_code' => 'BP-KRW-01',
            'bp_name' => 'Karawang Customer PT',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'branch_id' => $krw->id,
        ]);

        MasterBusinessPartner::create([
            'bp_code' => 'BP-UNTAGGED-01',
            'bp_name' => 'Untagged Customer PT',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'branch_id' => null,
        ]);

        // Filter JKT
        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->set('branchFilter', (string) $jkt->id)
            ->assertSee('BP-JKT-01')
            ->assertDontSee('BP-KRW-01')
            ->assertDontSee('BP-UNTAGGED-01');

        // Filter KRW
        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->set('branchFilter', (string) $krw->id)
            ->assertSee('BP-KRW-01')
            ->assertDontSee('BP-JKT-01')
            ->assertDontSee('BP-UNTAGGED-01');

        // Filter UNASSIGNED
        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->set('branchFilter', 'UNASSIGNED')
            ->assertSee('BP-UNTAGGED-01')
            ->assertDontSee('BP-JKT-01')
            ->assertDontSee('BP-KRW-01');
    }

    public function test_super_admin_can_toggle_auto_branch_karawang_and_k_prefix_gets_branched_to_karawang(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta Plant', 'is_active' => true]);
        $krw = Branch::firstOrCreate(['code' => 'KRW'], ['name' => 'Karawang Plant', 'is_active' => true]);

        // Pre-existing BP starting with K with null branch
        $preExistingK = MasterBusinessPartner::create([
            'bp_code' => 'KD-PREEXIST-01',
            'bp_name' => 'Pre-existing Karawang Customer PT',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'branch_id' => null,
        ]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['#', 'CardCode', 'CardName', 'ForeignName', 'Type', 'GroupCode', 'SalesEmployee'],
            ['1', 'KD-PREEXIST-01', 'Pre-existing Karawang Customer PT', '', 'PRODUCT', '100', ''],
            ['2', 'KM-NEW-01', 'Brand New Karawang Customer PT', '', 'PRODUCT', '100', ''],
            ['3', 'D0-JAKARTA-01', 'Jakarta Customer PT', '', 'PRODUCT', '100', ''],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_autobranch_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        try {
            $uploadedFile = UploadedFile::fake()->createWithContent(
                'test_autobranch.xlsx',
                file_get_contents($tempPath)
            );

            // 1. Verify Super-Admin sees the toggles in the modal
            $component = Livewire::actingAs($superAdmin)
                ->test(BusinessPartnerManager::class)
                ->call('openUploadModal')
                ->assertSee('Auto-Branch Karawang')
                ->assertSee('Default Branch Jakarta')
                ->set('file', $uploadedFile)
                ->set('autoBranchKarawang', true)
                ->set('defaultBranchJakarta', false)
                ->call('uploadFile')
                ->assertHasNoErrors()
                ->assertSee('Auto-branch Karawang aktif');

            // 2. KD-PREEXIST-01 must be auto-branched to Karawang
            $this->assertEquals($krw->id, $preExistingK->fresh()->branch_id);

            // 3. KM-NEW-01 must be auto-branched to Karawang
            $newK = MasterBusinessPartner::where('bp_code', 'KM-NEW-01')->first();
            $this->assertNotNull($newK);
            $this->assertEquals($krw->id, $newK->branch_id);

            // 4. D0-JAKARTA-01 does NOT start with K and default Jakarta was OFF, so branch remains null
            $jakartaBp = MasterBusinessPartner::where('bp_code', 'D0-JAKARTA-01')->first();
            $this->assertNotNull($jakartaBp);
            $this->assertNull($jakartaBp->branch_id);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_super_admin_can_toggle_default_branch_jakarta(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta Plant', 'is_active' => true]);
        $krw = Branch::firstOrCreate(['code' => 'KRW'], ['name' => 'Karawang Plant', 'is_active' => true]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['#', 'CardCode', 'CardName', 'ForeignName', 'Type', 'GroupCode', 'SalesEmployee'],
            ['1', 'KM-KARAWANG-01', 'Karawang Customer PT', '', 'PRODUCT', '100', ''],
            ['2', 'D0-JAKARTA-02', 'Jakarta Customer Two PT', '', 'PRODUCT', '100', ''],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_defjkt_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        try {
            $uploadedFile = UploadedFile::fake()->createWithContent(
                'test_defjkt.xlsx',
                file_get_contents($tempPath)
            );

            // Both toggles ON: K prefix to Karawang, others default to Jakarta
            Livewire::actingAs($superAdmin)
                ->test(BusinessPartnerManager::class)
                ->set('file', $uploadedFile)
                ->set('autoBranchKarawang', true)
                ->set('defaultBranchJakarta', true)
                ->call('uploadFile')
                ->assertHasNoErrors()
                ->assertSee('Auto-branch Karawang aktif')
                ->assertSee('Default Jakarta aktif');

            // KM goes to Karawang
            $km = MasterBusinessPartner::where('bp_code', 'KM-KARAWANG-01')->first();
            $this->assertNotNull($km);
            $this->assertEquals($krw->id, $km->branch_id);

            // D0 defaults to Jakarta
            $d0 = MasterBusinessPartner::where('bp_code', 'D0-JAKARTA-02')->first();
            $this->assertNotNull($d0);
            $this->assertEquals($jkt->id, $d0->branch_id);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_non_super_admin_cannot_activate_auto_branch_or_default_jakarta(): void
    {
        $jkt = Branch::firstOrCreate(['code' => 'JKT'], ['name' => 'Jakarta Plant', 'is_active' => true]);
        $krw = Branch::firstOrCreate(['code' => 'KRW'], ['name' => 'Karawang Plant', 'is_active' => true]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['#', 'CardCode', 'CardName', 'ForeignName', 'Type', 'GroupCode', 'SalesEmployee'],
            ['1', 'KD-ADMIN-UPLOAD', 'Admin Upload Customer PT', '', 'PRODUCT', '100', ''],
            ['2', 'D0-ADMIN-UPLOAD', 'Admin Upload Jakarta PT', '', 'PRODUCT', '100', ''],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_admin_autobranch_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        try {
            $uploadedFile = UploadedFile::fake()->createWithContent(
                'test_admin_autobranch.xlsx',
                file_get_contents($tempPath)
            );

            // Regular ADMIN user does not see toggles and cannot activate them
            Livewire::actingAs($this->user)
                ->test(BusinessPartnerManager::class)
                ->call('openUploadModal')
                ->assertDontSee('Auto-Branch Karawang')
                ->assertDontSee('Default Branch Jakarta')
                ->set('file', $uploadedFile)
                ->set('autoBranchKarawang', true)
                ->set('defaultBranchJakarta', true)
                ->call('uploadFile')
                ->assertHasNoErrors()
                ->assertDontSee('Auto-branch Karawang aktif')
                ->assertDontSee('Default Jakarta aktif');

            $kd = MasterBusinessPartner::where('bp_code', 'KD-ADMIN-UPLOAD')->first();
            $this->assertNotNull($kd);
            $this->assertNull($kd->branch_id);

            $d0 = MasterBusinessPartner::where('bp_code', 'D0-ADMIN-UPLOAD')->first();
            $this->assertNotNull($d0);
            $this->assertNull($d0->branch_id);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
