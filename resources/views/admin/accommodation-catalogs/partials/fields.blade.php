<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label" for="catalog-name">Nombre</label>
        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="catalog-name" name="name" value="{{ old('name', $record->name ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="name">{{ $errors->first('name') }}</div>
    </div>
    <div class="col-md-5">
        <label class="form-label" for="catalog-slug">Slug</label>
        <input class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}" id="catalog-slug" name="slug" value="{{ old('slug', $record->slug ?? '') }}">
        <div class="invalid-feedback" data-error-for="slug">{{ $errors->first('slug') }}</div>
    </div>

    @if ($metadata['has_capacity'])
        <div class="col-md-4">
            <label class="form-label" for="catalog-capacity">Capacidad</label>
            <input class="form-control {{ $errors->has('capacity') ? 'is-invalid' : '' }}" id="catalog-capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $record->capacity ?? 1) }}" required>
            <div class="invalid-feedback" data-error-for="capacity">{{ $errors->first('capacity') }}</div>
        </div>
    @endif

    <div class="col-md-{{ $metadata['has_capacity'] ? '4' : '6' }}">
        <label class="form-label" for="catalog-sort-order">Orden</label>
        <input class="form-control {{ $errors->has('sort_order') ? 'is-invalid' : '' }}" id="catalog-sort-order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $record->sort_order ?? '') }}">
        <div class="invalid-feedback" data-error-for="sort_order">{{ $errors->first('sort_order') }}</div>
    </div>
    <div class="col-md-{{ $metadata['has_capacity'] ? '4' : '6' }} d-flex align-items-end">
        <input type="hidden" name="is_active" value="0">
        <label class="form-check form-switch mb-2">
            <input class="form-check-input {{ $errors->has('is_active') ? 'is-invalid' : '' }}" name="is_active" type="checkbox" value="1" @checked(old('is_active', $record->is_active ?? true))>
            <span class="form-check-label">Activo</span>
        </label>
        <div class="invalid-feedback d-block" data-error-for="is_active">{{ $errors->first('is_active') }}</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="catalog-description">Descripcion</label>
        <textarea class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" id="catalog-description" name="description" rows="3">{{ old('description', $record->description ?? '') }}</textarea>
        <div class="invalid-feedback" data-error-for="description">{{ $errors->first('description') }}</div>
    </div>
</div>
