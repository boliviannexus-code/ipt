<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Nombre</label>
        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $room->name ?? $room->title ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="name">{{ $errors->first('name') }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Tipo de baño</label>
        <select class="form-select @error('bathroom_type_id') is-invalid @enderror" name="bathroom_type_id" required>
            <option value="">Seleccionar</option>
            @foreach ($bathroomTypes as $type)
                <option value="{{ $type->id }}" @selected((int) old('bathroom_type_id', $room->bathroom_type_id ?? 0) === $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="bathroom_type_id">{{ $errors->first('bathroom_type_id') }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Estado</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
            @foreach (['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo'] as $status => $label)
                <option value="{{ $status }}" @selected(old('status', $room->status ?? 'active') === $status)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="status">{{ $errors->first('status') }}</div>
    </div>
</div>
