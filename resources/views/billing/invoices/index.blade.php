@extends('layouts.admin')

@section('title', 'Facturas | '.config('app.name', 'Base Admin'))
@section('page-title', 'Facturas')
@section('page-subtitle', 'Facturas emitidas y respuestas del SIN')

@section('content')
    <x-ui.card title="Filtros" class="mb-3">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('billing.invoices.index') }}">
            <div class="col-lg-5">
                <label class="form-label" for="invoice-search">Buscar</label>
                <input
                    class="form-control"
                    id="invoice-search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Numero, cliente, documento, CUF o recepcion"
                >
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="invoice-status">Estado</label>
                <select class="form-select" id="invoice-status" name="status">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="ti ti-search me-1" aria-hidden="true"></i>Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('billing.invoices.index') }}">
                    <i class="ti ti-x me-1" aria-hidden="true"></i>Limpiar
                </a>
                @can('invoices.issue')
                    <a class="btn btn-success ms-lg-auto" href="{{ route('billing.invoices.issue.index') }}">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>Emitir
                    </a>
                @endcan
            </div>
        </form>
    </x-ui.card>

    <x-ui.table-card title="Facturas emitidas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th class="text-end">Total</th>
                    <th>Estado SIN</th>
                    <th>Recepcion</th>
                    <th>CUF</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td>
                            @if ($invoice->invoice_number)
                                <div class="fw-semibold">Nro. {{ $invoice->invoice_number }}</div>
                            @else
                                <div class="fw-semibold text-body-secondary">Intento nro. {{ $invoice->attempted_invoice_number ?? '-' }}</div>
                                <div class="text-danger small">No validada</div>
                            @endif
                            <div class="text-body-secondary small">
                                Suc. {{ $invoice->branch_code }} / PV {{ $invoice->point_of_sale_code }}
                            </div>
                        </td>
                        <td>{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            <div>{{ $invoice->customer?->name ?? '-' }}</div>
                            <div class="text-body-secondary small">
                                {{ $invoice->customer?->document_number ?? 'Sin documento' }}
                            </div>
                        </td>
                        <td class="text-end">Bs {{ money_format_decimal($invoice->total_amount) }}</td>
                        <td>
                            @php
                                $statusTone = match ($invoice->status_code) {
                                    908 => 'bg-success-lt',
                                    904 => 'bg-yellow-lt',
                                    default => $invoice->transaccion ? 'bg-primary-lt' : 'bg-danger-lt',
                                };
                            @endphp
                            <span class="badge {{ $statusTone }}">{{ $invoice->status_label }}</span>
                            @if ($invoice->status_code)
                                <div class="text-body-secondary small">Codigo {{ $invoice->status_code }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $invoice->reception_code ?? '-' }}</div>
                            <div class="text-body-secondary small">{{ $invoice->message ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="authorization-secret">{{ str($invoice->cuf)->limit(22) }}</span>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="No hay facturas emitidas." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $invoices->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
