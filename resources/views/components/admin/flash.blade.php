@if (session('success'))
    <div class="alert alert-success d-none" data-swal-success="{{ session('success') }}"></div>
@endif

@if (($errors ?? null)?->any())
    <div class="alert alert-danger d-none" data-swal-error="Revisa los datos ingresados."></div>
@endif
