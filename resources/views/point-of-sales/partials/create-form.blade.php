<form method="POST" action="{{ route('point-of-sales.store') }}" data-ajax-form data-refresh-url="{{ route('point-of-sales.index') }}" novalidate>
    @csrf
    @include('point-of-sales.partials.fields', ['pointOfSale' => null])
    <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm d-none" data-submit-spinner></span>
            Guardar
        </button>
    </div>
</form>
