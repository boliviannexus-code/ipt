<?php

namespace Tests\Feature\Billing;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCatalogItem;
use App\Models\SinCufd;
use App\Models\SinCuis;
use App\Models\SinPointOfSale;
use App\Models\User;
use App\Services\Siat\SiatCommunicationResult;
use App\Services\Siat\SiatCommunicationService;
use App\Services\Siat\SiatSoapClientFactory;
use App\Services\Siat\SiatWsdlRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_menu_is_rendered_for_users_that_can_issue_invoices(): void
    {
        $user = $this->companyUser(['dashboard.view', 'invoices.issue']);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturacion')
            ->assertSee('Emitir factura')
            ->assertSee(route('billing.invoices.issue.index'));
    }

    public function test_issue_index_lists_only_active_document_sector_types(): void
    {
        $user = $this->companyUser(['invoices.issue', 'customers.create']);

        $this->seedDocumentSector($user->company_id, '1', 'FACTURA COMPRA-VENTA');
        $this->seedDocumentSector($user->company_id, '2', 'FACTURA ALQUILER', isActive: false);

        $this
            ->actingAs($user)
            ->get(route('billing.invoices.issue.index'))
            ->assertOk()
            ->assertSee('FACTURA COMPRA-VENTA')
            ->assertSee(route('billing.invoices.issue.show', 1))
            ->assertDontSee('FACTURA ALQUILER');
    }

    public function test_purchase_sale_sector_renders_its_invoice_form(): void
    {
        $user = $this->companyUser(['invoices.issue']);
        $customer = Customer::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Cliente de prueba',
            'document_number' => '123456',
        ]);
        $product = Product::factory()->create([
            'company_id' => $user->company_id,
            'internal_code' => 'PRD-001',
            'description' => 'Producto homologado',
            'siat_product_code' => 99123,
        ]);

        $this->seedDocumentSector($user->company_id, '1', 'FACTURA COMPRA-VENTA');
        $this->seedCatalogItem($user->company_id, 'tipos_metodo_pago', '1', 'EFECTIVO');
        $this->seedCatalogItem($user->company_id, 'tipos_moneda', '1', 'BOLIVIANO');

        $this
            ->actingAs($user)
            ->get(route('billing.invoices.issue.show', 1))
            ->assertOk()
            ->assertSee('Factura compra-venta')
            ->assertDontSee('Datos basicos del contribuyente')
            ->assertDontSee('Casos especiales')
            ->assertSee('Datos de la transaccion comercial')
            ->assertSee('Cliente registrado')
            ->assertSee('Cliente nuevo')
            ->assertSee('Datos basicos del cliente')
            ->assertSee('Detalle de la transaccion comercial')
            ->assertSee($customer->name)
            ->assertSee('EFECTIVO')
            ->assertSee('BOLIVIANO')
            ->assertSee($product->internal_code)
            ->assertSee('Resumen')
            ->assertSee('Emitir factura');
    }

    public function test_purchase_sale_form_shows_green_fiscal_statuses_when_nit_cuis_and_cufd_are_ready(): void
    {
        $user = $this->companyUser(['invoices.issue']);
        [$apiToken, , $pointOfSale] = $this->siatConfiguration($user);

        SinCuis::factory()->create([
            'company_id' => $user->company_id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'branch_code' => $pointOfSale->branch->branch_code,
            'point_of_sale_code' => $pointOfSale->point_of_sale_code,
            'transaccion' => true,
            'cuis_code' => 'CUIS-CURRENT-123',
        ]);
        SinCufd::factory()->create([
            'company_id' => $user->company_id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'branch_code' => $pointOfSale->branch->branch_code,
            'point_of_sale_code' => $pointOfSale->point_of_sale_code,
            'transaccion' => true,
            'cufd_code' => 'CUFD-CURRENT-123',
            'expires_at' => now()->addDay(),
        ]);

        $this->mock(SiatCommunicationService::class, function (MockInterface $mock) use ($apiToken): void {
            $mock
                ->shouldReceive('verify')
                ->once()
                ->withArgs(fn (SinApiToken $token): bool => $token->is($apiToken))
                ->andReturn(new SiatCommunicationResult(
                    ok: true,
                    message: 'SIAT respondio correctamente.',
                    operation: 'verificarComunicacion',
                    wsdlUrl: $apiToken->wsdl_url,
                    durationMs: 90,
                    checkedAt: '30/07/2026 14:00:00',
                ));
        });

        $this->seedDocumentSector($user->company_id, '1', 'FACTURA COMPRA-VENTA');

        $this
            ->actingAs($user)
            ->get(route('billing.invoices.issue.show', 1))
            ->assertOk()
            ->assertSee('123456789')
            ->assertSee('CUIS')
            ->assertSee('CUFD')
            ->assertDontSee('CUIS vigente')
            ->assertDontSee('CUFD vigente')
            ->assertSee('data-communication-ok="1"', false)
            ->assertSee('data-cuis-valid="1"', false)
            ->assertSee('data-cufd-valid="1"', false)
            ->assertSee('data-cufd-request-url=', false)
            ->assertDontSee('Solicitar CUFD');
    }

    public function test_invoice_cufd_request_uses_codes_wsdl_and_stores_successful_result(): void
    {
        $user = $this->companyUser(['invoices.issue']);
        [, , $pointOfSale] = $this->siatConfiguration($user);
        SinCuis::factory()->create([
            'company_id' => $user->company_id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'branch_code' => $pointOfSale->branch->branch_code,
            'point_of_sale_code' => $pointOfSale->point_of_sale_code,
            'transaccion' => true,
            'cuis_code' => 'CUIS-CURRENT-123',
        ]);

        $factory = new class extends SiatSoapClientFactory
        {
            public array $wsdlUrls = [];

            public function make(string $wsdlUrl, string $apiToken, int $timeoutSeconds = 5): object
            {
                $this->wsdlUrls[] = $wsdlUrl;

                return new class
                {
                    public function cufd(array $params): object
                    {
                        return (object) [
                            'RespuestaCufd' => (object) [
                                'codigo' => 'CUFD-OK-123456',
                                'codigoControl' => 'CTRL-123',
                                'direccion' => 'Av. Impuestos 123',
                                'fechaVigencia' => now()->addDay()->toIso8601String(),
                                'transaccion' => true,
                            ],
                        ];
                    }
                };
            }
        };

        $this->instance(SiatSoapClientFactory::class, $factory);

        $this
            ->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('billing.invoices.issue.cufd.request'), [
                'sin_point_of_sale_id' => $pointOfSale->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cufd.status', 'Vigente')
            ->assertJsonPath('data.cufd.control_code', 'CTRL-123');

        $this->assertSame([SiatWsdlRegistry::CODES], $factory->wsdlUrls);
        $this->assertDatabaseHas('sin_cufds', [
            'company_id' => $user->company_id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'cufd_code' => 'CUFD-OK-123456',
            'control_code' => 'CTRL-123',
            'transaccion' => true,
            'wsdl_url' => SiatWsdlRegistry::CODES,
        ]);
    }

    public function test_other_active_document_sector_types_show_development_message(): void
    {
        $user = $this->companyUser(['invoices.issue']);

        $this->seedDocumentSector($user->company_id, '1', 'FACTURA COMPRA-VENTA');
        $this->seedDocumentSector($user->company_id, '43', 'FACTURA COMERCIAL DE EXPORTACION HIDROCARBUROS');

        $this
            ->actingAs($user)
            ->get(route('billing.invoices.issue.show', 43))
            ->assertOk()
            ->assertSee('Formulario en desarrollo')
            ->assertSee('FACTURA COMERCIAL DE EXPORTACION HIDROCARBUROS')
            ->assertSee('FACTURA COMPRA-VENTA');
    }

    public function test_inactive_document_sector_type_cannot_be_opened(): void
    {
        $user = $this->companyUser(['invoices.issue']);

        $this->seedDocumentSector($user->company_id, '1', 'FACTURA COMPRA-VENTA', isActive: false);

        $this
            ->actingAs($user)
            ->get(route('billing.invoices.issue.show', 1))
            ->assertNotFound();
    }

    public function test_role_permission_seeder_assigns_invoice_issue_permission_to_operational_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Permission::query()->where('name', 'invoices.issue')->exists());
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('invoices.issue'));
        $this->assertTrue(Role::findByName('manager')->hasPermissionTo('invoices.issue'));
        $this->assertTrue(Role::findByName('cashier')->hasPermissionTo('invoices.issue'));
        $this->assertTrue(Role::findByName('cashier')->hasPermissionTo('customers.create'));
        $this->assertFalse(Role::findByName('viewer')->hasPermissionTo('invoices.issue'));
    }

    private function companyUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function seedDocumentSector(
        int $companyId,
        string $code,
        string $description,
        bool $isActive = true
    ): SinCatalogItem {
        return $this->seedCatalogItem($companyId, 'tipos_documento_sector', $code, $description, $isActive);
    }

    private function seedCatalogItem(
        int $companyId,
        string $catalogKey,
        string $code,
        string $description,
        bool $isActive = true
    ): SinCatalogItem {
        return SinCatalogItem::factory()->create([
            'company_id' => $companyId,
            'catalog_key' => $catalogKey,
            'item_key' => 'codigoClasificador:'.$code,
            'classifier_code' => $code,
            'description' => $description,
            'is_active' => $isActive,
            'raw_data' => [
                'codigoClasificador' => $code,
                'descripcion' => $description,
            ],
        ]);
    }

    /**
     * @return array{SinApiToken, SinAuthorization, SinPointOfSale}
     */
    private function siatConfiguration(User $user): array
    {
        $branch = SinBranch::factory()->create([
            'company_id' => $user->company_id,
            'branch_code' => 7,
            'name' => 'Sucursal Centro',
            'is_active' => true,
        ]);
        $pointOfSale = SinPointOfSale::factory()->create([
            'company_id' => $user->company_id,
            'sin_branch_id' => $branch->id,
            'point_of_sale_code' => 3,
            'name' => 'Caja 3',
            'is_active' => true,
        ]);
        $apiToken = SinApiToken::factory()->create([
            'company_id' => $user->company_id,
            'api_token' => 'TOKEN-API-123456',
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDay()->toDateString(),
        ]);
        $authorization = SinAuthorization::factory()->create([
            'company_id' => $user->company_id,
            'tax_id' => '123456789',
            'system_code' => 'SYSTEM-CODE-123',
        ]);

        return [$apiToken, $authorization, $pointOfSale];
    }
}
