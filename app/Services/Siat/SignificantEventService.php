<?php

namespace App\Services\Siat;

use App\Models\SinApiToken;
use App\Models\SinInvoiceIssue;
use App\Models\SinPointOfSale;
use App\Models\SinSignificantEvent;
use App\Models\User;
use App\Services\Parameters\SinAuthorizationService;
use App\Services\SinApiTokenService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SignificantEventService
{
    public function __construct(
        private readonly SiatSoapClientFactory $clients,
        private readonly SinApiTokenService $apiTokens,
        private readonly SinAuthorizationService $authorizations,
        private readonly SiatCuisService $cuisService,
        private readonly SiatCufdService $cufdService,
    ) {}

    /** @param array<string, mixed> $data */
    public function registerForPointOfSale(User $user, SinPointOfSale $pointOfSale, array $data): SinSignificantEvent
    {
        $pointOfSale->loadMissing('branch');
        $apiToken = $this->apiTokens->current();
        $authorization = $this->authorizations->current();
        $cuis = $this->cuisService->currentForPointOfSale($pointOfSale);
        $cufd = $this->cufdService->currentForPointOfSale($pointOfSale);

        if (! $apiToken || ! $authorization || ! $cuis?->cuis_code || ! $cufd?->cufd_code || ! $pointOfSale->branch) {
            throw ValidationException::withMessages([
                'configuration' => 'El punto de venta necesita token, autorizacion, CUIS y CUFD vigentes para registrar la contingencia.',
            ]);
        }

        return $this->submit(
            $user,
            null,
            $apiToken,
            $authorization,
            $pointOfSale,
            $cuis,
            $cufd,
            $data,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(User $user, SinInvoiceIssue $invoice, array $data): SinSignificantEvent
    {
        $invoice->loadMissing(['authorization', 'pointOfSale.branch', 'cuis', 'cufd']);

        if (! $invoice->allowsSignificantEvent()) {
            throw ValidationException::withMessages([
                'invoice' => 'El evento de contingencia solo puede registrarse para una factura que no pudo conectarse con el SIN.',
            ]);
        }

        if ($invoice->significantEvents()->where('transaccion', true)->exists()) {
            throw ValidationException::withMessages([
                'invoice' => 'Esta contingencia ya tiene un evento significativo registrado en el SIN.',
            ]);
        }

        $authorization = $invoice->authorization;
        $pointOfSale = $invoice->pointOfSale;
        $cuis = $invoice->cuis;
        $cufd = $invoice->cufd;
        $apiToken = SinApiToken::query()->find($invoice->sin_api_token_id);

        if (! $authorization || ! $pointOfSale?->branch || ! $cuis?->cuis_code || ! $cufd?->cufd_code || ! $apiToken) {
            throw ValidationException::withMessages([
                'configuration' => 'No se cuenta con toda la configuracion fiscal usada al emitir la factura.',
            ]);
        }

        return $this->submit($user, $invoice, $apiToken, $authorization, $pointOfSale, $cuis, $cufd, $data);
    }

    /** @param array<string, mixed> $data */
    private function submit(
        User $user,
        ?SinInvoiceIssue $invoice,
        SinApiToken $apiToken,
        object $authorization,
        SinPointOfSale $pointOfSale,
        object $cuis,
        object $cufd,
        array $data,
    ): SinSignificantEvent {
        $payload = [
            'SolicitudEventoSignificativo' => [
                'codigoAmbiente' => $authorization->environment_code->value,
                'codigoMotivoEvento' => (int) $data['event_code'],
                'codigoPuntoVenta' => $pointOfSale->point_of_sale_code,
                'codigoSistema' => (string) $authorization->system_code,
                'codigoSucursal' => $pointOfSale->branch->branch_code,
                'cufd' => (string) $cufd->cufd_code,
                'cufdEvento' => (string) $cufd->cufd_code,
                'cuis' => (string) $cuis->cuis_code,
                'descripcion' => trim((string) $data['description']),
                'fechaHoraFinEvento' => Carbon::parse((string) $data['ended_at'])->format('Y-m-d\TH:i:s.v'),
                'fechaHoraInicioEvento' => Carbon::parse((string) $data['started_at'])->format('Y-m-d\TH:i:s.v'),
                'nit' => $authorization->tax_id,
            ],
        ];

        $event = SinSignificantEvent::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'sin_invoice_issue_id' => $invoice?->id,
            'sin_api_token_id' => $apiToken->id,
            'sin_authorization_id' => $authorization->id,
            'sin_cuis_id' => $cuis->id,
            'sin_cufd_id' => $cufd->id,
            'event_code' => (int) $data['event_code'],
            'event_description' => trim((string) $data['description']),
            'started_at' => Carbon::parse((string) $data['started_at']),
            'ended_at' => Carbon::parse((string) $data['ended_at']),
            'request_payload' => $payload,
        ]);

        $startedAt = microtime(true);

        try {
            $client = $this->clients->make(SiatWsdlRegistry::OPERATIONS, (string) $apiToken->api_token, 30);
            $response = $this->normalize($client->registroEventoSignificativo($payload));
            $transaccion = $this->findBoolean($response, 'transaccion') ?? false;
            $receptionCode = $this->findValue($response, ['codigoRecepcionEventoSignificativo', 'codigoRecepcionEvento', 'codigoRecepcion']);
            $message = $this->findValue($response, ['descripcion', 'mensaje', 'descripcionMensaje'])
                ?: ($transaccion ? 'Evento significativo registrado en el SIN.' : 'El SIN rechazo el registro del evento significativo.');

            $event->update([
                'reception_code' => $receptionCode,
                'transaccion' => $transaccion,
                'status_label' => $transaccion ? 'Registrado' : 'Rechazado',
                'response' => $response,
                'message' => $message,
                'duration_ms' => $this->durationMs($startedAt),
                'registered_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'status_label' => 'Error',
                'message' => 'No se pudo registrar el evento en el SIN: '.Str::limit($exception->getMessage(), 280),
                'duration_ms' => $this->durationMs($startedAt),
                'registered_at' => now(),
            ]);
        }

        return $event->refresh();
    }

    /** @return array<string, mixed> */
    private function normalize(mixed $response): array
    {
        $data = json_decode((string) json_encode($response), true);

        return is_array($data) ? $data : ['value' => $response];
    }

    /** @param array<int, string> $keys */
    private function findValue(array $data, array $keys): ?string
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $keys, true) && is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }

            if (is_array($value) && ($found = $this->findValue($value, $keys)) !== null) {
                return $found;
            }
        }

        return null;
    }

    private function findBoolean(array $data, string $key): ?bool
    {
        foreach ($data as $currentKey => $value) {
            if ($currentKey === $key) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            if (is_array($value) && ($found = $this->findBoolean($value, $key)) !== null) {
                return $found;
            }
        }

        return null;
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
