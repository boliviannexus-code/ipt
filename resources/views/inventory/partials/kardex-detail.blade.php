<div class="mb-3">
    <div class="text-body-secondary">Producto</div>
    <div class="h3 mb-1">{{ $product->name }}</div>
    <div class="text-body-secondary">
        {{ $warehouse ? 'Almacen: '.$warehouse->name : 'Todos los almacenes' }}
    </div>
</div>

<div class="table-responsive">
    <table class="table table-sm table-vcenter mb-0">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Almacen</th>
                <th>Presentacion</th>
                <th class="text-end">Saldo anterior</th>
                <th class="text-end">Entrada</th>
                <th class="text-end">Salida</th>
                <th class="text-end">Saldo actual</th>
                <th>Usuario</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $movement)
                @php
                    $quantity = (int) $movement->quantity;
                    $entry = $quantity > 0 ? $quantity : 0;
                    $exit = $quantity < 0 ? abs($quantity) : 0;
                    $presentation = $movement->presentation_name
                        ? $movement->presentation_name.' x '.$movement->units_per_package.' ('.abs((int) $movement->package_quantity).' empaques)'
                        : '-';
                @endphp
                <tr>
                    <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $movement->type?->label() ?? (string) $movement->type }}</td>
                    <td>
                        <div>{{ $movement->warehouse?->name ?? '-' }}</div>
                        <div class="text-body-secondary small">{{ $movement->warehouse?->branch?->name }}</div>
                    </td>
                    <td>{{ $presentation }}</td>
                    <td class="text-end text-body-secondary">{{ number_format((int) $movement->previous_balance) }}</td>
                    <td class="text-end text-success fw-semibold">{{ $entry > 0 ? number_format($entry) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $exit > 0 ? number_format($exit) : '-' }}</td>
                    <td class="text-end fw-semibold">{{ number_format((int) $movement->running_balance) }}</td>
                    <td>{{ $movement->user?->name ?? '-' }}</td>
                    <td>{{ $movement->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center text-body-secondary py-4" colspan="10">Este producto no tiene movimientos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
