@if ($applications->isNotEmpty())
    <div class="mt-3 mb-3">
        <x-ui.table-card title="Aplicaciones de saldo a favor">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Movimiento</th><th>Matrícula</th><th>Caja</th><th>Registrado por</th><th class="text-end">Saldo utilizado</th></tr></thead>
                <tbody>
                    @foreach ($applications->sortByDesc('created_at') as $application)
                        <tr><td>{{ $application->created_at->format('d/m/Y H:i') }}</td><td>SALDO-{{ str_pad((string) $application->id, 6, '0', STR_PAD_LEFT) }}</td><td>{{ $application->contract?->account_number }}</td><td>#{{ $application->cash_register_id }}</td><td>{{ $application->operator?->name }}</td><td class="text-end">Bs {{ money_format_decimal($application->amount) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.table-card>
    </div>
@endif
