@props([
    'label',
    'value',
    'icon' => 'ti ti-dashboard',
    'tone' => 'primary',
])

<div class="card h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar bg-{{ $tone }}-lt text-{{ $tone }}">
            <i class="{{ $icon }} fs-4"></i>
        </span>
        <div>
            <div class="text-muted small">{{ $label }}</div>
            <div class="h2 mb-0">{{ $value }}</div>
        </div>
    </div>
</div>
