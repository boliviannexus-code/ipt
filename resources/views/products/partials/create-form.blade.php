<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" data-ajax-form data-refresh-url="{{ route('products.index') }}" autocomplete="off" novalidate>
    @csrf

    @include('products.partials.fields', ['product' => null])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>
            Crear producto
        </button>
    </div>
</form>
