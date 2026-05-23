<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="permission-name">Nombre</label>
        <input class="form-control {{ ($errors ?? null)?->has('name') ? 'is-invalid' : '' }}" id="permission-name" name="name" value="{{ old('name', $permission->name ?? '') }}" required>
        <div class="invalid-feedback" data-error-for="name">{{ ($errors ?? null)?->first('name') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="permission-guard-name">Guard</label>
        <input class="form-control {{ ($errors ?? null)?->has('guard_name') ? 'is-invalid' : '' }}" id="permission-guard-name" name="guard_name" value="{{ old('guard_name', $permission->guard_name ?? 'web') }}">
        <div class="invalid-feedback" data-error-for="guard_name">{{ ($errors ?? null)?->first('guard_name') }}</div>
    </div>
</div>
