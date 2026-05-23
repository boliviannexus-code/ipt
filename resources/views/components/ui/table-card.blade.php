@props([
    'title',
    'actions' => null,
    'footer' => null,
])

<x-ui.card :title="$title" {{ $attributes->merge(['class' => 'table-card']) }}>
    @if ($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endif

    <div class="table-responsive">
        {{ $slot }}
    </div>

    @if ($footer)
        <x-slot:footer>
            {{ $footer }}
        </x-slot:footer>
    @endif
</x-ui.card>
