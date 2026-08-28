<div class="row g-3">
 @if(\App\Support\CompanyContext::isGlobalAdmin(auth()->user()) && !isset($area))<div class="col-md-6"><label class="form-label">Empresa</label><select class="form-select" name="company_id" required><option value="">Seleccione</option>@foreach(\App\Models\Company::where('is_active',true)->orderBy('name')->get() as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></div>@endif
 <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="name" value="{{ old('name',$area->name ?? '') }}" required>@error('name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
 <div class="col-12"><input type="hidden" name="is_active" value="0"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$area->is_active ?? true))><span class="form-check-label">Área activa</span></label></div>
</div>
