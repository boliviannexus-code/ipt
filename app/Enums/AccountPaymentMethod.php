<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum AccountPaymentMethod: int
{
    case Cash = 1;
    case Qr = 2;
    case Transfer = 3;

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Qr => 'QR',
            self::Transfer => 'Transferencia',
        };
    }

    public function requiresReference(): bool
    {
        return $this !== self::Cash;
    }

    /** @return Collection<int, array{code: int, label: string, requires_reference: bool}> */
    public static function options(): Collection
    {
        return collect(self::cases())->map(fn (self $method): array => [
            'code' => $method->value,
            'label' => $method->label(),
            'requires_reference' => $method->requiresReference(),
        ]);
    }

    /** @return Collection<int, string> */
    public static function labels(): Collection
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $method): array => [$method->value => $method->label()]
        );
    }

    /** @return array<int, int> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
