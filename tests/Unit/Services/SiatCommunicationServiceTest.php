<?php

namespace Tests\Unit\Services;

use App\Models\SinApiToken;
use App\Services\Siat\SiatCommunicationService;
use App\Services\Siat\SiatSoapClientFactory;
use App\Services\Siat\SiatWsdlRegistry;
use Tests\TestCase;

class SiatCommunicationServiceTest extends TestCase
{
    public function test_verify_returns_success_when_siat_reports_transaction_true(): void
    {
        $service = new SiatCommunicationService(new class extends SiatSoapClientFactory
        {
            public function make(string $wsdlUrl, string $apiToken, int $timeoutSeconds = 5): object
            {
                return new class
                {
                    public function verificarComunicacion(): object
                    {
                        return (object) [
                            'RespuestaComunicacion' => (object) [
                                'transaccion' => true,
                            ],
                        ];
                    }
                };
            }
        });

        $result = $service->verify(new SinApiToken([
            'api_token' => 'TOKEN-123',
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDay()->toDateString(),
        ]));

        $this->assertTrue($result->ok);
        $this->assertSame('SIAT respondio correctamente.', $result->message);
        $this->assertSame('verificarComunicacion', $result->operation);
        $this->assertTrue($result->response['RespuestaComunicacion']['transaccion']);
    }

    public function test_verify_does_not_call_siat_when_token_is_not_active(): void
    {
        $service = new SiatCommunicationService(new class extends SiatSoapClientFactory
        {
            public function make(string $wsdlUrl, string $apiToken, int $timeoutSeconds = 5): object
            {
                throw new \RuntimeException('SOAP should not be called.');
            }
        });

        $result = $service->verify(new SinApiToken([
            'api_token' => 'TOKEN-123',
            'wsdl_url' => SiatWsdlRegistry::CODES,
            'starts_at' => now()->subDays(5)->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
        ]));

        $this->assertFalse($result->ok);
        $this->assertStringContainsString('Vencido', $result->message);
    }
}
