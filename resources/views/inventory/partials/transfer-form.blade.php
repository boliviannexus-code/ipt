<form method="POST" action="{{ route('inventory.transfers.store') }}" data-ajax-form data-transfer-form data-transfer-stock='@json($stockAvailability)' data-refresh-url="{{ route('inventory.index') }}" autocomplete="off" novalidate>
    @csrf

    <input type="hidden" name="operation" value="transfer">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="transfer-source-warehouse">Almacen origen</label>
            <select class="form-select" id="transfer-source-warehouse" name="source_warehouse_id" data-transfer-source data-tom-select data-placeholder="Seleccionar origen" required>
                <option value="">Seleccionar origen</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="source_warehouse_id"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transfer-target-warehouse">Almacen destino</label>
            <select class="form-select" id="transfer-target-warehouse" name="target_warehouse_id" data-transfer-target data-tom-select data-placeholder="Seleccionar destino" required>
                <option value="">Seleccionar destino</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="target_warehouse_id"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transfer-product">Producto</label>
            <select class="form-select" id="transfer-product" name="items[0][product_id]" data-transfer-product data-tom-select data-placeholder="Seleccionar producto" required>
                <option value="">Seleccionar producto</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-name="{{ $product->name }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="items.0.product_id"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transfer-presentation">Presentacion</label>
            <select class="form-select" id="transfer-presentation" name="items[0][presentation_id]" data-transfer-presentation data-tom-select data-placeholder="Unidad base">
                <option value="">Unidad base</option>
                @foreach ($presentations as $presentation)
                    <option value="{{ $presentation->id }}" data-name="{{ $presentation->name }}" data-units="{{ $presentation->units_per_package }}">{{ $presentation->name }} ({{ $presentation->units_per_package }} u.)</option>
                @endforeach
            </select>
            <div class="form-text">Selecciona una presentacion con stock o deja Unidad base para unidades sueltas.</div>
            <div class="invalid-feedback" data-error-for="items.0.presentation_id"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transfer-quantity">Cantidad de unidades</label>
            <input class="form-control" id="transfer-quantity" name="items[0][quantity]" type="number" min="1" value="1" autocomplete="off" data-transfer-units>
            <div class="form-text" data-transfer-units-help>Usa este campo cuando no selecciones una presentacion.</div>
            <div class="invalid-feedback" data-error-for="items.0.quantity"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="transfer-package-quantity">Cantidad de presentaciones</label>
            <input class="form-control" id="transfer-package-quantity" name="items[0][package_quantity]" type="number" min="1" autocomplete="off" data-transfer-packages disabled>
            <div class="form-text" data-transfer-packages-help>Se habilita cuando selecciones caja, paquete u otra presentacion.</div>
            <div class="invalid-feedback" data-error-for="items.0.package_quantity"></div>
        </div>

        <div class="col-12">
            <div class="alert alert-info mb-0 py-2" data-transfer-summary>
                Selecciona almacen origen y producto para ver existencias disponibles.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transfer-notes">Notas</label>
            <textarea class="form-control" id="transfer-notes" name="notes" rows="2" placeholder="Motivo o detalle de la transferencia"></textarea>
            <div class="invalid-feedback" data-error-for="notes"></div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit" data-transfer-submit disabled>
            <span class="spinner-border spinner-border-sm d-none me-1" data-submit-spinner aria-hidden="true"></span>
            Registrar transferencia
        </button>
    </div>
</form>
