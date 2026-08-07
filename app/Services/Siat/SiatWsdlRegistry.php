<?php

namespace App\Services\Siat;

use Illuminate\Support\Collection;

class SiatWsdlRegistry
{
    public const SYNCHRONIZATION = 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionSincronizacion?wsdl';

    public const OPERATIONS = 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionOperaciones?wsdl';

    public const CODES = 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?wsdl';

    public const PURCHASE_SALE_INVOICE = 'https://pilotosiatservicios.impuestos.gob.bo/v2/ServicioFacturacionCompraVenta?wsdl';

    public static function relatedService(string $configuredWsdl, string $service): string
    {
        $parts = parse_url(trim($configuredWsdl));

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \InvalidArgumentException('El WSDL configurado para SIAT no es válido.');
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/v2/FacturacionCodigos');
        $versionPath = preg_match('#^/v\d+/#', $path) === 1
            ? preg_replace('#^(/v\d+/).*$#', '$1', $path)
            : '/v2/';

        return $parts['scheme'].'://'.$parts['host'].$port.$versionPath.$service.'?wsdl';
    }

    /**
     * @return Collection<int, array{key: string, name: string, url: string}>
     */
    public function all(): Collection
    {
        return collect([
            ['key' => 'synchronization', 'name' => 'Servicio de sincronizacion de datos', 'url' => self::SYNCHRONIZATION],
            ['key' => 'operations', 'name' => 'Servicio de operaciones', 'url' => self::OPERATIONS],
            ['key' => 'codes', 'name' => 'Servicio de obtencion de codigos', 'url' => self::CODES],
            ['key' => 'purchase_sale_invoice', 'name' => 'Factura compra-venta', 'url' => self::PURCHASE_SALE_INVOICE],
        ]);
    }
}
