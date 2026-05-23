<form method="POST" action="{{ route('payment-methods.store') }}" data-ajax-form data-refresh-url="{{ route('payment-methods.index') }}" autocomplete="off" novalidate>
    @csrf

    @include('payment-methods.partials.fields', ['paymentMethod' => null])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>
            Crear metodo
        </button>
    </div>
</form>
