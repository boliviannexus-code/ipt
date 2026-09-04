@extends('layouts.admin')
@section('title', 'Ponderaciones de '.$program->title.' | '.config('app.name'))
@section('page-title', 'Ponderaciones de notas')
@section('page-subtitle', $program->title)

@section('content')
@php
    $isFinalized = $scheme->isFinalized();
    $hasDraft = $versions->contains('status', 'draft');
    $initialComponents = $scheme->components->map(fn ($component) => ['name' => $component->name, 'weight' => (float) $component->weight, 'frequency' => $component->frequency->value, 'skill_mode' => $component->skill_mode->value, 'scoring_method' => $component->scoring_method->value, 'skills' => $component->skills->map(fn ($skill) => ['name' => $skill->name, 'weight' => (float) $skill->weight])->values()->all()])->values()->all();
@endphp
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <a class="btn btn-outline-secondary align-self-start" href="{{ route('academic.control.index') }}"><i class="ti ti-arrow-left me-1"></i>Volver a programas</a>
    <div class="d-flex flex-wrap align-items-center gap-2"><label class="form-label mb-0" for="grading-version">Versión</label><select class="form-select w-auto" id="grading-version" data-version-select>@foreach($versions as $version)<option value="{{ route('academic.control.show', ['program' => $program, 'version' => $version->version]) }}" @selected($version->is($scheme))>Versión {{ $version->version }} · {{ $version->isFinalized() ? ($version->is_active ? 'Vigente' : 'Finalizada') : 'Borrador' }}</option>@endforeach</select><span class="badge {{ $isFinalized ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }} fs-6"><i class="ti {{ $isFinalized ? 'ti-lock' : 'ti-edit' }} me-1"></i>{{ $isFinalized ? 'Finalizada' : 'Borrador editable' }}</span></div>
</div>

@if($errors->any())<div class="alert alert-danger"><strong>No se pudo guardar.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($isFinalized)
    <div class="alert alert-success d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div><i class="ti ti-lock-check me-2" aria-hidden="true"></i>Esta versión está protegida y no puede modificarse.</div>
        @if(!$hasDraft)
            @can('academic-control.manage')
                <button class="btn btn-primary flex-shrink-0" type="button" data-bs-toggle="modal" data-bs-target="#createGradingVersionModal"><i class="ti ti-copy-plus me-1" aria-hidden="true"></i>Crear nueva versión</button>
            @endcan
        @endif
    </div>
@else
    <div class="alert alert-warning"><i class="ti ti-alert-triangle me-2"></i>Al finalizar, esta versión quedará bloqueada permanentemente.</div>
@endif

@unless($isFinalized)
    @can('academic-control.manage')
        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="{{ route('academic.control.versions.destroy', [$program, $scheme]) }}" data-confirm-action data-confirm-title="¿Eliminar este borrador?" data-confirm-text="Se eliminarán todas las ponderaciones y habilidades guardadas en esta versión. Esta acción no se puede deshacer." data-confirm-button="Sí, eliminar borrador">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger" type="submit"><i class="ti ti-trash me-1" aria-hidden="true"></i>Eliminar borrador</button>
            </form>
        </div>
    @endcan
@endunless

@if($isFinalized && !$hasDraft)
    @can('academic-control.manage')
        @include('academic-control.partials.create-version-modal', ['modalId' => 'createGradingVersionModal'])
    @endcan
@endif

<form method="POST" action="{{ route('academic.control.update', [$program, $scheme]) }}" id="grading-draft-form">
    @csrf @method('PUT')
    <div data-component-inputs></div>
    <div class="row g-3">
        <div class="col-xl-9">
            <x-ui.table-card title="Tipos de ponderación">
                @unless($isFinalized)
                    <x-slot:actions><button class="btn btn-primary btn-sm" type="button" data-add-weighting><i class="ti ti-plus me-1"></i>Agregar ponderación</button></x-slot:actions>
                @endunless
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Orden</th><th>Ponderación</th><th>Frecuencia</th><th>Método</th><th>Habilidades</th><th class="text-end">Porcentaje</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody data-weighting-list></tbody>
                </table>
            </x-ui.table-card>
        </div>
        <div class="col-xl-3"><div class="position-sticky" style="top:1rem"><x-ui.card title="Resumen"><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>Total general</span><strong data-main-total>0%</strong></div><div class="progress mb-3" style="height:8px"><div class="progress-bar" data-main-progress></div></div><label class="form-label required" for="passing-score">Nota mínima</label><div class="input-group"><input class="form-control" id="passing-score" name="passing_score" type="number" min="0" max="100" step="0.01" value="{{ old('passing_score', $scheme->passing_score) }}" required @disabled($isFinalized)><span class="input-group-text">/ 100</span></div></div></x-ui.card>
            @can('academic-control.manage')
                @unless($isFinalized)
                    <div class="d-grid gap-2 mt-3"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Guardar nota mínima</button><button class="btn btn-outline-danger" type="submit" form="finalize-grading"><i class="ti ti-lock me-1"></i>Finalizar configuración</button></div>
                @endunless
            @endcan
            </div></div>
    </div>
