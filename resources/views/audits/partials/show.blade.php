@php
    $formatAuditValue = function ($value): string {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
        }

        return (string) $value;
    };

    $fields = collect(array_keys($oldValues))
        ->merge(array_keys($newValues))
        ->unique()
        ->values();
@endphp

<div class="mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge text-bg-primary">{{ $eventLabel }}</span>
        <span class="fw-semibold">{{ $auditableLabel }}</span>
        <span class="text-body-secondary">#{{ $audit->auditable_id }}</span>
    </div>
    <div class="text-body-secondary small mt-1">
        {{ $audit->created_at?->format('Y-m-d H:i:s') }} · {{ $audit->user?->name ?? 'Sistema' }} · {{ $audit->ip_address ?? 'Sin IP' }}
    </div>
</div>

<dl class="row mb-3">
    <dt class="col-sm-3">URL</dt>
    <dd class="col-sm-9 text-break">{{ $audit->url ?? '-' }}</dd>

    <dt class="col-sm-3">Navegador</dt>
    <dd class="col-sm-9 text-break">{{ $audit->user_agent ?? '-' }}</dd>

    <dt class="col-sm-3">Tags</dt>
    <dd class="col-sm-9">{{ $audit->tags ?? '-' }}</dd>
</dl>

<div class="table-responsive">
    <table class="table table-sm table-vcenter mb-0">
        <thead>
            <tr>
                <th>Campo</th>
                <th>Antes</th>
                <th>Despues</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fields as $field)
                <tr>
                    <td class="fw-semibold">{{ $field }}</td>
                    <td class="text-break">{{ $formatAuditValue($oldValues[$field] ?? null) }}</td>
                    <td class="text-break">{{ $formatAuditValue($newValues[$field] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center text-body-secondary py-4" colspan="3">Este evento no registro cambios de valores.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
