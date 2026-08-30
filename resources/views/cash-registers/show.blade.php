@extends('layouts.admin')

@section('title', 'Detalle de caja #' . $cashRegister->id)
@section('page-title', 'Detalle de caja #' . $cashRegister->id)
@section('page-subtitle', 'Resumen de una caja cerrada')

@section('content')
    @php($collected = $cashRegister->accountPayments->sum(fn ($payment) => (float) $payment->amount))
    <div class="mb-3"><a class="btn btn-outline-secondary" href="{{ route('cash-registers.history') }}"><i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver al historial</a></div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="text-body-secondary small">Responsable</div><div class="fw-semibold">{{ $cashRegister->user?->name ?? 'Usuario no disponible' }}</div><small class="text-body-secondary">{{ $cashRegister->user?->email }}</small></div>
                <div class="col-sm-6 col-lg-3"><div class="text-body-secondary small">Apertura</div><div class="fw-semibold">{{ $cashRegister->opened_at->format('d/m/Y H:i') }}</div><small class="text-body-secondary">Inicial: Bs {{ money_format_decimal($cashRegister->opening_amount) }}</small></div>
                <div class="col-sm-6 col-lg-3"><div class="text-body-secondary small">Cierre</div><div class="fw-semibold">{{ $cashRegister->closed_at->format('d/m/Y H:i') }}</div><small class="text-body-secondary">Final: Bs {{ money_format_decimal($cashRegister->closing_amount) }}</small></div>
                <div class="col-sm-6 col-lg-3"><div class="text-body-secondary small">Cobros registrados</div><div class="h2 text-success mb-0">Bs {{ money_format_decimal($collected) }}</div><small class="text-body-secondary">{{ $cashRegister->accountPayments->count() }} pagos</small></div>
            </div>
            @if ($cashRegister->opening_notes || $cashRegister->closing_notes)
                <div class="row g-3 border-top mt-3 pt-3">
                    <div class="col-md-6"><div class="text-body-secondary small">Observación de apertura</div><div>{{ $cashRegister->opening_notes ?: 'Sin observación' }}</div></div>
                    <div class="col-md-6"><div class="text-body-secondary small">Observación de cierre</div><div>{{ $cashRegister->closing_notes ?: 'Sin observación' }}</div></div>
                </div>
            @endif
        </div>
    </div>

    <x-ui.table-card title="Pagos registrados en esta caja">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Comprobante</th><th>Estudiante</th><th>Método</th><th>Registrado por</th><th class="text-end">Monto</th></tr></thead>
            <tbody>
                @forelse ($cashRegister->accountPayments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                        <td>PAGO-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ collect([$payment->contract?->student?->first_name, $payment->contract?->student?->paternal_surname, $payment->contract?->student?->maternal_surname])->filter()->join(' ') ?: 'No disponible' }}</td>
                        <td>{{ $paymentMethodLabels->get($payment->payment_method_code, 'Método no disponible') }}@if($payment->reference)<small class="d-block text-body-secondary">Ref.: {{ $payment->reference }}</small>@endif</td>
                        <td>{{ $payment->receiver?->name ?? 'Usuario no disponible' }}</td>
                        <td class="text-end fw-semibold">Bs {{ money_format_decimal($payment->amount) }}</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="Esta caja no tiene pagos de mensualidades registrados." />
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
@endsection
