<?php

namespace Tests\Feature;

use App\Livewire\Admin\BusinessPartnerManager;
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
        MasterBusinessPartner::create([
            'bp_code' => 'KD000022',
            'bp_name' => 'ALGERINDO PRIMA NUSANTARA PT.',
            'group_code' => '100',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
            'type' => 'PRODUCT',
            'foreign_name' => 'ALGERINDO',
        ]);

        MasterBusinessPartner::create([
            'bp_code' => 'VML0000115',
            'bp_name' => 'ASTRA HONDA MOTOR PT.',
            'group_code' => '101',
            'category' => MasterBusinessPartner::CATEGORY_VENDOR,
            'type' => 'MOULD',
        ]);

        // 1. Search by customer name or code
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
}
