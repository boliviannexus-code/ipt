<?php

namespace App\Services\Siat;

use App\Enums\SiatModality;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinCatalogItem;
use App\Models\SinCufd;
use App\Models\SinCuis;
use App\Models\SinInvoiceIssue;
use App\Models\SinPointOfSale;
use App\Models\User;
use App\Services\Parameters\SinAuthorizationService;
use App\Services\SinApiTokenService;
use App\Support\CompanyContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SoapVar;
use Throwable;

class PurchaseSaleInvoiceIssueService
{
    private const EMISSION_TYPE_ONLINE = 1;

    private const DOCUMENT_SECTOR_PURCHASE_SALE = 1;

    private const INVOICE_DOCUMENT_WITH_TAX_CREDIT = 1;

    public function __construct(
        private readonly SiatSoapClientFactory $clients,
        private readonly SinApiTokenService $apiTokens,
        private readonly SinAuthorizationService $authorizations,
        private readonly SiatCuisService $cuis,
        private readonly SiatCufdService $cufds,
        private readonly SiatCufGenerator $cufGenerator,
        private readonly PurchaseSaleInvoiceXmlBuilder $xmlBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function issue(User $user, array $data): SinInvoiceIssue
    {
        $company = CompanyContext::activeCompany($user);
        $companyId = CompanyContext::id($user);

        if (! $company || $companyId === null || $companyId <= 0) {
            throw ValidationException::withMessages([
                'company' => 'Selecciona una empresa antes de emitir la factura.',
            ]);
        }

        $apiToken = $this->apiTokens->current();
        $authorization = $this->authorizations->current();
        $pointOfSale = SinPointOfSale::query()->with('branch')->findOrFail((int) $data['sin_point_of_sale_id']);
        $customer = Customer::query()->findOrFail((int) $data['customer_id']);

        $this->ensureReady($apiToken, $authorization, $pointOfSale);

        $currentCuis = $this->cuis->currentForPointOfSale($pointOfSale);
        $currentCufd = $this->cufds->currentForPointOfSale($pointOfSale);

        if (! $currentCuis) {
            throw ValidationException::withMessages([
                'cuis' => 'Genera primero el CUIS vigente para esta sucursal y punto de venta.',
            ]);
        }

        if (! $currentCufd) {
            $requestedCufd = $this->cufds->request($user, $pointOfSale);
            $currentCufd = $requestedCufd->transaccion ? $requestedCufd : null;
        }

        if (! $currentCufd || ! $currentCufd->expires_at?->isFuture()) {
            throw ValidationException::withMessages([
                'cufd' => 'No se pudo obtener un CUFD vigente para emitir la factura.',
            ]);
        }

        $issuedAt = Carbon::parse((string) $data['issued_at']);
        $lines = $this->lines($data);
        $totals = $this->totals($lines, (float) ($data['total_discount'] ?? 0));
        $attemptedInvoiceNumber = $this->nextInvoiceNumber($companyId);
        $cuf = $this->cufGenerator->generate(
            (string) $authorization->tax_id,
            $issuedAt,
            (int) $pointOfSale->branch->branch_code,
            $authorization->modality_code->value,
            self::EMISSION_TYPE_ONLINE,
            self::INVOICE_DOCUMENT_WITH_TAX_CREDIT,
            self::DOCUMENT_SECTOR_PURCHASE_SALE,
            $attemptedInvoiceNumber,
            (int) $pointOfSale->point_of_sale_code,
            (string) $currentCufd->control_code,
        );

        $invoiceData = $this->invoiceData(
            $user,
            $company,
            $authorization,
            $pointOfSale,
            $customer,
            $currentCuis,
            $currentCufd,
            $issuedAt,
            $attemptedInvoiceNumber,
            $cuf,
            $lines,
            $totals,
            $data,
        );

        $xml = $this->xmlBuilder->build($invoiceData);
        $gzip = gzencode($xml, 9);

        if ($gzip === false) {
            throw ValidationException::withMessages([
                'xml' => 'No se pudo comprimir el XML de la factura.',
            ]);
        }

        $hash = hash('sha256', $gzip);
        $paths = $this->storeFiles($companyId, $attemptedInvoiceNumber, $xml, $gzip);
        $issue = $this->storePendingIssue(
            $user,
            $authorization,
            $pointOfSale,
            $customer,
            $currentCuis,
            $currentCufd,
            $issuedAt,
            $attemptedInvoiceNumber,
            $cuf,
            $hash,
            $paths,
            $invoiceData,
            $totals,
        );

        return $this->send($issue, $apiToken, $authorization, $pointOfSale, $currentCuis, $currentCufd, $gzip, $hash);
    }

    private function ensureReady(?SinApiToken $apiToken, ?SinAuthorization $authorization, SinPointOfSale $pointOfSale): void
    {
        $messages = [];

        if (! extension_loaded('soap')) {
            $messages['soap'] = 'La extension SOAP de PHP no esta disponible en el servidor.';
        }

        if (! $apiToken) {
            $messages['api_token'] = 'Registra primero el token API.';
        } elseif ($apiToken->status_label !== 'Vigente') {
            $messages['api_token'] = "El token API esta {$apiToken->status_label}. Actualiza su vigencia antes de emitir.";
        }

        if (! $authorization) {
            $messages['authorization'] = 'Registra primero la autorizacion SIN.';
        } elseif ($authorization->modality_code !== SiatModality::ComputerizedOnline) {
            $messages['authorization'] = 'La emision implementada corresponde a modalidad Computarizada en Linea.';
        }

        if (! $pointOfSale->is_active || ! $pointOfSale->branch?->is_active) {
            $messages['sin_point_of_sale_id'] = 'Selecciona una sucursal y punto de venta activos.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function lines(array $data): array
    {
        $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($data['items'])
            ->values()
            ->map(function (array $item) use ($products, $data): array {
                $product = $products->get((int) $item['product_id']);
                $quantity = round((float) $item['quantity'], 5);
                $unitPrice = round((float) $item['unit_price'], 5);
                $discount = round((float) ($item['discount'] ?? 0), 5);
                $subtotal = round(max(0, ($quantity * $unitPrice) - $discount), 5);
                $additionalDescription = trim((string) ($item['additional_description'] ?? ''));
                $description = trim((string) ($item['description'] ?? $product?->description));

                if ($additionalDescription !== '') {
                    $description .= ' - '.$additionalDescription;
                }

                return [
                    'product_id' => $product?->id,
                    'actividadEconomica' => (string) ($product?->economic_activity_code ?: $data['economic_activity_code']),
                    'codigoProductoSin' => (int) $product?->siat_product_code,
                    'codigoProducto' => (string) $product?->internal_code,
                    'descripcion' => $description,
                    'cantidad' => $quantity,
                    'unidadMedida' => (int) $product?->measurement_unit_code,
                    'precioUnitario' => $unitPrice,
                    'montoDescuento' => $discount,
                    'subTotal' => $subtotal,
                    'numeroSerie' => null,
                    'numeroImei' => null,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{subtotal: float, discount: float, total: float, taxable: float}
     */
    private function totals(array $lines, float $discount): array
    {
        $subtotal = round((float) collect($lines)->sum('subTotal'), 5);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'total_discount' => 'El descuento adicional no puede ser mayor al subtotal.',
            ]);
        }

        $total = round($subtotal - $discount, 5);

        return [
            'subtotal' => $subtotal,
            'discount' => round($discount, 5),
            'total' => $total,
            'taxable' => $total,
        ];
    }

    private function nextInvoiceNumber(int $companyId): int
    {
        return DB::transaction(function () use ($companyId): int {
            $lastNumber = SinInvoiceIssue::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('status_code', 908)
                ->where('transaccion', true)
                ->whereNotNull('invoice_number')
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->value('invoice_number');

            return ((int) $lastNumber) + 1;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array{subtotal: float, discount: float, total: float, taxable: float}  $totals
     * @param  array<string, mixed>  $data
     * @return array{cabecera: array<string, mixed>, detalle: array<int, array<string, mixed>>}
     */
    private function invoiceData(
        User $user,
        object $company,
        SinAuthorization $authorization,
        SinPointOfSale $pointOfSale,
        Customer $customer,
        SinCuis $cuis,
        SinCufd $cufd,
        Carbon $issuedAt,
        int $invoiceNumber,
        string $cuf,
        array $lines,
        array $totals,
        array $data,
    ): array {
        return [
            'cabecera' => [
                'nitEmisor' => $authorization->tax_id,
                'razonSocialEmisor' => $authorization->legal_name ?: $company->legal_name ?: $company->name,
                'municipio' => $company->city ?: 'Bolivia',
                'telefono' => $company->phone ?: null,
                'numeroFactura' => $invoiceNumber,
                'cuf' => $cuf,
                'cufd' => $cufd->cufd_code,
                'codigoSucursal' => $pointOfSale->branch->branch_code,
                'direccion' => $cufd->address ?: $company->address ?: 'Sin direccion registrada',
                'codigoPuntoVenta' => $pointOfSale->point_of_sale_code,
                'fechaEmision' => $issuedAt->format('Y-m-d\TH:i:s.v'),
                'nombreRazonSocial' => $customer->name,
                'codigoTipoDocumentoIdentidad' => $customer->identity_document_type_code,
                'numeroDocumento' => $customer->document_number,
                'complemento' => $customer->document_complement,
                'codigoCliente' => $customer->customer_code,
                'codigoMetodoPago' => (int) $data['payment_method_code'],
                'numeroTarjeta' => null,
                'montoTotal' => $this->amount($totals['total']),
                'montoTotalSujetoIva' => $this->amount($totals['taxable']),
                'codigoMoneda' => (int) $data['currency_code'],
                'tipoCambio' => '1.00',
                'montoTotalMoneda' => $this->amount($totals['total']),
                'montoGiftCard' => null,
                'descuentoAdicional' => $this->amount($totals['discount']),
                'codigoExcepcion' => null,
                'cafc' => null,
                'leyenda' => $this->legend(),
                'usuario' => Str::limit($user->name ?: $user->email, 50, ''),
                'codigoDocumentoSector' => self::DOCUMENT_SECTOR_PURCHASE_SALE,
            ],
            'detalle' => array_map(fn (array $line): array => [
                'actividadEconomica' => $line['actividadEconomica'],
                'codigoProductoSin' => $line['codigoProductoSin'],
                'codigoProducto' => $line['codigoProducto'],
                'descripcion' => $line['descripcion'],
                'cantidad' => $this->quantity($line['cantidad']),
                'unidadMedida' => $line['unidadMedida'],
                'precioUnitario' => $this->amount($line['precioUnitario']),
                'montoDescuento' => $this->amount($line['montoDescuento']),
                'subTotal' => $this->amount($line['subTotal']),
                'numeroSerie' => null,
                'numeroImei' => null,
            ], $lines),
        ];
    }

    /**
     * @return array{xml: string, gzip: string}
     */
    private function storeFiles(int $companyId, int $invoiceNumber, string $xml, string $gzip): array
    {
        $basePath = 'invoices/'.$companyId.'/'.now()->format('Y/m');
        $baseName = 'factura-'.$invoiceNumber.'-'.Str::random(8);
        $xmlPath = $basePath.'/'.$baseName.'.xml';
        $gzipPath = $basePath.'/'.$baseName.'.xml.gz';

        Storage::disk('local')->put($xmlPath, $xml);
        Storage::disk('local')->put($gzipPath, $gzip);

        return ['xml' => $xmlPath, 'gzip' => $gzipPath];
    }

    /**
     * @param  array{xml: string, gzip: string}  $paths
     * @param  array<string, mixed>  $invoiceData
     * @param  array{subtotal: float, discount: float, total: float, taxable: float}  $totals
     */
    private function storePendingIssue(
        User $user,
        SinAuthorization $authorization,
        SinPointOfSale $pointOfSale,
        Customer $customer,
        SinCuis $cuis,
        SinCufd $cufd,
        Carbon $issuedAt,
        int $invoiceNumber,
        string $cuf,
        string $hash,
        array $paths,
        array $invoiceData,
        array $totals,
    ): SinInvoiceIssue {
        return SinInvoiceIssue::query()->create([
            'company_id' => (int) $user->company_id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sin_api_token_id' => $cufd->sin_api_token_id,
            'sin_authorization_id' => $authorization->id,
            'sin_branch_id' => $pointOfSale->sin_branch_id,
            'sin_point_of_sale_id' => $pointOfSale->id,
            'sin_cuis_id' => $cuis->id,
            'sin_cufd_id' => $cufd->id,
            'tax_id' => $authorization->tax_id,
            'environment_code' => $authorization->environment_code,
            'modality_code' => $authorization->modality_code,
            'emission_type_code' => self::EMISSION_TYPE_ONLINE,
            'document_sector_code' => self::DOCUMENT_SECTOR_PURCHASE_SALE,
            'invoice_document_type_code' => self::INVOICE_DOCUMENT_WITH_TAX_CREDIT,
            'branch_code' => $pointOfSale->branch->branch_code,
            'point_of_sale_code' => $pointOfSale->point_of_sale_code,
            'attempted_invoice_number' => $invoiceNumber,
            'invoice_number' => null,
            'cuf' => $cuf,
            'cufd_code' => $cufd->cufd_code,
            'control_code' => $cufd->control_code,
            'status_label' => 'Pendiente',
            'xml_path' => $paths['xml'],
            'gzip_path' => $paths['gzip'],
            'hash_file' => $hash,
            'subtotal_amount' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_amount' => $totals['total'],
            'taxable_amount' => $totals['taxable'],
            'payload' => $invoiceData,
            'issued_at' => $issuedAt,
        ]);
    }

    private function send(
        SinInvoiceIssue $issue,
        SinApiToken $apiToken,
        SinAuthorization $authorization,
        SinPointOfSale $pointOfSale,
        SinCuis $cuis,
        SinCufd $cufd,
        string $gzip,
        string $hash,
    ): SinInvoiceIssue {
        $startedAt = microtime(true);

        try {
            $client = $this->clients->make(SiatWsdlRegistry::PURCHASE_SALE_INVOICE, (string) $apiToken->api_token, 30);
            $payload = [
                'SolicitudServicioRecepcionFactura' => [
                    'codigoAmbiente' => $authorization->environment_code->value,
                    'codigoDocumentoSector' => self::DOCUMENT_SECTOR_PURCHASE_SALE,
                    'codigoEmision' => self::EMISSION_TYPE_ONLINE,
                    'codigoModalidad' => $authorization->modality_code->value,
                    'codigoPuntoVenta' => $pointOfSale->point_of_sale_code,
                    'codigoSistema' => (string) $authorization->system_code,
                    'codigoSucursal' => $pointOfSale->branch->branch_code,
                    'cufd' => (string) $cufd->cufd_code,
                    'cuis' => (string) $cuis->cuis_code,
                    'nit' => $authorization->tax_id,
                    'tipoFacturaDocumento' => self::INVOICE_DOCUMENT_WITH_TAX_CREDIT,
                    'archivo' => new SoapVar($gzip, XSD_BASE64BINARY),
                    'fechaEnvio' => now()->format('Y-m-d\TH:i:s.v'),
                    'hashArchivo' => $hash,
                ],
            ];
            $response = $client->recepcionFactura($payload);
            $responseData = $this->normalizeResponse($response);
            $statusCode = $this->findInt($responseData, ['codigoEstado']);
            $transaccion = $this->findTransaction($responseData) ?? $statusCode === 908;
            $message = $this->messageFor($statusCode, $transaccion, $responseData);
            $validated = $statusCode === 908 && $transaccion;

            $issue->update([
                'invoice_number' => $validated ? $issue->attempted_invoice_number : null,
                'reception_code' => $this->findValue($responseData, ['codigoRecepcion']),
                'status_code' => $statusCode,
                'status_label' => $this->statusLabel($statusCode, $transaccion),
                'transaccion' => $transaccion,
                'response' => $responseData,
                'message' => $message,
                'duration_ms' => $this->durationMs($startedAt),
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $issue->update([
                'status_label' => 'Error',
                'transaccion' => false,
                'message' => 'No se pudo enviar la factura al SIN: '.Str::limit($exception->getMessage(), 280),
                'duration_ms' => $this->durationMs($startedAt),
                'sent_at' => now(),
            ]);
        }

        return $issue->refresh();
    }

    private function legend(): string
    {
        $legend = SinCatalogItem::query()
            ->where('catalog_key', 'leyendas_factura')
            ->active()
            ->inRandomOrder()
            ->first();

        return $legend?->description ?: 'Ley Nro 453: El proveedor debe brindar atencion sin discriminacion.';
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
     * @param  array<int, string>  $keys
     */
    private function findValue(array $data, array $keys): ?string
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $keys, true) && is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }

            if (is_array($value)) {
                $found = $this->findValue($value, $keys);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function findInt(array $data, array $keys): ?int
    {
        $value = $this->findValue($data, $keys);

        return is_numeric($value) ? (int) $value : null;
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

    /**
     * @param  array<string, mixed>  $response
     */
    private function messageFor(?int $statusCode, bool $transaccion, array $response): string
    {
        if ($statusCode === 908 && $transaccion) {
            return 'Factura validada por el SIN.';
        }

        if ($statusCode === 904) {
            return $this->findValue($response, ['descripcion', 'mensaje', 'descripcionMensaje'])
                ?: 'Factura observada por el SIN.';
        }

        return $this->findValue($response, ['descripcion', 'mensaje', 'descripcionMensaje'])
            ?: 'El SIN respondio la recepcion de factura.';
    }

    private function statusLabel(?int $statusCode, bool $transaccion): string
    {
        return match ($statusCode) {
            908 => $transaccion ? 'Validado' : 'Observado',
            904 => 'Observado',
            default => $transaccion ? 'Procesado' : 'Observado',
        };
    }

    private function amount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function quantity(float|int|string $amount): string
    {
        return rtrim(rtrim(number_format((float) $amount, 5, '.', ''), '0'), '.');
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
