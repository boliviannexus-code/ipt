<form method="POST" action="{{ route('categories.store') }}" data-ajax-form data-refresh-url="{{ route('categories.index') }}" novalidate>
    @csrf

    @include('categories.partials.fields', ['category' => null])

    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>
            Crear categoria
        </button>
    </div>
</form>
