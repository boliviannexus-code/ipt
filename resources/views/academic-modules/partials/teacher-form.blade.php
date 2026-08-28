<form method="POST" action="{{ route('academic.modules.teacher.update', $module) }}" data-ajax-form data-refresh-url="{{ route('academic.modules.index') }}" novalidate>
    @csrf @method('PUT')
    @include('academic-modules.partials.teacher-fields')
    <div class="d-flex justify-content-end gap-2 mt-4"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit" @disabled($personnel->isEmpty())><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Asignar docente</button></div>
</form>
