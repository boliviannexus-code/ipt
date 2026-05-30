<form method="POST" action="{{ route('admin.accommodation-catalogs.update', [$catalog, $record->id]) }}" data-ajax-form data-refresh-url="{{ route('admin.accommodation-catalogs.index', $catalog) }}" novalidate>
    @csrf
    @method('PUT')
    @include('admin.accommodation-catalogs.partials.fields', compact('record'))
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit"><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Guardar cambios</button>
    </div>
</form>
