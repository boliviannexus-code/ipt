@props([
    'route' => null,
    'icon' => null,
    'label',
    'active' => false,
    'disabled' => false,
])

<li class="nav-item {{ $active ? 'active' : '' }}">
    <a
        class="nav-link {{ $disabled ? 'disabled text-muted' : '' }}"
        href="{{ $route && ! $disabled ? route($route) : '#' }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        @if ($icon)
            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="{{ $icon }}"></i></span>
        @endif
        <span class="nav-link-title">{{ $label }}</span>
    </a>
</li>
