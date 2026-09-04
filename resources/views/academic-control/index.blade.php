@extends('layouts.admin')

@section('title', 'Control académico | '.config('app.name'))
@section('page-title', 'Control académico')
@section('page-subtitle', 'Selecciona un programa para definir cómo se evaluará a sus estudiantes')

@section('content')
    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
        <i class="ti ti-info-circle fs-3" aria-hidden="true"></i>
        <div>
            <strong>Configuración independiente por programa</strong>
            <div class="text-secondary">Cada programa tendrá sus propias ponderaciones y criterios de aprobación.</div>
        </div>
    </div>

    <x-ui.table-card title="Programas disponibles">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Programa</th>
                    <th>Código</th>
                    <th class="text-center">Niveles</th>
                    <th class="text-center">Módulos</th>
                    <th>Configuración</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td class="fw-semibold">{{ $program->title }}</td>
                        <td><span class="badge text-bg-secondary">{{ $program->enrollment_code ?: 'Pendiente' }}</span></td>
                        <td class="text-center">{{ $program->levels_count }}</td>
                        <td class="text-center">{{ $program->academic_modules_count }}</td>
                        <td>@if ($program->gradingScheme?->finalized_at)<span class="badge bg-green-lt text-green"><i class="ti ti-lock me-1"></i>Finalizada</span>@elseif($program->gradingScheme)<span class="badge bg-yellow-lt text-yellow">En configuración</span>@else<span class="badge bg-secondary-lt text-secondary">Sin ponderaciones</span>@endif</td>
                        <td class="text-end">
                            @if($program->gradingScheme)
                                <a class="btn btn-primary btn-sm" href="{{ route('academic.control.show', $program) }}" aria-label="Iniciar configuración de {{ $program->title }}"><i class="ti ti-adjustments-horizontal me-1" aria-hidden="true"></i>{{ $program->gradingScheme->finalized_at ? 'Ver configuración' : 'Configurar' }}</a>
                            @else
                                @can('academic-control.manage')
                                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#createGradingModal{{ $program->id }}"><i class="ti ti-copy-plus me-1" aria-hidden="true"></i>Crear ponderaciones</button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="No hay programas registrados para la empresa activa." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $programs->links() }}</x-slot:footer>
    </x-ui.table-card>

    @can('academic-control.manage')
        @foreach($programs->getCollection()->filter(fn ($program) => $program->gradingScheme === null) as $program)
            @include('academic-control.partials.create-version-modal', ['modalId' => 'createGradingModal'.$program->id])
        @endforeach
    @endcan
@endsection
