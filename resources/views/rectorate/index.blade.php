@extends('layouts.admin')

@section('title', 'Inscripciones')
@section('page-title', 'Inscripciones')
@section('page-subtitle', 'Titulares y avance de sus inscripciones en la empresa activa')

@section('content')
    <x-ui.table-card title="Listado de inscripciones">
        <x-slot:actions>
            <a class="btn btn-primary btn-sm" href="{{ route('rectorate.new') }}">
                <i class="ti ti-plus me-1"></i>Nueva inscripción
            </a>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Matrícula / Sede</th><th>Titular</th><th>CI</th><th>Contacto</th><th>Programa / Plan</th><th>Estado</th><th>Registrado</th><th class="text-end">Acción</th></tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        @php
                            $step = max(1, min(4, $application->current_step));
                            $stepLabel = match ($step) { 1 => 'Titular', 2 => 'Programa', 3 => 'Estudiante', default => 'Confirmación' };
                            $continueRoute = match ($step) {
                                1, 2 => route('rectorate.applications.plan.edit', $application),
                                3 => route('rectorate.applications.student.edit', $application),
                                default => route('rectorate.applications.confirmation.show', $application),
                            };
                            $continueLabel = match (true) {
                                $application->status === 'completed' => 'Ver resumen',
                                $step === 2 => 'Continuar programa',
                                $step === 3 => 'Continuar estudiante',
                                $step === 4 => 'Confirmar',
                                default => 'Continuar',
                            };
                        @endphp
                        <tr>
                            <td><span class="fw-semibold d-block">{{ $application->account_number }}</span><small class="text-body-secondary">{{ $application->campus?->name }}</small></td>
                            <td><span class="fw-semibold d-block">{{ $application->first_name }} {{ $application->paternal_surname }} {{ $application->maternal_surname }}</span><small class="text-body-secondary">Inscripción #{{ $application->id }}</small></td>
                            <td>{{ $application->identity_document }}</td>
                            <td><span class="d-block">{{ $application->email }}</span><small class="text-body-secondary">{{ $application->phone }}</small></td>
                            <td><span class="d-block">{{ $application->program?->title ?? 'Pendiente' }}</span>@if($application->plan)<small class="text-body-secondary">{{ $application->plan->name }}</small>@endif</td>
                            <td>@if ($application->status === 'completed')<span class="badge text-bg-success">Completada</span>@else<span class="badge text-bg-azure">Paso {{ $step }} de 4 · {{ $stepLabel }}</span>@endif</td>
                            <td>{{ $application->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-outline-primary btn-sm" href="{{ $continueRoute }}"><i class="ti ti-player-play me-1"></i>{{ $continueLabel }}</a>
                                    @if ($application->contract)
                                        <a class="btn btn-outline-dark btn-sm" href="{{ route('rectorate.contracts.print', $application->contract) }}" target="_blank" rel="noopener" title="Imprimir contrato"><i class="ti ti-printer me-1"></i>Contrato</a>
                                        <a class="btn btn-outline-success btn-sm" href="{{ route('rectorate.contracts.account.show', $application->contract) }}"><i class="ti ti-cash me-1"></i>Estado de cuenta</a>
                                    @endif
                                    @can('rectorate.delete')
                                        @if ($application->status === 'completed')
                                            <button class="btn btn-outline-secondary btn-sm" type="button" disabled title="Las inscripciones aprobadas no pueden eliminarse" aria-label="Eliminación no disponible"><i class="ti ti-lock"></i></button>
                                        @else
                                            <form method="POST" action="{{ route('rectorate.applications.destroy', $application) }}" data-confirm-delete="¿Eliminar la inscripción #{{ $application->id }} de {{ $application->first_name }} {{ $application->paternal_surname }}? El cliente y el estudiante se conservarán.">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit" title="Eliminar inscripción" aria-label="Eliminar inscripción"><i class="ti ti-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="8" message="No existen inscripciones registradas." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
