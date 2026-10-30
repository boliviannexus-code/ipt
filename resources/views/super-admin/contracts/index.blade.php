@extends('layouts.admin')
@section('title', 'Eliminación de contratos')
@section('page-title', 'Eliminación de contratos')
@section('page-subtitle', 'Administración exclusiva de super administrador')
@section('content')
    <div class="alert alert-warning">La eliminación es definitiva e incluye la inscripción, las cuotas, el historial de planes y los resultados vinculados a esas cuotas. Se conserva al estudiante y sus otros contratos. Solo se permite eliminar contratos sin ningún cobro registrado.</div>
    <x-ui.table-card title="Contratos de la empresa activa">
        <x-slot:actions>
            <form class="d-flex flex-wrap gap-2" method="GET" action="{{ route('super-admin.contracts.index') }}">
                <input class="form-control form-control-sm" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Matrícula, contrato, estudiante o CI" aria-label="Buscar contratos">
                <button class="btn btn-outline-primary btn-sm" type="submit">Buscar</button>
            </form>
        </x-slot:actions>
        <table class="table table-hover align-middle">
            <thead><tr><th>Contrato / Matrícula</th><th>Estudiante</th><th>Programa</th><th>Sede</th><th>Cobros</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
                @forelse ($contracts as $contract)
                    @php($hasCollections = $contract->payments_exists || $contract->credit_applications_exists || $contract->has_paid_charges)
                    <tr>
                        <td><strong>#{{ $contract->contract_number }}</strong><small class="d-block text-secondary">{{ $contract->account_number ?: 'Sin matrícula' }}</small></td>
                        <td>{{ trim($contract->student?->first_name.' '.$contract->student?->paternal_surname.' '.$contract->student?->maternal_surname) }}<small class="d-block text-secondary">{{ $contract->student?->identity_document }}</small></td>
                        <td>{{ $contract->program?->title }}</td>
                        <td>{{ $contract->campus?->short ?: '—' }}</td>
                        <td><span class="badge {{ $hasCollections ? 'text-bg-secondary' : 'text-bg-success' }}">{{ $hasCollections ? 'Con cobros' : 'Sin cobros' }}</span></td>
                        <td class="text-end">
                            @if ($hasCollections)
                                <span class="text-secondary small">Eliminación bloqueada</span>
                            @else
                                <form method="POST" action="{{ route('super-admin.contracts.destroy', $contract) }}" data-confirm-delete="¿Eliminar definitivamente el contrato #{{ $contract->contract_number }}, su inscripción y todas sus dependencias? Esta acción no se puede deshacer.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="ti ti-trash me-1" aria-hidden="true"></i>Eliminar contrato</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="No se encontraron contratos." />
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $contracts->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
