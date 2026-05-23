<form method="POST" action="{{ route('measurement-units.store') }}" data-ajax-form data-refresh-url="{{ route('measurement-units.index') }}" autocomplete="off" novalidate>
    @csrf

    @include('measurement-units.partials.fields', ['measurementUnit' => null])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner aria-hidden="true"></span>
            Crear unidad
        </button>
    </div>
</form>
