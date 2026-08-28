@extends('layouts.admin')

@section('title', 'Historial de cajas')
@section('page-title', 'Historial de cajas')
@section('page-subtitle', 'Cajas cerradas de la empresa activa')

@section('content')
    <x-ui.table-card title="Cajas cerradas">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Caja</th><th>Responsable</th><th>Apertura</th><th>Cierre</th><th class="text-end">Monto inicial</th><th class="text-end">Monto final</th><th class="text-end">Cobros</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @forelse ($cashRegisters as $cashRegister)
                    <tr>
                        <td><span class="fw-semibold">#{{ $cashRegister->id }}</span><small class="d-block text-body-secondary">Cerrada</small></td>
                        <td>{{ $cashRegister->user?->name ?? 'Usuario no disponible' }}</td>
                        <td>{{ $cashRegister->opened_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $cashRegister->closed_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">Bs {{ money_format_decimal($cashRegister->opening_amount) }}</td>
                        <td class="text-end">Bs {{ money_format_decimal($cashRegister->closing_amount) }}</td>
                        <td class="text-end"><span class="fw-semibold">Bs {{ money_format_decimal($cashRegister->account_payments_sum_amount ?? 0) }}</span><small class="d-block text-body-secondary">{{ $cashRegister->account_payments_count }} pagos</small></td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('cash-registers.show', $cashRegister) }}"><i class="ti ti-eye me-1" aria-hidden="true"></i>Ver detalle</a></td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="8" message="Todavía no existen cajas cerradas." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $cashRegisters->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
