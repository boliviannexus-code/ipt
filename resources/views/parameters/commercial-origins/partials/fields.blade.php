<div>
    <label class="form-label required" for="commercial-origin-name">Nombre</label>
    <input class="form-control @error('name') is-invalid @enderror" id="commercial-origin-name" name="name" value="{{ old('name', $commercialOrigin->name ?? '') }}" maxlength="150" autocomplete="off" required autofocus>
    <div class="invalid-feedback" data-error-for="name">{{ $errors->first('name') }}</div>
</div>
