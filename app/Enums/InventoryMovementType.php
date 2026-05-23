<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case InitialStock = 'initial_stock';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case DefragmentOut = 'defragment_out';
    case DefragmentIn = 'defragment_in';

    public function label(): string
    {
        return match ($this) {
            self::InitialStock => 'Stock inicial',
            self::Purchase => 'Compra',
            self::Sale => 'Venta',
            self::AdjustmentIn => 'Ingreso manual',
            self::AdjustmentOut => 'Salida manual',
            self::TransferIn => 'Ingreso por traspaso',
            self::TransferOut => 'Salida por traspaso',
            self::DefragmentOut => 'Desfragmentacion de empaque',
            self::DefragmentIn => 'Ingreso por desfragmentacion',
        };
    }
}
