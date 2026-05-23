<form method="POST" action="{{ route('branches.update', $branch) }}" data-ajax-form data-refresh-url="{{ route('branches.index') }}" novalidate>
    @csrf
    @method('PUT')
    @include('branches.partials.fields')
    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Guardar cambios</button>
    </div>
</form>
