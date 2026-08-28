@extends('layouts.admin')

@section('title', 'Cuentas por cobrar')
@section('page-title', 'Cuentas por cobrar')
@section('page-subtitle', 'Selecciona una cuenta para registrar el cobro')

@section('content')
    <div class="card mb-3 border-success">
        <div class="card-body py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-success-lt text-success" aria-hidden="true"><i class="ti ti-lock-open"></i></span>
                    <div><div class="fw-semibold">Caja #{{ $activeCashRegister->id }} abierta</div><div class="text-body-secondary small">Desde {{ $activeCashRegister->opened_at->format('d/m/Y H:i') }} · {{ auth()->user()->name }}</div></div>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('cash-registers.index') }}"><i class="ti ti-building-bank me-1" aria-hidden="true"></i>Administrar caja</a>
            </div>
        </div>
    </div>

    <x-ui.table-card title="Cuentas disponibles para cobro">
        <x-slot:actions>
            <form class="d-flex gap-2" method="GET" action="{{ route('rectorate.collectible-accounts.index') }}" role="search">
                <label class="visually-hidden" for="account-search">Buscar estudiante, CI, cuenta o contrato</label>
                <input class="form-control" id="account-search" name="buscar" type="search" value="{{ $search }}" placeholder="Estudiante, CI, cuenta o contrato" autocomplete="off">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search me-1" aria-hidden="true"></i>Buscar</button>
            </form>
        </x-slot:actions>

        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Contrato</th><th>Estudiante</th><th>Programa / Plan</th><th>Estado</th><th class="text-end">Saldo</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @forelse ($contracts as $contract)
                    @php($balance = (float) $contract->total_charged - (float) $contract->total_paid)
                    <tr>
                        <td><span class="fw-semibold">{{ $contract->application->account_number }}</span><small class="d-block text-body-secondary">{{ $contract->application->campus?->name }} · Contrato #{{ $contract->contract_number }}</small></td>
                        <td><span class="fw-semibold d-block">{{ $contract->student->first_name }} {{ $contract->student->paternal_surname }} {{ $contract->student->maternal_surname }}</span><small class="text-body-secondary">CI {{ $contract->student->identity_document }}</small></td>
                        <td>{{ $contract->program->title }}<small class="d-block text-body-secondary">{{ $contract->plan->name }}</small></td>
                        <td><span class="badge {{ $contract->status === 'enrolled' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $contract->status === 'enrolled' ? 'Inscrito' : 'Preinscrito' }}</span></td>
                        <td class="text-end"><strong class="text-danger">Bs {{ number_format($balance, 2, ',', '.') }}</strong></td>
                        <td class="text-end"><a class="btn btn-success" href="{{ route('rectorate.contracts.account.show', $contract) }}"><i class="ti ti-cash me-1" aria-hidden="true"></i>Cobrar</a></td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="No existen cuentas con saldo pendiente para cobrar." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $contracts->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
