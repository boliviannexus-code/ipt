<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="modal-warehouse-branch">Sucursal</label>
        <select class="form-select" id="modal-warehouse-branch" name="branch_id" required>
            <option value="">Selecciona una sucursal</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $warehouse->branch_id ?? 0) === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="modal-warehouse-name">Nombre</label>
        <input class="form-control" id="modal-warehouse-name" name="name" value="{{ old('name', $warehouse->name ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>
    <div class="col-md-2">
        <label class="form-label" for="modal-warehouse-code">Codigo</label>
        <input class="form-control" id="modal-warehouse-code" name="code" value="{{ old('code', $warehouse->code ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="code"></div>
    </div>
</div>

<input type="hidden" name="is_active" value="0">
<div class="form-check form-switch my-4">
    <input class="form-check-input" id="modal-warehouse-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $warehouse->is_active ?? true))>
    <label class="form-check-label" for="modal-warehouse-is-active">Activo</label>
    <div class="invalid-feedback d-block" data-error-for="is_active"></div>
</div>