</form>
@can('academic-control.manage')
    @unless($isFinalized)
        <form id="finalize-grading" method="POST" action="{{ route('academic.control.finalize', [$program, $scheme]) }}" data-confirm-action data-confirm-title="¿Finalizar la configuración?" data-confirm-text="Esta versión quedará activa y no podrá editarse ni eliminarse después." data-confirm-button="Sí, finalizar configuración">@csrf</form>
    @endunless
@endcan

@unless($isFinalized)
<div class="modal modal-blur fade" id="weightingModal" tabindex="-1" aria-labelledby="weightingModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><div class="text-secondary small">Configuración de evaluación</div><h2 class="modal-title" id="weightingModalTitle">Nueva ponderación</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body"><div class="alert alert-danger d-none" data-modal-error role="alert"></div><div class="row g-3">
            <div class="col-md-7"><label class="form-label required" for="weighting-name">Título</label><input class="form-control" id="weighting-name" maxlength="100" required></div>
            <div class="col-md-5"><label class="form-label required" for="weighting-weight">Porcentaje general</label><div class="input-group"><input class="form-control" id="weighting-weight" type="number" min="0.01" max="100" step="0.01" required><span class="input-group-text">%</span></div></div>
            <div class="col-md-6"><label class="form-label required" for="weighting-frequency">Tipo de calificación</label><select class="form-select" id="weighting-frequency"><option value="daily">Diaria</option><option value="single">Única</option></select><div class="form-hint">Diaria se registrará en cada jornada; única, una vez por módulo.</div></div>
            <div class="col-md-6"><label class="form-label required" for="weighting-mode">Habilidades a calificar</label><select class="form-select" id="weighting-mode"><option value="single_skill">Una habilidad</option><option value="multiple_skills">Varias habilidades</option></select></div>
            <div class="col-12"><label class="form-label required" for="weighting-scoring-method">Método de ponderación</label><select class="form-select" id="weighting-scoring-method"><option value="percentage">Por porcentaje (0 a 100)</option><option value="simple">Simple (0 o 1)</option></select><div class="form-hint">En el método simple, 1 equivale a 100 y 0 equivale a 0 para los cálculos.</div></div>
        </div><hr><div class="d-flex justify-content-between align-items-center mb-2"><div><h3 class="mb-0">Habilidades</h3><small class="text-secondary">Entre 2 y 10 cuando selecciones varias.</small></div><span class="badge" data-skills-total>Total: 100%</span></div><div data-modal-skills></div><button class="btn btn-outline-primary btn-sm d-none" type="button" data-add-skill><i class="ti ti-plus me-1"></i>Agregar habilidad</button></div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="button" data-save-weighting><span class="spinner-border spinner-border-sm me-2 d-none" data-save-spinner></span>Guardar ponderación</button></div>
    </div></div>
