@php
    $steps = [
        'details' => ['label' => 'Datos', 'route' => isset($space) ? route('spaces.private.details.edit', $space) : null],
        'descriptions' => ['label' => 'Descripciones', 'route' => isset($space) ? route('spaces.private.descriptions.edit', $space) : null],
        'photos' => ['label' => 'Fotos', 'route' => isset($space) ? route('spaces.private.photos.edit', $space) : null],
        'services' => ['label' => 'Servicios', 'route' => isset($space) ? route('spaces.private.services.edit', $space) : null],
        'location' => ['label' => 'Ubicacion', 'route' => isset($space) ? route('spaces.private.location.edit', $space) : null],
        'review' => ['label' => 'Revision', 'route' => isset($space) ? route('spaces.private.review', $space) : null],
    ];
@endphp

<div class="steps steps-counter steps-blue mb-3">
    @foreach ($steps as $key => $step)
        <a class="step-item {{ $currentStep === $key ? 'active' : '' }}" href="{{ $step['route'] }}">
            {{ $step['label'] }}
        </a>
    @endforeach
</div>
