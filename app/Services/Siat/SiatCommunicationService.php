<?php

namespace App\Services\Siat;

use App\Models\SinApiToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class SiatCommunicationService
{
    public function __construct(
        private readonly SiatSoapClientFactory $clients,
    ) {}

    public function verify(SinApiToken $configuration): SiatCommunicationResult
    {
        if (! extension_loaded('soap')) {
            return SiatCommunicationResult::failed(
                'La extension SOAP de PHP no esta disponible en el servidor.',
                $configuration->wsdl_url,
            );
        }

        if ($configuration->status_label !== 'Vigente') {
            return SiatCommunicationResult::failed(
                "El token API esta {$configuration->status_label}. Actualiza su vigencia antes de verificar la comunicacion.",
                $configuration->wsdl_url,
            );
        }

        $startedAt = microtime(true);

        try {
            $client = $this->clients->make($configuration->wsdl_url, (string) $configuration->api_token);
            $response = $client->verificarComunicacion();
            $responseData = $this->normalizeResponse($response);
            $durationMs = $this->durationMs($startedAt);
            $transaction = $this->findTransaction($responseData);

            return new SiatCommunicationResult(
                ok: $transaction ?? true,
                message: $this->messageFor($transaction),
                operation: 'verificarComunicacion',
                wsdlUrl: $configuration->wsdl_url,
                durationMs: $durationMs,
                checkedAt: Carbon::now()->format('d/m/Y H:i:s'),
                response: $responseData,
            );
        } catch (Throwable $exception) {
            return SiatCommunicationResult::failed(
                'No se pudo comunicar con SIAT: '.Str::limit($exception->getMessage(), 280),
                $configuration->wsdl_url,
                $this->durationMs($startedAt),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResponse(mixed $response): array
    {
        if (is_array($response)) {
            return $response;
        }

        $json = json_encode($response);

        if (! is_string($json)) {
            return ['value' => $response];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : ['value' => $response];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findTransaction(array $data): ?bool
    {
        foreach ($data as $key => $value) {
            if ($key === 'transaccion') {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            if (is_array($value)) {
                $transaction = $this->findTransaction($value);

                if ($transaction !== null) {
                    return $transaction;
                }
            }
        }

        return null;
    }

    private function messageFor(?bool $transaction): string
    {
        return match ($transaction) {
            true => 'SIAT respondio correctamente.',
            false => 'SIAT respondio, pero reporto una comunicacion no exitosa.',
            null => 'SIAT respondio. Revisa el detalle devuelto por el servicio.',
        };
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
