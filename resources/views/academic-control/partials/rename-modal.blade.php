<div class="modal modal-blur fade" id="renameSchemeModal{{ $scheme->id }}" tabindex="-1" aria-labelledby="renameSchemeTitle{{ $scheme->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('academic.weightings.rename', $scheme) }}">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h2 class="modal-title" id="renameSchemeTitle{{ $scheme->id }}">Cambiar nombre de la ponderación</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label class="form-label required" for="rename-scheme-name-{{ $scheme->id }}">Nombre</label>
                <input class="form-control" id="rename-scheme-name-{{ $scheme->id }}" name="name" maxlength="150" value="{{ $scheme->name }}" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Guardar nombre</button>
            </div>
        </form>
    </div>
</div>
