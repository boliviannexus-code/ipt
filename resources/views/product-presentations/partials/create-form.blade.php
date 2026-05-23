<form method="POST" action="{{ route('product-presentations.store') }}" data-ajax-form data-refresh-url="{{ route('product-presentations.index') }}" autocomplete="off" novalidate>
    @csrf
    @include('product-presentations.partials.fields', ['productPresentation' => null])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>Crear presentacion</button>
    </div>
</form>
