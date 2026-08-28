@extends('layouts.admin')
@section('title', 'Mi cuenta | '.config('app.name'))
@section('page-title', 'Mi cuenta')
@section('page-subtitle', 'Datos de acceso y seguridad')
@section('content')
<div class="row g-4">
    <div class="col-lg-5"><x-ui.card title="Datos de la cuenta"><div class="card-body"><dl class="row mb-0"><dt class="col-sm-4">Personal</dt><dd class="col-sm-8">{{ $user->personnel?->full_name ?? $user->name }}</dd><dt class="col-sm-4">Usuario / correo</dt><dd class="col-sm-8">{{ $user->email }}</dd><dt class="col-sm-4">Empresa</dt><dd class="col-sm-8">{{ \App\Support\CompanyContext::activeCompany($user)?->name ?? '—' }}</dd></dl></div></x-ui.card></div>
    <div class="col-lg-7"><x-ui.card title="Cambiar contraseña"><form method="POST" action="{{ route('account.password.update') }}" class="card-body">@csrf @method('PUT')<div class="mb-3"><label class="form-label">Contraseña actual</label><input class="form-control @error('current_password') is-invalid @enderror" type="password" name="current_password" required>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="row g-3"><div class="col-md-6"><label class="form-label">Nueva contraseña</label><input class="form-control @error('password') is-invalid @enderror" type="password" name="password" minlength="8" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input class="form-control" type="password" name="password_confirmation" required></div></div><button class="btn btn-primary mt-4" type="submit"><i class="ti ti-lock me-1"></i>Actualizar contraseña</button></form></x-ui.card></div>
</div>
@endsection
