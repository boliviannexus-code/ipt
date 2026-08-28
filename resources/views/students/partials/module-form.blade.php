<form method="POST" action="{{ route('students.modules.store', $student) }}" data-ajax-form data-refresh-url="{{ route('students.index') }}" novalidate>
    @csrf
    @include('students.partials.module-fields')
    <div class="d-flex justify-content-end gap-2 mt-4"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit" @disabled($modules->isEmpty())><span class="spinner-border spinner-border-sm me-2 d-none" data-submit-spinner></span>Asignar módulo</button></div>
</form>
