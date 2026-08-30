@extends('layouts.admin')

@section('title', 'Mi caja | '.config('app.name', 'Base Admin'))
@section('page-title', 'Mi caja')
@section('page-subtitle', 'Resumen, apertura y cierre de tu caja')

@section('content')
    @php
        $collectedAmount = $activeCashRegister?->accountPayments->sum(fn ($payment) => (float) $payment->amount) ?? 0;
        $registeredAmount = ($activeCashRegister ? (float) $activeCashRegister->opening_amount : 0) + $collectedAmount;
    @endphp
    @if ($errors->has('cash_register'))
        <div class="alert alert-danger" role="alert">
            <i class="ti ti-alert-circle me-1" aria-hidden="true"></i>{{ $errors->first('cash_register') }}
        </div>
    @endif

    @if ($activeCashRegister)
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small">Monto inicial</div><div class="h2 mb-0">Bs {{ money_format_decimal($activeCashRegister->opening_amount) }}</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small">Total cobrado</div><div class="h2 text-success mb-0">Bs {{ money_format_decimal($collectedAmount) }}</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small">Movimientos</div><div class="h2 mb-0">{{ $activeCashRegister->accountPayments->count() }}</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-body-secondary small">Total registrado</div><div class="h2 text-primary mb-0">Bs {{ money_format_decimal($registeredAmount) }}</div></div></div></div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            @if ($activeCashRegister)
                <x-ui.card title="Mi caja activa">
                    <div class="card-body">
                        <dl class="row mb-4">
                            <dt class="col-sm-5">Apertura</dt>
                            <dd class="col-sm-7">{{ $activeCashRegister->opened_at->format('d/m/Y H:i') }}</dd>

                            <dt class="col-sm-5">Monto inicial</dt>
                            <dd class="col-sm-7 fw-semibold">Bs {{ money_format_decimal($activeCashRegister->opening_amount) }}</dd>

                            <dt class="col-sm-5">Estado</dt>
                            <dd class="col-sm-7"><span class="badge text-bg-success">Activa</span></dd>

                            @if ($activeCashRegister->opening_notes)
                                <dt class="col-sm-5">Nota</dt>
                                <dd class="col-sm-7">{{ $activeCashRegister->opening_notes }}</dd>
                            @endif
                        </dl>

                        @can('close', $activeCashRegister)
                            <form method="POST" action="{{ route('cash-registers.close', $activeCashRegister) }}" novalidate>
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label class="form-label" for="closing-amount">Monto contado al cierre</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs</span>
                                        <input
                                            class="form-control @error('closing_amount') is-invalid @enderror"
                                            id="closing-amount"
                                            name="closing_amount"
                                            type="number"
                                            min="0"
                                            max="9999999999999999.99"
                                            step="0.01"
                                            inputmode="decimal"
                                            value="{{ old('closing_amount') }}"
                                            required
                                        >
                                        @error('closing_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="closing-notes">Observacion de cierre</label>
                                    <textarea
                                        class="form-control @error('closing_notes') is-invalid @enderror"
                                        id="closing-notes"
                                        name="closing_notes"
                                        rows="3"
                                        maxlength="1000"
                                    >{{ old('closing_notes') }}</textarea>
                                    @error('closing_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button class="btn btn-danger w-100" type="submit">
                                    <i class="ti ti-lock me-1" aria-hidden="true"></i>Cerrar caja
                                </button>
                            </form>
                        @endcan
                    </div>
                </x-ui.card>
            @else
                @can('create', \App\Models\CashRegister::class)
                    <x-ui.card title="Abrir mi caja">
                        <div class="card-body">
                            <form method="POST" action="{{ route('cash-registers.store') }}" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="opening-amount">Monto inicial</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs</span>
                                        <input
                                            class="form-control @error('opening_amount') is-invalid @enderror"
                                            id="opening-amount"
                                            name="opening_amount"
                                            type="number"
                                            min="0"
                                            max="9999999999999999.99"
                                            step="0.01"
                                            inputmode="decimal"
                                            value="{{ old('opening_amount', '0.00') }}"
                                            required
                                            autofocus
                                        >
                                        @error('opening_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="opening-notes">Observacion de apertura</label>
                                    <textarea
                                        class="form-control @error('opening_notes') is-invalid @enderror"
                                        id="opening-notes"
                                        name="opening_notes"
                                        rows="3"
                                        maxlength="1000"
                                    >{{ old('opening_notes') }}</textarea>
                                    @error('opening_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="ti ti-lock-open me-1" aria-hidden="true"></i>Abrir caja
                                </button>
                            </form>
                        </div>
                    </x-ui.card>
                @endcan
            @endif
        </div>

        <div class="col-lg-7">
            <x-ui.card title="Control operativo">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <span class="avatar bg-primary-lt text-primary"><i class="ti ti-user-dollar"></i></span>
                        <div>
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            <div class="text-body-secondary small">{{ auth()->user()->company?->name }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-body-secondary small mb-1">Estado personal</div>
                                <div class="fw-semibold">{{ $activeCashRegister ? 'Caja activa' : 'Sin caja activa' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-body-secondary small mb-1">Registros de la empresa</div>
                                <div class="fw-semibold">{{ $cashRegisterHistoryCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    @if ($activeCashRegister)
        <x-ui.table-card title="Movimientos de la caja activa">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Fecha y hora</th><th>Comprobante</th><th>Estudiante</th><th>Método de pago</th><th class="text-end">Monto</th></tr></thead>
                <tbody>
                    @forelse ($activeCashRegister->accountPayments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td><span class="fw-semibold">PAGO-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</span></td>
                            <td>{{ collect([$payment->contract?->student?->first_name, $payment->contract?->student?->paternal_surname, $payment->contract?->student?->maternal_surname])->filter()->join(' ') ?: 'No disponible' }}</td>
                            <td>{{ $paymentMethodLabels->get($payment->payment_method_code, 'Método no disponible') }}@if($payment->reference)<small class="d-block text-body-secondary">Ref.: {{ $payment->reference }}</small>@endif</td>
                            <td class="text-end fw-semibold text-success">Bs {{ money_format_decimal($payment->amount) }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="5" message="Todavía no existen movimientos registrados en esta caja." />
                    @endforelse
                </tbody>
            </table>
        </x-ui.table-card>
    @endif

@endsection
