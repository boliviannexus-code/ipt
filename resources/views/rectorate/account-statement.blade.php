@extends('layouts.admin')

@section('title', 'Cobro de mensualidad')
@section('page-title', 'Cobro de mensualidad')
@section('page-subtitle', 'Cuenta ' . $contract->application->account_number . ' · Contrato #' . $contract->contract_number . ' · ' . $contract->program->title)

@section('content')
    @php
        $application = $contract->application;
        $customer = $application->customer;
        $total = $contract->charges->where('status', '!=', 'cancelled')->sum(fn ($charge) => (float) $charge->amount);
        $paid = $contract->charges->sum(fn ($charge) => (float) $charge->paid_amount);
        $balance = $total - $paid;
        $studentName = collect([$contract->student->first_name, $contract->student->paternal_surname, $contract->student->maternal_surname])->filter()->join(' ');
        $holderName = collect([$application->first_name, $application->paternal_surname, $application->maternal_surname])->filter()->join(' ');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('rectorate.collectible-accounts.index') }}">
            <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>Volver a cuentas por cobrar
        </a>
        <span class="badge fs-6 {{ $contract->status === 'enrolled' ? 'text-bg-success' : 'text-bg-warning' }}">
            {{ $contract->status === 'enrolled' ? 'Inscrito' : 'Preinscrito' }}
        </span>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-sm-6 col-xl-3">
                    <div class="text-body-secondary small mb-1">Cuenta / Sede</div>
                    <div class="fw-semibold">{{ $application->account_number }}</div>
                    <div class="text-body-secondary small">{{ $application->campus?->name }}</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="text-body-secondary small mb-1">Programa</div>
                    <div class="fw-semibold">{{ $contract->program->title }}</div>
                    <div class="text-body-secondary small">{{ $contract->program->duration_months }} meses</div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="text-body-secondary small mb-1">Plan</div>
                    <div class="fw-semibold">{{ $contract->plan->name }}</div>
                    <div class="text-body-secondary small">Bs {{ number_format((float) $contract->monthly_amount, 2, ',', '.') }} / mes</div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="text-body-secondary small mb-1">Estudiante</div>
                    <div class="fw-semibold">{{ $studentName }}</div>
                </div>
                <div class="col-sm-6 col-xl-2">
                    <div class="text-body-secondary small mb-1">Titular</div>
                    <div class="fw-semibold">{{ $holderName }}</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="text-body-secondary small mb-1">Facturación</div>
                    <div class="fw-semibold">{{ $customer->name }}</div>
                    <div class="text-body-secondary small">NIT {{ $customer->document_number }}{{ $customer->document_complement ? '-' . $customer->document_complement : '' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <x-ui.table-card title="Detalle de mensualidades">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Periodo</th><th>Concepto</th><th>Vencimiento</th><th class="text-end">Importe</th><th class="text-end">Pagado</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($contract->charges as $charge)
                            <tr>
                                <td>{{ $charge->period->format('m/Y') }}</td>
                                <td>{{ $charge->concept ?? 'Mensualidad' }}</td>
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

        <div class="col-lg-5">
            <div class="card h-100 border-success">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-cash me-2 text-success" aria-hidden="true"></i>Registrar pago</h3></div>
                <div class="card-body">
                    <div class="d-flex align-items-end justify-content-between gap-3 bg-danger-lt rounded p-3 mb-3">
                        <div class="text-body-secondary fw-semibold">Saldo pendiente</div>
                        <div class="h1 text-danger mb-0">Bs {{ number_format($balance, 2, ',', '.') }}</div>
                    </div>
                    @if (! $activeCashRegister)
                        <div class="alert alert-warning">Debes abrir tu caja antes de registrar un pago.</div>
                        <a class="btn btn-primary w-100" href="{{ route('cash-registers.index') }}">Ir a cajas</a>
                    @elseif ($paymentMethods->isEmpty())
                        <div class="alert alert-warning mb-0">No hay métodos de pago activos. Sincroniza el catálogo “Tipos método pago” del SIN antes de cobrar.</div>
                    @elseif ($balance > 0)
                        <form method="POST" action="{{ route('rectorate.contracts.payments.store', $contract) }}" data-disable-on-submit data-submitting-label="Registrando…">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required" for="amount">Monto a pagar</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Bs</span>
                                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" min="0.01" max="{{ number_format($balance, 2, '.', '') }}" step="0.01" inputmode="decimal" value="{{ old('amount', number_format($balance, 2, '.', '')) }}" required autofocus>
                                </div>
                                @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label required" for="payment_method_code">Método de pago</label>
                                <select class="form-select @error('payment_method_code') is-invalid @enderror" id="payment_method_code" name="payment_method_code" data-tom-select data-allow-empty-option="false" data-placeholder="Buscar método de pago" required>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->classifier_code }}" @selected(old('payment_method_code', 1) == $method->classifier_code)>{{ $method->classifier_code }} · {{ $method->description }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-success btn-lg w-100" type="submit"><i class="ti ti-check me-1" aria-hidden="true"></i><span>Registrar pago</span></button>
                        </form>
                    @else
                        <div class="alert alert-success mb-0"><i class="ti ti-circle-check me-1" aria-hidden="true"></i>Esta cuenta no tiene saldo pendiente.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-ui.table-card title="Historial de pagos">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Fecha y hora</th><th>Comprobante</th><th>Caja</th><th>Método de pago</th><th class="text-end">Monto pagado</th></tr></thead>
            <tbody>
                @forelse ($contract->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                        <td><span class="fw-semibold">PAGO-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</span></td>
                        <td>#{{ $payment->cash_register_id }}</td>
                        <td>{{ $payment->payment_method_code }} · {{ $paymentMethodLabels->get((string) $payment->payment_method_code, 'Método SIN') }}</td>
                        <td class="text-end fw-semibold text-success">Bs {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="5" message="Todavía no existen pagos registrados para este contrato." />
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
@endsection
