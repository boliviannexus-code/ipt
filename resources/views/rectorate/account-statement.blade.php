@extends('layouts.admin')

@section('title', 'Estado de cuenta y cobros')
@section('page-title', 'Estado de cuenta y cobros')
@section('page-subtitle', 'Matrícula ' . $contract->account_number . ' · ' . $contract->program->title)

@section('content')
    @php
        $application = $contract->application;
        $customer = $application->customer;
        $total = $contract->charges->where('status', '!=', 'cancelled')->sum(fn ($charge) => (float) $charge->amount);
        $paid = $contract->charges->where('status', '!=', 'cancelled')->sum(fn ($charge) => (float) $charge->paid_amount);
        $balance = $total - $paid;
        $received = $contract->payments->sum(fn ($payment) => (float) $payment->amount);
        $allocated = $contract->payments->sum(fn ($payment) => (float) $payment->allocations_sum_amount);
        $credit = max(0, round($received - $allocated, 2));
        $amountDue = max(0, round($balance - $credit, 2));
        $studentName = collect([$contract->student->first_name, $contract->student->paternal_surname, $contract->student->maternal_surname])->filter()->join(' ');
        $holderName = collect([$application->first_name, $application->paternal_surname, $application->maternal_surname])->filter()->join(' ');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('rectorate.collectible-accounts.index') }}">
            <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver a cuentas por cobrar
        </a>
        <span class="badge fs-6 {{ $contract->status === 'enrolled' ? 'text-bg-success' : 'text-bg-warning' }}">
            {{ $contract->statusLabel() }}
        </span>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Matrícula / Sede</div>
                    <div class="fw-semibold">{{ $contract->account_number }}</div>
                    <div class="text-body-secondary small">{{ $application->campus?->name }}</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Programa</div>
                    <div class="fw-semibold">{{ $contract->program->title }}</div>
                    <div class="text-body-secondary small">{{ $contract->program->duration_months }} meses</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Plan</div>
                    <div class="fw-semibold">{{ $contract->plan->name }}</div>
                    <div class="text-body-secondary small">Bs {{ number_format((float) $contract->monthly_amount, 2, ',', '.') }} / mes</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Estudiante</div>
                    <div class="fw-semibold">{{ $studentName }}</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Titular</div>
                    <div class="fw-semibold">{{ $holderName }}</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="text-body-secondary small mb-1">Facturación</div>
                    <div class="fw-semibold">{{ $customer->name }}</div>
                    <div class="text-body-secondary small">NIT {{ $customer->document_number }}{{ $customer->document_complement ? '-' . $customer->document_complement : '' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach (['Total recibido' => $received, 'Aplicado a cargos' => $allocated, 'Saldo a favor disponible' => $credit] as $label => $value)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small">{{ $label }}</div>
                        <div class="h2 mb-0">Bs {{ number_format($value, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xxl-7">
            <x-ui.table-card title="Detalle de cargos">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Periodo</th><th>Concepto</th><th>Vencimiento</th><th class="text-end">Importe</th><th class="text-end">Pagado</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($contract->charges as $charge)
                            <tr>
                                <td>{{ $charge->period?->format('m/Y') ?? 'Adicional' }}</td>
                                <td>{{ $charge->concept ?? 'Mensualidad' }}@if($charge->detail)<small class="d-block text-secondary text-break">{{ $charge->detail }}</small>@endif</td>
                                <td>{{ $charge->due_date->format('d/m/Y') }}</td>
                                <td class="text-end">Bs {{ number_format((float) $charge->amount, 2, ',', '.') }}</td>
                                <td class="text-end">Bs {{ number_format((float) $charge->paid_amount, 2, ',', '.') }}</td>
                                <td><span class="badge {{ $charge->status === 'paid' ? 'text-bg-success' : ($charge->status === 'partial' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ ['paid' => 'Pagada', 'partial' => 'Parcial', 'pending' => 'Pendiente', 'cancelled' => 'Anulada'][$charge->status] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-12 col-xxl-5">
            <div class="card border-success">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-cash me-2 text-success" aria-hidden="true"></i>Registrar pago</h3></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 bg-danger-lt rounded p-3 mb-3">
                        <div class="text-body-secondary fw-semibold">Monto pendiente de cobro</div>
                        <div class="h1 text-danger mb-0">Bs {{ number_format($amountDue, 2, ',', '.') }}</div>
                    </div>
                    @if (! $activeCashRegister)
                        <div class="alert alert-warning">Debes abrir tu caja antes de registrar un pago.</div>
                        <a class="btn btn-primary w-100" href="{{ route('cash-registers.index') }}">Ir a cajas</a>
                    @elseif ($contract->status !== 'cancelled')
                        <form method="POST" action="{{ route('rectorate.contracts.payments.store', $contract) }}" data-account-payment-form data-disable-on-submit data-submitting-label="Registrando…">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required" for="amount">Monto a pagar</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Bs</span>
                                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" min="{{ $credit >= $balance && $balance > 0 ? '0' : '0.01' }}" max="9999999999.99" step="0.01" inputmode="decimal" value="{{ old('amount', $balance > 0 ? number_format($amountDue, 2, '.', '') : '') }}" required autofocus>
                                </div>
                                @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label required" for="payment_method_code">Método de pago</label>
                                <select class="form-select @error('payment_method_code') is-invalid @enderror" id="payment_method_code" name="payment_method_code" @required($amountDue > 0)>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method['code'] }}" data-requires-reference="{{ $method['requires_reference'] ? '1' : '0' }}" @selected((int) old('payment_method_code', 1) === $method['code'])>{{ $method['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4 {{ in_array((int) old('payment_method_code', 1), [2, 3], true) ? '' : 'd-none' }}" data-payment-reference-field>
                                <label class="form-label required" for="reference">Referencia</label>
                                <input class="form-control @error('reference') is-invalid @enderror" id="reference" name="reference" value="{{ old('reference') }}" maxlength="100" autocomplete="off" placeholder="Número de operación o comprobante" @required(in_array((int) old('payment_method_code', 1), [2, 3], true))>
                                @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-success btn-lg w-100" type="submit"><i class="ti ti-check me-1" aria-hidden="true"></i><span>Registrar pago</span></button>
                        </form>
                    @else
                        <div class="alert alert-success mb-0"><i class="ti ti-circle-check me-1" aria-hidden="true"></i>Esta matrícula está anulada.</div>
                    @endif
                    @error('contract')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror
                    @error('cash_register')<div class="alert alert-danger mt-3">{{ $message }}</div>@enderror

                </div>
            </div>
        </div>
    </div>

    <x-ui.table-card title="Historial de pagos">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Fecha y hora</th><th>Comprobante</th><th>Caja</th><th>Método de pago</th><th class="text-end">Monto recibido</th><th class="text-end">Aplicado a la fecha</th><th class="text-end">Disponible a favor</th></tr></thead>
            <tbody>
                @forelse ($contract->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                        <td><span class="fw-semibold">PAGO-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</span></td>
                        <td>#{{ $payment->cash_register_id }}</td>
                        <td>{{ $paymentMethodLabels->get($payment->payment_method_code, 'Método no disponible') }}@if($payment->reference)<small class="d-block text-body-secondary">Ref.: {{ $payment->reference }}</small>@endif</td>
                        <td class="text-end fw-semibold text-success">Bs {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                        <td class="text-end">Bs {{ number_format((float) $payment->allocations_sum_amount, 2, ',', '.') }}</td>
                        <td class="text-end">Bs {{ number_format(max(0, (float) $payment->amount - (float) $payment->allocations_sum_amount), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="Todavía no existen pagos registrados para este contrato." />
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
    @include('cash-registers.partials.credit-applications', ['applications' => $contract->creditApplications])
@endsection
