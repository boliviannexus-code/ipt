@props([
    'title' => null,
    'actions' => null,
    'footer' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => "card {$class}"]) }}>
    @if ($title || $actions)
        <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
            @if ($title)
                <h3 class="card-title mb-0">{{ $title }}</h3>
            @endif

            @if ($actions)
                <div>{{ $actions }}</div>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if ($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
