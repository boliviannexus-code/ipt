<form method="POST" action="{{ route('warehouses.store') }}" data-ajax-form data-refresh-url="{{ route('warehouses.index') }}" novalidate>
    @csrf
    @include('warehouses.partials.fields', ['warehouse' => null])
    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Crear almacen</button>
    </div>
</form>
