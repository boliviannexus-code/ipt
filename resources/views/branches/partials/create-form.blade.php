<form method="POST" action="{{ route('branches.store') }}" data-ajax-form data-refresh-url="{{ route('branches.index') }}" novalidate>
    @csrf
    @include('branches.partials.fields', ['branch' => null])
    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Crear sucursal</button>
    </div>
</form>
