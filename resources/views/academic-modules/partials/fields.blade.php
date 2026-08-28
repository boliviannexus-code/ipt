<div class="row g-3">
    <div class="col-md-6"><label class="form-label required" for="module-program">Programa</label><select class="form-select @error('program_id') is-invalid @enderror" id="module-program" name="program_id" required><option value="">Seleccionar</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((int)old('program_id', $module?->program_id) === $program->id)>{{ $program->title }}</option>@endforeach</select>@error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6">
        <label class="form-label required" for="module-level">Nivel</label>
        <select class="form-select @error('program_level_id') is-invalid @enderror" id="module-level" name="program_level_id" required>
            <option value="">Seleccionar</option>
            @foreach($programs as $program)
                @foreach($program->levels as $level)
                    <option value="{{ $level->id }}" data-program-id="{{ $program->id }}" @selected((int) old('program_level_id', $module?->program_level_id) === $level->id)>{{ $level->name }}</option>
                @endforeach
            @endforeach
        </select>
        @error('program_level_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12"><label class="form-label required" for="module-name">Nombre</label><input class="form-control @error('name') is-invalid @enderror" id="module-name" name="name" maxlength="160" value="{{ old('name', $module?->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label required" for="module-modality">Modalidad</label><select class="form-select" id="module-modality" name="modality" required><option value="presential" @selected(old('modality', $module?->modality) === 'presential')>Presencial</option><option value="virtual" @selected(old('modality', $module?->modality) === 'virtual')>Virtual</option></select></div>
    <div class="col-md-4"><label class="form-label required" for="module-start">Hora inicio</label><input class="form-control @error('starts_at') is-invalid @enderror" id="module-start" name="starts_at" type="time" value="{{ old('starts_at', $module ? substr($module->starts_at, 0, 5) : '') }}" required>@error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label required" for="module-end">Hora fin</label><input class="form-control @error('ends_at') is-invalid @enderror" id="module-end" name="ends_at" type="time" value="{{ old('ends_at', $module ? substr($module->ends_at, 0, 5) : '') }}" required>@error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label required" for="module-start-date">Fecha inicio</label><input class="form-control @error('start_date') is-invalid @enderror" id="module-start-date" name="start_date" type="date" value="{{ old('start_date', $module?->start_date?->toDateString()) }}" required>@error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label required" for="module-end-date">Fecha fin</label><input class="form-control @error('end_date') is-invalid @enderror" id="module-end-date" name="end_date" type="date" value="{{ old('end_date', $module?->end_date?->toDateString()) }}" required>@error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div>