</div>
@endunless
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('grading-draft-form'); if (!form) return;
    const editable = @json(!$isFinalized), list = form.querySelector('[data-weighting-list]'), inputs = form.querySelector('[data-component-inputs]');
    let components = {{ Illuminate\Support\Js::from($initialComponents) }}, editingIndex = null;
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
    const fmt = value => Number(value || 0).toFixed(2).replace(/\.00$/, '');
    const modalElement = document.getElementById('weightingModal'), modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const fields = {name: document.getElementById('weighting-name'), weight: document.getElementById('weighting-weight'), frequency: document.getElementById('weighting-frequency'), mode: document.getElementById('weighting-mode'), scoringMethod: document.getElementById('weighting-scoring-method')};
    let modalSkills = [];

    function renderList() {
        if (!components.length) list.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5"><i class="ti ti-scale-off fs-1 d-block mb-2"></i>No hay ponderaciones registradas.</td></tr>';
        else list.innerHTML = components.map((item,index) => `<tr><td><span class="badge bg-primary-lt text-primary">${index+1}</span></td><td><strong>${esc(item.name)}</strong><small class="d-block text-secondary">${item.skill_mode === 'single_skill' ? 'Calificación única' : 'Calificación por habilidades'}</small></td><td><span class="badge ${item.frequency === 'daily' ? 'bg-azure-lt text-azure' : 'bg-purple-lt text-purple'}">${item.frequency === 'daily' ? 'Diaria' : 'Única'}</span></td><td><span class="badge ${item.scoring_method === 'simple' ? 'bg-orange-lt text-orange' : 'bg-blue-lt text-blue'}">${item.scoring_method === 'simple' ? 'Simple · 0/1' : 'Porcentaje · 0/100'}</span></td><td>${item.skills.length} · ${item.skills.map(skill => esc(skill.name)).join(', ')}</td><td class="text-end fw-semibold">${fmt(item.weight)}%</td><td class="text-end">${editable ? `<div class="btn-group"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="up" data-index="${index}" aria-label="Subir" ${index===0?'disabled':''}><i class="ti ti-arrow-up"></i></button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="down" data-index="${index}" aria-label="Bajar" ${index===components.length-1?'disabled':''}><i class="ti ti-arrow-down"></i></button><button class="btn btn-outline-primary btn-sm" type="button" data-edit data-index="${index}"><i class="ti ti-edit me-1"></i>Modificar</button><button class="btn btn-outline-danger btn-sm" type="button" data-remove data-index="${index}" aria-label="Eliminar"><i class="ti ti-trash"></i></button></div>` : '<span class="text-secondary">Solo lectura</span>'}</td></tr>`).join('');
        inputs.innerHTML = components.map((item,index) => `<input type="hidden" name="components[${index}][name]" value="${esc(item.name)}"><input type="hidden" name="components[${index}][weight]" value="${esc(item.weight)}"><input type="hidden" name="components[${index}][frequency]" value="${esc(item.frequency)}"><input type="hidden" name="components[${index}][skill_mode]" value="${esc(item.skill_mode)}"><input type="hidden" name="components[${index}][scoring_method]" value="${esc(item.scoring_method)}">${item.skills.map((skill,s) => `<input type="hidden" name="components[${index}][skills][${s}][name]" value="${esc(skill.name)}"><input type="hidden" name="components[${index}][skills][${s}][weight]" value="${esc(skill.weight)}">`).join('')}`).join('');
        const total=components.reduce((sum,item)=>sum+Number(item.weight||0),0), progress=form.querySelector('[data-main-progress]'); form.querySelector('[data-main-total]').textContent=`${fmt(total)}%`; progress.style.width=`${Math.min(total,100)}%`; progress.className=`progress-bar ${Math.abs(total-100)<.001?'bg-success':total>100?'bg-danger':''}`;
    }
    function renderSkills() {
        const multiple=fields.mode.value==='multiple_skills'; if(!multiple) modalSkills=[{name:modalSkills[0]?.name||'',weight:100}]; if(multiple&&modalSkills.length<2) modalSkills.push({name:'',weight:0});
        document.querySelector('[data-modal-skills]').innerHTML=modalSkills.map((skill,index)=>`<div class="row g-2 align-items-end mb-2"><div class="col"><label class="form-label" for="modal-skill-${index}">Habilidad ${index+1}</label><input class="form-control" id="modal-skill-${index}" value="${esc(skill.name)}" data-skill-name data-index="${index}"></div><div class="col-4"><label class="form-label" for="modal-skill-weight-${index}">Porcentaje</label><div class="input-group"><input class="form-control" id="modal-skill-weight-${index}" type="number" min="0.01" max="100" step="0.01" value="${multiple?esc(skill.weight):100}" data-skill-weight data-index="${index}" ${multiple?'':'disabled'}><span class="input-group-text">%</span></div></div>${multiple?`<div class="col-auto"><button class="btn btn-icon btn-outline-danger" type="button" data-remove-skill data-index="${index}" ${modalSkills.length<=2?'disabled':''}><i class="ti ti-x"></i></button></div>`:''}</div>`).join('');
        document.querySelector('[data-add-skill]').classList.toggle('d-none',!multiple); const total=modalSkills.reduce((sum,s)=>sum+Number(s.weight||0),0),badge=document.querySelector('[data-skills-total]'); badge.textContent=`Total: ${fmt(total)}%`; badge.className=`badge ${Math.abs(total-100)<.001?'bg-green-lt text-green':'bg-red-lt text-red'}`;
    }
    function refreshSkillTotal() { const total=modalSkills.reduce((sum,s)=>sum+Number(s.weight||0),0),badge=document.querySelector('[data-skills-total]'); badge.textContent=`Total: ${fmt(total)}%`; badge.className=`badge ${Math.abs(total-100)<.001?'bg-green-lt text-green':'bg-red-lt text-red'}`; }
    function openEditor(index=null) { editingIndex=index; const item=index===null?{name:'',weight:'',frequency:'daily',skill_mode:'single_skill',scoring_method:'percentage',skills:[{name:'',weight:100}]}:structuredClone(components[index]); fields.name.value=item.name; fields.weight.value=item.weight; fields.frequency.value=item.frequency; fields.mode.value=item.skill_mode; fields.scoringMethod.value=item.scoring_method || 'percentage'; modalSkills=item.skills; document.getElementById('weightingModalTitle').textContent=index===null?'Nueva ponderación':'Modificar ponderación'; document.querySelector('[data-modal-error]').classList.add('d-none'); renderSkills(); modal.show(); }
    function persist() { renderList(); form.requestSubmit(); }
    document.querySelector('[data-add-weighting]')?.addEventListener('click',()=>openEditor());
    fields.mode?.addEventListener('change',renderSkills);
    modalElement?.addEventListener('input',event=>{if(event.target.matches('[data-skill-name]'))modalSkills[event.target.dataset.index].name=event.target.value;if(event.target.matches('[data-skill-weight]'))modalSkills[event.target.dataset.index].weight=event.target.value;refreshSkillTotal();});
    modalElement?.addEventListener('click',event=>{const button=event.target.closest('button');if(button?.matches('[data-add-skill]')&&modalSkills.length<10){modalSkills.push({name:'',weight:0});renderSkills();}if(button?.matches('[data-remove-skill]')){modalSkills.splice(button.dataset.index,1);renderSkills();}});
    document.querySelector('[data-save-weighting]')?.addEventListener('click',()=>{const error=document.querySelector('[data-modal-error]'),name=fields.name.value.trim(),weight=Number(fields.weight.value),multiple=fields.mode.value==='multiple_skills',skillTotal=modalSkills.reduce((sum,s)=>sum+Number(s.weight||0),0);let message='';if(!name||!weight||weight<=0||weight>100)message='Completa el título y un porcentaje válido.';else if(components.some((item,index)=>index!==editingIndex&&item.name.toLowerCase()===name.toLowerCase()))message='Ya existe una ponderación con este título.';else if(modalSkills.some(skill=>!skill.name.trim()))message='Todas las habilidades deben tener nombre.';else if(multiple&&(modalSkills.length<2||modalSkills.length>10))message='Configura entre 2 y 10 habilidades.';else if(Math.abs(skillTotal-100)>.001)message='Las habilidades deben sumar exactamente 100%.';if(message){error.textContent=message;error.classList.remove('d-none');return;}const item={name,weight,frequency:fields.frequency.value,skill_mode:fields.mode.value,scoring_method:fields.scoringMethod.value,skills:modalSkills.map(skill=>({name:skill.name.trim(),weight:Number(skill.weight) }))};if(editingIndex===null)components.push(item);else components[editingIndex]=item;modal.hide();persist();});
    list.addEventListener('click',event=>{const button=event.target.closest('button');if(!button)return;const index=Number(button.dataset.index);if(button.matches('[data-edit]'))return openEditor(index);if(button.matches('[data-remove]')){if(confirm('¿Eliminar esta ponderación del borrador?')){components.splice(index,1);persist();}return;}if(button.dataset.move==='up')[components[index-1],components[index]]=[components[index],components[index-1]];if(button.dataset.move==='down')[components[index+1],components[index]]=[components[index],components[index+1]];persist();});
    document.querySelector('[data-version-select]')?.addEventListener('change',event=>window.location.href=event.target.value);renderList();
});
</script>
@endpush
