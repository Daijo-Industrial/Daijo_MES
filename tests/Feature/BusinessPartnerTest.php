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
        $filePath = base_path('LISTSEMUAVENDORACTIVEDANTYPE.xls');
        if (! file_exists($filePath)) {
            $this->markTestSkipped('LISTSEMUAVENDORACTIVEDANTYPE.xls not found in base path.');
        }

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'LISTSEMUAVENDORACTIVEDANTYPE.xls',
            file_get_contents($filePath)
        );

        Livewire::actingAs($this->user)
            ->test(BusinessPartnerManager::class)
            ->set('file', $uploadedFile)
            ->call('uploadFile')
            ->assertHasNoErrors()
            ->assertSee('Berhasil mengimpor');

        // Check master_business_partners table count (1586 valid records, 1 blank vendor name row skipped)
        $this->assertEquals(1586, MasterBusinessPartner::count());
        $this->assertEquals(257, MasterBusinessPartner::customers()->count());
        $this->assertEquals(1329, MasterBusinessPartner::vendors()->count());

        // Check Group Code breakdown
        $this->assertEquals(257, MasterBusinessPartner::where('group_code', '100')->count());
        $this->assertEquals(1298, MasterBusinessPartner::where('group_code', '101')->count());
        $this->assertEquals(31, MasterBusinessPartner::where('group_code', '102')->count());

        // Check new active customer KD000022 (ALGERINDO PRIMA NUSANTARA PT.)
        $algerindo = MasterBusinessPartner::where('bp_code', 'KD000022')->first();
        $this->assertNotNull($algerindo);
        $this->assertEquals('ALGERINDO PRIMA NUSANTARA PT.', $algerindo->bp_name);
        $this->assertEquals('100', $algerindo->group_code);
        $this->assertEquals('CUSTOMER', $algerindo->category);
        $this->assertEquals('PRODUCT', $algerindo->type);

        // Check automatic sync of customer to master_customer_delivery
        $syncedAlgerindo = MasterCustomerDelivery::where('customer_code', 'KD000022')->first();
        $this->assertNotNull($syncedAlgerindo);
        $this->assertEquals('ALGERINDO PRIMA NUSANTARA PT.', $syncedAlgerindo->customer_name);

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
}
