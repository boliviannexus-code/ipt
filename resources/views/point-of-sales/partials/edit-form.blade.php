<form method="POST" action="{{ route('point-of-sales.update', $pointOfSale) }}" data-ajax-form data-refresh-url="{{ route('point-of-sales.index') }}" novalidate>
    @csrf
    @method('PUT')
    @include('point-of-sales.partials.fields')
    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm d-none" data-submit-spinner></span>
            Actualizar
        </button>
    </div>
</form>
