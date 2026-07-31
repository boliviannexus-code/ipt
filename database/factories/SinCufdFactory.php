<?php

namespace Database\Factories;

use App\Enums\SiatEnvironment;
use App\Enums\SiatModality;
use App\Models\Company;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCufd;
use App\Models\SinCuis;
use App\Models\SinPointOfSale;
use App\Services\Siat\SiatWsdlRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SinCufd>
 */
class SinCufdFactory extends Factory
{
    protected $model = SinCufd::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sin_api_token_id' => SinApiToken::factory(),
            'sin_authorization_id' => SinAuthorization::factory(),
            'sin_branch_id' => SinBranch::factory(),
            'sin_point_of_sale_id' => SinPointOfSale::factory(),
            'sin_cuis_id' => SinCuis::factory(),
            'tax_id' => fake()->numerify('##########'),
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'environment_code' => SiatEnvironment::TestingAndPilot,
            'modality_code' => SiatModality::ComputerizedOnline,
            'branch_code' => 0,
            'point_of_sale_code' => 0,
            'transaccion' => true,
            'cufd_code' => fake()->regexify('[A-Z0-9]{24,48}'),
            'control_code' => fake()->regexify('[A-Z0-9]{6,12}'),
            'address' => fake()->streetAddress(),
            'expires_at' => now()->addDay(),
            'message' => 'CUFD generado correctamente.',
            'response' => [
                'RespuestaCufd' => [
                    'transaccion' => true,
                ],
            ],
            'duration_ms' => 120,
            'requested_at' => now(),
        ];
    }
}
