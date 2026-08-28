@extends('layouts.admin')

@section('title', 'Niveles de ' . $program->title)
@section('page-title', 'Niveles del programa')
@section('page-subtitle', $program->title . ' · ' . $program->duration_months . ' meses')

@section('content')
    <div class="mb-3"><a class="btn btn-outline-secondary" href="{{ route('parameters.programs.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a programas</a></div>
    <div class="row g-3">
        <div class="col-lg-4">
            <x-ui.card title="Agregar nivel">
                <div class="card-body">
                    <form method="POST" action="{{ route('parameters.programs.levels.store', $program) }}">
                        @csrf
                        <div class="mb-3"><label class="form-label required" for="level-name">Nombre</label><input class="form-control @error('name') is-invalid @enderror" id="level-name" name="name" maxlength="120" value="{{ old('name') }}" placeholder="Ej.: Básico 1" required autofocus>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="mb-3"><label class="form-label" for="level-position">Posición</label><input class="form-control @error('position') is-invalid @enderror" id="level-position" name="position" type="number" min="1" max="999" value="{{ old('position') }}" placeholder="Automática">@error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <button class="btn btn-primary w-100" type="submit"><i class="ti ti-plus me-1"></i>Agregar nivel</button>
                    </form>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-8">
            @foreach($levels as $level)
                <form id="update-level-{{ $level->id }}" method="POST" action="{{ route('parameters.programs.levels.update', [$program, $level]) }}">@csrf @method('PUT')</form>
                <form id="delete-level-{{ $level->id }}" method="POST" action="{{ route('parameters.programs.levels.destroy', [$program, $level]) }}" data-confirm-delete="¿Eliminar el nivel {{ $level->name }}?">@csrf @method('DELETE')</form>
            @endforeach
            <x-ui.table-card title="Niveles configurados">
                <table class="table align-middle mb-0"><thead><tr><th>Posición</th><th>Nombre</th><th>Activo</th><th class="text-end">Acciones</th></tr></thead><tbody>
                    @forelse($levels as $level)
                        <tr>
                            <td style="width: 7rem"><input class="form-control form-control-sm" form="update-level-{{ $level->id }}" name="position" type="number" min="1" max="999" value="{{ $level->position }}" aria-label="Posición de {{ $level->name }}" required></td>
                            <td><input class="form-control form-control-sm" form="update-level-{{ $level->id }}" name="name" maxlength="120" value="{{ $level->name }}" aria-label="Nombre del nivel" required></td>
                            <td><label class="form-check form-switch mb-0"><input form="update-level-{{ $level->id }}" type="hidden" name="is_active" value="0"><input class="form-check-input" form="update-level-{{ $level->id }}" name="is_active" type="checkbox" value="1" @checked($level->is_active) aria-label="Nivel activo"></label></td>
                            <td class="text-end"><div class="d-inline-flex gap-1"><button class="btn btn-outline-primary btn-sm" form="update-level-{{ $level->id }}" type="submit">Guardar</button><button class="btn btn-outline-danger btn-sm" form="delete-level-{{ $level->id }}" type="submit" aria-label="Eliminar {{ $level->name }}"><i class="ti ti-trash"></i></button></div></td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="4" message="Este programa todavía no tiene niveles configurados." />
                    @endforelse
                </tbody></table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
