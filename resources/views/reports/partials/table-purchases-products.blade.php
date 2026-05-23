<x-ui.table-card title="Compras por producto">
    @if ($singleColumn)
        <div class="row g-3 p-3 border-bottom">
            <div class="col-md-4"><span class="text-body-secondary">Compras:</span> <strong>{{ $purchasesSummary['count'] }}</strong></div>
            <div class="col-md-4"><span class="text-body-secondary">Subtotal:</span> <strong>{{ money_format_decimal($purchasesSummary['subtotal']) }}</strong></div>
            <div class="col-md-4"><span class="text-body-secondary">Total:</span> <strong>{{ money_format_decimal($purchasesSummary['total']) }}</strong></div>
        </div>
    @endif
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            @forelse ($purchasesByProduct as $row)
                <tr><td>{{ $row->name }}</td><td class="text-end">{{ number_format((float) $row->units) }}</td><td class="text-end fw-semibold">{{ money_format_decimal($row->total) }}</td></tr>
            @empty
                <tr><td class="text-center text-body-secondary py-3" colspan="3">Sin compras en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-ui.table-card>
