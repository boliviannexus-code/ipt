<form method="POST" action="{{ route('inventory.adjustment.store') }}" data-ajax-form data-stock-adjustment-form data-refresh-url="{{ route('inventory.index') }}" autocomplete="off" novalidate>
    @csrf

    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">

    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">Producto</div>
            <div class="fw-semibold">{{ $product->name }}</div>
        </div>

        <div class="col-md-6">
            <div class="small text-muted">Almacen</div>
            <div class="fw-semibold">{{ $warehouse->name }}</div>
        </div>

        <div class="col-12">
            <label class="form-label" for="stock-adjustment-presentation">Tipo de stock</label>
            <select class="form-select" id="stock-adjustment-presentation" name="presentation_id" data-stock-adjustment-presentation>
                @php
                    $hasUnitPresentation = $presentations->contains(fn ($presentation): bool => (int) $presentation->units_per_package === 1);
                @endphp
                @if ($baseStock > 0 || ! $hasUnitPresentation)
                    <option value="" data-current="{{ $baseStock }}" data-units="1" data-label="Unidad base">
                        Unidad base sin presentacion - {{ $baseStock }} unidad(es) suelta(s)
                    </option>
                @endif
                @foreach ($presentations as $presentation)
                    <option value="{{ $presentation->presentation_id }}" data-current="{{ (int) $presentation->packages }}" data-units="{{ (int) $presentation->units_per_package }}" data-label="{{ $presentation->presentation_name }}">
                        {{ $presentation->presentation_name }} - {{ (int) $presentation->packages }} disp. ({{ (int) $presentation->units }} u.)
                    </option>
                @endforeach
            </select>
            <div class="form-text">Si existe la presentacion Unidad, se mostrara aqui como un tipo de stock independiente. El sistema generara solo la diferencia.</div>
            <div class="invalid-feedback" data-error-for="presentation_id"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="stock-adjustment-reason">Motivo</label>
            <select class="form-select" id="stock-adjustment-reason" name="reason" required>
                <option value="conteo_fisico">Conteo fisico</option>
                <option value="perdida">Perdida</option>
                <option value="robo">Robo</option>
                <option value="otros">Otros</option>
            </select>
            <div class="invalid-feedback" data-error-for="reason"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="stock-adjustment-current">Cantidad en sistema</label>
            <input class="form-control" id="stock-adjustment-current" type="number" value="{{ $baseStock }}" data-stock-adjustment-current readonly>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="stock-adjustment-counted">Cantidad contada</label>
            <input class="form-control" id="stock-adjustment-counted" name="counted_quantity" type="number" min="0" value="{{ $baseStock }}" data-stock-adjustment-counted required>
            <div class="invalid-feedback" data-error-for="counted_quantity"></div>
        </div>

        <div class="col-12">
            <div class="alert alert-info mb-0 py-2" data-stock-adjustment-preview>
                Sin diferencia: no se generara movimiento.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="stock-adjustment-notes">Notas</label>
            <textarea class="form-control" id="stock-adjustment-notes" name="notes" rows="2" placeholder="Motivo del conteo o reajuste"></textarea>
            <div class="invalid-feedback" data-error-for="notes"></div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm d-none me-1" data-submit-spinner aria-hidden="true"></span>
            Reajustar stock
        </button>
    </div>
</form>
