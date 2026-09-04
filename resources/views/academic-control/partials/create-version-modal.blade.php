<div class="modal modal-blur fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('academic.control.versions.store', $program) }}">
                @csrf
                <div class="modal-header">
                    <div><div class="text-secondary small">Programa destino: {{ $program->title }}</div><h2 class="modal-title" id="{{ $modalId }}-title">Crear ponderaciones</h2></div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="{{ $modalId }}-source">Configuración de origen</label>
                    <select class="form-select" id="{{ $modalId }}-source" name="source_scheme_id">
                        <option value="">Crear desde cero</option>
                        @foreach($availableSources as $sourceVersion)
                            <option value="{{ $sourceVersion->id }}">{{ $sourceVersion->program->title }} · Versión {{ $sourceVersion->version }}{{ $sourceVersion->is_active ? ' · Vigente' : '' }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Puedes copiar la nota mínima, ponderaciones, métodos y habilidades desde cualquier programa de esta empresa.</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-copy-plus me-1" aria-hidden="true"></i>Confirmar y crear borrador</button>
                </div>
            </form>
        </div>
    </div>
</div>
