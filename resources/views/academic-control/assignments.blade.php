@extends('layouts.admin')
@section('title', 'Ponderaciones de '.$program->title.' | '.config('app.name'))
@section('page-title', 'Asignación de ponderaciones')
@section('page-subtitle', $program->title)
@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ route('academic.control.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a programas</a>
    <a class="btn btn-outline-primary" href="{{ route('academic.weightings.index') }}"><i class="ti ti-scale me-1"></i>Ver ponderaciones</a>
</div>
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="alert alert-info">Selecciona una ponderación activa para cada nivel de este programa. Puedes usar la misma en varios niveles. Las notas registradas conservan su configuración original.</div>
        <form method="POST" action="{{ route('academic.control.levels.update', $program) }}" class="mb-3">
            @csrf @method('PUT')
            <x-ui.table-card :title="$program->title">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Nivel</th><th>Ponderación vinculada</th></tr></thead>
                    <tbody>
                    @forelse($program->levels as $level)
                        <tr>
                            <td><label class="form-label mb-0" for="level-scheme-{{ $level->id }}">{{ $level->name }} @unless($level->is_active)<span class="text-secondary">(inactivo)</span>@endunless</label></td>
                            <td>
                                <input type="hidden" name="levels[{{ $loop->index }}][id]" value="{{ $level->id }}">
                                <select class="form-select" id="level-scheme-{{ $level->id }}" name="levels[{{ $loop->index }}][scheme_id]" @disabled(!auth()->user()->can('academic-control.manage'))>
                                    <option value="">Sin ponderación</option>
                                    @foreach($schemes->where('is_active', true)->where('status', 'finalized') as $scheme)
                                        <option value="{{ $scheme->id }}" @selected($level->program_grading_scheme_id === $scheme->id)>{{ $scheme->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="2" message="Crea niveles para vincular sus ponderaciones." />
                    @endforelse
                    </tbody>
                </table>
                @can('academic-control.manage')
                    @if($program->levels->isNotEmpty())
                        <x-slot:footer><button class="btn btn-primary" type="submit">Guardar asignaciones</button></x-slot:footer>
                    @endif
                @endcan
            </x-ui.table-card>
        </form>
@endsection
