@include('point-of-sales.partials.fields')

<div class="d-flex justify-content-end gap-2">
    <a class="btn btn-outline-secondary" href="{{ route('point-of-sales.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Guardar</button>
</div>
