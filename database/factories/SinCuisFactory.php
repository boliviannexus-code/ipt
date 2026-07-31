<?php

namespace Database\Factories;

use App\Enums\SiatEnvironment;
use App\Enums\SiatModality;
use App\Models\Company;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCuis;
use App\Models\SinPointOfSale;
use App\Services\Siat\SiatWsdlRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SinCuis>
 */
class SinCuisFactory extends Factory
{
    protected $model = SinCuis::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sin_api_token_id' => SinApiToken::factory(),
            'sin_authorization_id' => SinAuthorization::factory(),
            'sin_branch_id' => SinBranch::factory(),
            'sin_point_of_sale_id' => SinPointOfSale::factory(),
            'tax_id' => fake()->numerify('##########'),
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'environment_code' => SiatEnvironment::TestingAndPilot,
            'modality_code' => SiatModality::ComputerizedOnline,
            'branch_code' => 0,
            'point_of_sale_code' => 0,
            'transaccion' => true,
            'cuis_code' => fake()->regexify('[A-Z0-9]{10,20}'),
            'message' => 'CUIS generado correctamente.',
            'response' => [
                'RespuestaCuis' => [
                    'transaccion' => true,
                ],
            ],
            'duration_ms' => 120,
            'requested_at' => now(),
        ];
    }
}
