<form method="POST" action="{{ route('inventory.defragment.store') }}" data-ajax-form data-defragment-form data-refresh-url="{{ route('inventory.index') }}" autocomplete="off" novalidate>
    @csrf

    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">

    <div class="mb-3">
        <div class="small text-muted">Producto</div>
        <div class="fw-semibold">{{ $product->name }}</div>
    </div>

    <div class="mb-3">
        <div class="small text-muted">Almacen</div>
        <div class="fw-semibold">{{ $warehouse->name }}</div>
    </div>

    @if ($presentations->isEmpty())
        <div class="alert alert-warning mb-0">
            No hay cajas o empaques disponibles para desfragmentar en este almacen.
        </div>
    @else
        <div class="mb-3">
            <label class="form-label" for="defragment-presentation">Presentacion a separar</label>
            <select class="form-select" id="defragment-presentation" name="presentation_id" data-defragment-presentation required>
                @foreach ($presentations as $presentation)
                    <option value="{{ $presentation->presentation_id }}" data-max="{{ (int) $presentation->packages }}" data-units="{{ (int) $presentation->units_per_package }}">
                        {{ $presentation->presentation_name }} - {{ (int) $presentation->packages }} disp. ({{ (int) $presentation->units }} u.)
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="presentation_id"></div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="defragment-package-quantity">Cantidad de cajas/empaques</label>
            <input class="form-control" id="defragment-package-quantity" name="package_quantity" type="number" min="1" value="1" data-defragment-quantity required>
            <div class="invalid-feedback" data-error-for="package_quantity"></div>
            <div class="form-text" data-defragment-preview>Se descontara el empaque seleccionado y se ingresaran sus unidades equivalentes como Unidad.</div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="defragment-notes">Notas</label>
            <textarea class="form-control" id="defragment-notes" name="notes" rows="2" placeholder="Motivo o detalle de control"></textarea>
            <div class="invalid-feedback" data-error-for="notes"></div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" type="submit">
                <span class="spinner-border spinner-border-sm d-none me-1" data-submit-spinner aria-hidden="true"></span>
                Desfragmentar
            </button>
        </div>
    @endif
</form>
