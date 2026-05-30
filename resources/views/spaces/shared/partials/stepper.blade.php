@php
    $steps = [
        'details' => ['label' => 'Datos', 'route' => isset($space) ? route('spaces.shared.details.edit', $space) : null],
        'rooms' => ['label' => 'Habitaciones', 'route' => isset($space) ? route('spaces.shared.rooms.edit', $space) : null],
        'beds' => ['label' => 'Camas', 'route' => isset($space) ? route('spaces.shared.beds.edit', $space) : null],
        'room-services' => ['label' => 'Servicios habitacion', 'route' => isset($space) ? route('spaces.shared.room-services.edit', $space) : null],
        'photos' => ['label' => 'Fotos', 'route' => isset($space) ? route('spaces.shared.photos.edit', $space) : null],
        'services' => ['label' => 'Servicios generales', 'route' => isset($space) ? route('spaces.shared.services.edit', $space) : null],
        'location' => ['label' => 'Ubicacion', 'route' => isset($space) ? route('spaces.shared.location.edit', $space) : null],
        'review' => ['label' => 'Revision', 'route' => isset($space) ? route('spaces.shared.review', $space) : null],
    ];
@endphp

<div class="steps steps-counter steps-blue mb-3">
    @foreach ($steps as $key => $step)
        <a class="step-item {{ $currentStep === $key ? 'active' : '' }}" href="{{ $step['route'] }}">{{ $step['label'] }}</a>
    @endforeach
</div>
