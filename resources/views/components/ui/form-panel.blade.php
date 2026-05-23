@props([
    'action',
    'method' => 'POST',
    'enctype' => null,
])

<div class="card form-panel">
    <div class="card-body">
        <form method="POST" action="{{ $action }}" autocomplete="off" novalidate @if ($enctype) enctype="{{ $enctype }}" @endif>
            @csrf
            @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                @method($method)
            @endif

            {{ $slot }}
        </form>
    </div>
</div>
