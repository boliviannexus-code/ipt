<?php

namespace App\Services\Siat;

use DOMDocument;
use DOMElement;

class PurchaseSaleInvoiceXmlBuilder
{
    /**
     * @param  array<string, mixed>  $invoice
     */
    public function build(array $invoice): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;
        $document->preserveWhiteSpace = false;

        $root = $document->createElement('facturaComputarizadaCompraVenta');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', 'facturaComputarizadaCompraVenta.xsd');
        $document->appendChild($root);

        $cabecera = $document->createElement('cabecera');
        $root->appendChild($cabecera);

        foreach ($invoice['cabecera'] as $key => $value) {
            $this->append($document, $cabecera, (string) $key, $value);
        }

        foreach ($invoice['detalle'] as $line) {
            $detalle = $document->createElement('detalle');
            $root->appendChild($detalle);

            foreach ($line as $key => $value) {
                $this->append($document, $detalle, (string) $key, $value);
            }
        }

        return $document->saveXML() ?: '';
    }

    private function append(DOMDocument $document, DOMElement $parent, string $name, mixed $value): void
    {
        $element = $document->createElement($name);

        if ($value === null) {
            $element->setAttribute('xsi:nil', 'true');
        } else {
            $element->appendChild($document->createTextNode((string) $value));
        }

        $parent->appendChild($element);
    }
}
