<?php

namespace Tests\Feature;

use App\Enums\SiatEnvironment;
use App\Enums\SiatModality;
use App\Models\Company;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCuis;
use App\Models\SinPointOfSale;
use App\Models\User;
use App\Services\Siat\SiatSoapClientFactory;
use App\Services\Siat\SiatWsdlRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiatCuisTest extends TestCase
{
    use RefreshDatabase;

    public function test_cuis_menu_and_history_page_are_available_to_company_users(): void
    {
        $user = $this->companyUser([
            'dashboard.view',
            'siat-cuis.view',
            'siat-cuis.request',
        ]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SIAT')
            ->assertSee('CUIS')
            ->assertSee(route('siat.cuis.index'));

        $this
            ->actingAs($user)
            ->get(route('siat.cuis.index'))
            ->assertOk()
            ->assertSee('CUIS actual')
            ->assertSee('Historico CUIS')
            ->assertSee('Configuracion incompleta');
    }

    public function test_company_user_can_request_cuis_and_store_successful_result(): void
    {
        $user = $this->companyUser([
            'siat-cuis.view',
            'siat-cuis.request',
        ]);
        [$apiToken, $authorization, $pointOfSale] = $this->siatConfiguration($user);

        $this->fakeSoapClient(new class
        {
            public function cuis(array $params): object
            {
                return (object) [
                    'RespuestaCuis' => (object) [
                        'codigo' => 'CUIS-OK-123456',
                        'transaccion' => true,
                    ],
                ];
            }
        });

        $this
            ->actingAs($user)
            ->followingRedirects()
            ->post(route('siat.cuis.request'), [
                'sin_point_of_sale_id' => $pointOfSale->id,
            ])
            ->assertOk()
            ->assertSee('CUIS-OK-123456')
            ->assertSee('Generado');

        $this->assertDatabaseHas('sin_cuis', [
            'company_id' => $user->company_id,
            'sin_api_token_id' => $apiToken->id,
            'sin_authorization_id' => $authorization->id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'tax_id' => '123456789',
            'branch_code' => 7,
            'point_of_sale_code' => 3,
            'transaccion' => true,
            'cuis_code' => 'CUIS-OK-123456',
        ]);
    }

    public function test_cuis_request_uses_codes_wsdl_even_when_token_stores_another_service_url(): void
    {
        $user = $this->companyUser([
            'siat-cuis.view',
            'siat-cuis.request',
        ]);
        [$apiToken, , $pointOfSale] = $this->siatConfiguration($user);
        $apiToken->update(['wsdl_url' => SiatWsdlRegistry::OPERATIONS]);

        $factory = new class extends SiatSoapClientFactory
        {
            public array $wsdlUrls = [];

            public function make(string $wsdlUrl, string $apiToken, int $timeoutSeconds = 5): object
            {
                $this->wsdlUrls[] = $wsdlUrl;

                return new class
                {
                    public function cuis(array $params): object
                    {
                        return (object) [
                            'RespuestaCuis' => (object) [
                                'codigo' => 'CUIS-CODES-WSDL-123',
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
            ->post(route('siat.cuis.request'), [
                'sin_point_of_sale_id' => $pointOfSale->id,
            ])
            ->assertRedirect(route('siat.cuis.index'));

        $this->assertSame([SiatWsdlRegistry::CODES], $factory->wsdlUrls);
        $this->assertDatabaseHas('sin_cuis', [
            'company_id' => $user->company_id,
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'cuis_code' => 'CUIS-CODES-WSDL-123',
        ]);
    }

    public function test_later_false_cuis_response_is_stored_without_replacing_current_cuis(): void
    {
        $user = $this->companyUser([
            'siat-cuis.view',
            'siat-cuis.request',
        ]);
        [, , $pointOfSale] = $this->siatConfiguration($user);
        SinCuis::factory()->create([
            'company_id' => $user->company_id,
            'transaccion' => true,
            'cuis_code' => 'CURRENT-CUIS-999',
            'requested_at' => now()->subHour(),
        ]);

        $this->fakeSoapClient(new class
        {
            public function cuis(array $params): object
            {
                return (object) [
                    'RespuestaCuis' => (object) [
                        'mensajesList' => (object) [
                            'descripcion' => 'CUIS ya fue generado para estos parametros.',
                        ],
                        'transaccion' => false,
                    ],
                ];
            }
        });

        $this
            ->actingAs($user)
            ->followingRedirects()
            ->post(route('siat.cuis.request'), [
                'sin_point_of_sale_id' => $pointOfSale->id,
            ])
            ->assertOk()
            ->assertSee('CURRENT-CUIS-999')
            ->assertSee('CUIS ya fue generado para estos parametros.')
            ->assertSee('Observado');

        $this->assertSame(2, SinCuis::query()->count());
        $this->assertDatabaseHas('sin_cuis', [
            'company_id' => $user->company_id,
            'transaccion' => false,
            'cuis_code' => null,
            'message' => 'CUIS ya fue generado para estos parametros.',
        ]);
        $this->assertSame('CURRENT-CUIS-999', SinCuis::query()->successful()->latest('requested_at')->first()?->cuis_code);
    }

    public function test_cuis_request_requires_token_and_authorization_configuration(): void
    {
        $user = $this->companyUser([
            'siat-cuis.view',
            'siat-cuis.request',
        ]);

        $this
            ->actingAs($user)
            ->from(route('siat.cuis.index'))
            ->post(route('siat.cuis.request'))
            ->assertRedirect(route('siat.cuis.index'))
            ->assertSessionHasErrors(['sin_point_of_sale_id']);
    }

    public function test_viewer_can_open_cuis_page_but_cannot_request_cuis(): void
    {
        $user = $this->companyUser(['siat-cuis.view']);

        $this
            ->actingAs($user)
            ->get(route('siat.cuis.index'))
            ->assertOk()
            ->assertDontSee(route('siat.cuis.request'));

        $this
            ->actingAs($user)
            ->post(route('siat.cuis.request'))
            ->assertForbidden();
    }

    public function test_role_seeder_registers_cuis_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = Role::findByName('admin');
        $manager = Role::findByName('manager');
        $viewer = Role::findByName('viewer');
        $cashier = Role::findByName('cashier');

        $this->assertTrue($admin->hasPermissionTo('siat-cuis.request'));
        $this->assertTrue($manager->hasPermissionTo('siat-cuis.request'));
        $this->assertTrue($viewer->hasPermissionTo('siat-cuis.view'));
        $this->assertFalse($viewer->hasPermissionTo('siat-cuis.request'));
        $this->assertFalse($cashier->hasPermissionTo('siat-cuis.view'));
    }

    private function fakeSoapClient(object $client): void
    {
        $this->instance(SiatSoapClientFactory::class, new class($client) extends SiatSoapClientFactory
        {
            public function __construct(private readonly object $client) {}

            public function make(string $wsdlUrl, string $apiToken, int $timeoutSeconds = 5): object
            {
                return $this->client;
            }
        });
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
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDay()->toDateString(),
        ]);
        $authorization = SinAuthorization::factory()->create([
            'company_id' => $user->company_id,
            'tax_id' => '123456789',
            'system_code' => 'SYSTEM-CODE-123',
            'environment_code' => SiatEnvironment::TestingAndPilot,
            'modality_code' => SiatModality::ComputerizedOnline,
            'branch_code' => 0,
            'point_of_sale_code' => 0,
        ]);

        return [$apiToken, $authorization, $pointOfSale];
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
}
