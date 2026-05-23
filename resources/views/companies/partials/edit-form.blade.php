<form method="POST" action="{{ route('companies.update', $company) }}" enctype="multipart/form-data" data-ajax-form data-refresh-url="{{ route('companies.index') }}" novalidate>
    @csrf
    @method('PUT')
    @include('companies.partials.fields', compact('company'))
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Guardar cambios</button>
    </div>
</form>
