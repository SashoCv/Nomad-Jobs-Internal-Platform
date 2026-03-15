<?php

namespace Tests\Unit;

use App\Models\Candidate;
use App\Models\CompanyServiceContract;
use App\Models\ContractPricing;
use App\Models\ContractType;
use App\Models\Invoice;
use App\Models\Status;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $companyId;
    private int $contractId;
    private int $statusId;
    private int $erpr1TypeId;
    private int $ninetyDaysTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base data
        $this->companyId = \DB::table('companies')->insertGetId([
            'nameOfCompany' => 'Test Company',
            'address' => 'Test Address',
            'phoneNumber' => '123456',
            'EIK' => 'TEST123',
            'contactPerson' => 'Test',
            'companyCity' => 'Sofia',
            'logoPath' => '',
            'logoName' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contractId = \DB::table('company_service_contracts')->insertGetId([
            'company_id' => $this->companyId,
            'contractNumber' => 'TEST-001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ensure statuses exist
        $this->statusId = \DB::table('statuses')->insertGetId([
            'nameOfStatus' => 'Test Status',
            'order' => 99,
            'showOnHomePage' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ensure contract types exist
        $this->erpr1TypeId = \DB::table('contract_types')->where('slug', 'erpr1')->value('id')
            ?? \DB::table('contract_types')->insertGetId([
                'name' => 'ERPR 1', 'slug' => 'erpr1', 'created_at' => now(), 'updated_at' => now(),
            ]);

        $this->ninetyDaysTypeId = \DB::table('contract_types')->where('slug', '90days')->value('id')
            ?? \DB::table('contract_types')->insertGetId([
                'name' => '90 дни', 'slug' => '90days', 'created_at' => now(), 'updated_at' => now(),
            ]);

        // Create a contract service type
        if (!\DB::table('contract_service_types')->where('id', 1)->exists()) {
            \DB::table('contract_service_types')->insert([
                'id' => 1, 'name' => 'Test Service', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function createCandidate(array $overrides = []): int
    {
        return \DB::table('candidates')->insertGetId(array_merge([
            'fullName' => 'Test Candidate',
            'company_id' => $this->companyId,
            'contract_type_id' => $this->erpr1TypeId,
            'country_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createPricing(array $contractTypeIds = [], array $overrides = []): int
    {
        $pricingId = \DB::table('contract_pricings')->insertGetId(array_merge([
            'company_service_contract_id' => $this->contractId,
            'contract_service_type_id' => 1,
            'price' => 100.00,
            'status_id' => $this->statusId,
            'country_scope_type' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        foreach ($contractTypeIds as $typeId) {
            \DB::table('company_pricing_contract_types')->insert([
                'pricing_id' => $pricingId,
                'contract_type_id' => $typeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $pricingId;
    }

    /** @test */
    public function it_creates_invoice_for_matching_contract_type()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => $this->erpr1TypeId]);
        $this->createPricing([$this->erpr1TypeId]); // Pricing for ERPR1 only

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('invoices', [
            'candidate_id' => $candidateId,
            'company_id' => $this->companyId,
            'price' => 100.00,
        ]);
    }

    /** @test */
    public function it_does_not_create_invoice_for_non_matching_contract_type()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => $this->ninetyDaysTypeId]); // 90days candidate
        $this->createPricing([$this->erpr1TypeId]); // Pricing for ERPR1 only

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 0);
    }

    /** @test */
    public function it_creates_invoice_when_pricing_has_no_contract_types_all()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => $this->erpr1TypeId]);
        $this->createPricing([]); // No contract types = applies to ALL

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 1);
    }

    /** @test */
    public function it_does_not_create_invoice_when_candidate_has_no_contract_type_and_pricing_is_restricted()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => null]); // No contract type
        $this->createPricing([$this->erpr1TypeId]); // Restricted to ERPR1

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 0);
    }

    /** @test */
    public function it_creates_invoice_when_candidate_has_no_contract_type_and_pricing_is_unrestricted()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => null]);
        $this->createPricing([]); // No restriction

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 1);
    }

    /** @test */
    public function it_creates_multiple_invoices_for_multiple_matching_pricings()
    {
        $candidateId = $this->createCandidate(['contract_type_id' => $this->erpr1TypeId]);
        $this->createPricing([$this->erpr1TypeId], ['price' => 100.00]);
        $this->createPricing([], ['price' => 50.00]); // All types

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 2);
    }

    /** @test */
    public function it_respects_country_scope_include()
    {
        // Ensure country exists
        if (!\DB::table('countries')->where('id', 1)->exists()) {
            \DB::table('countries')->insert(['id' => 1, 'name' => 'Nepal', 'created_at' => now(), 'updated_at' => now()]);
        }
        if (!\DB::table('countries')->where('id', 2)->exists()) {
            \DB::table('countries')->insert(['id' => 2, 'name' => 'India', 'created_at' => now(), 'updated_at' => now()]);
        }

        $candidateId = $this->createCandidate(['country_id' => 1]); // Nepal
        $this->createPricing([], [
            'country_scope_type' => 'include',
            'country_scope_ids' => json_encode([2]), // Only India
        ]);

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 0); // Nepal not in include list
    }

    /** @test */
    public function it_does_not_create_invoice_without_active_contract()
    {
        // Deactivate the contract
        \DB::table('company_service_contracts')
            ->where('id', $this->contractId)
            ->update(['status' => 'expired']);

        $candidateId = $this->createCandidate();
        $this->createPricing([]);

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 0);
    }

    /** @test */
    public function it_does_not_create_invoice_for_candidate_without_company()
    {
        $candidateId = $this->createCandidate(['company_id' => null]);

        InvoiceService::saveInvoiceOnStatusChange($candidateId, $this->statusId, '2026-03-15');

        $this->assertDatabaseCount('invoices', 0);
    }
}
