<h1>Centralizador de notas</h1>
<p><strong>Programa:</strong> {{ $module->program->title }}<br>
<strong>Módulo:</strong> {{ $module->name }} · <strong>Nivel:</strong> {{ $module->level?->name ?? '—' }}<br>
<strong>Clases:</strong> {{ $module->classSessions->count() }}
@if($scheme) · <strong>Configuración:</strong> v{{ $scheme->version }} · <strong>Nota de aprobación:</strong> {{ number_format((float) $scheme->passing_score, 2, ',', '.') }}@endif
</p>
@if(!$scheme || $skills->isEmpty())
<p>Este módulo todavía no tiene ponderaciones configuradas.</p>
@endif
@php($columnCount = 2 + $skills->count() + $displayComponents->filter(fn ($component) => $component->skills->count() > 1)->count())
@php($scoreWidth = 78 / ($columnCount - 1))
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background-color: #e8edf4;">
            <th rowspan="{{ $displayComponents->isEmpty() ? 1 : 2 }}" width="22%"><strong>Estudiante</strong></th>
            @foreach($displayComponents as $component)
                <th width="{{ $scoreWidth * ($component->skills->count() + ($component->skills->count() > 1 ? 1 : 0)) }}%" align="center" colspan="{{ $component->skills->count() + ($component->skills->count() > 1 ? 1 : 0) }}"><strong>{{ $component->name }}</strong><br>{{ $component->frequency === \App\Enums\GradingFrequency::Daily ? 'Diaria' : 'Única' }} · {{ number_format((float) $component->weight, 2, ',', '.') }}%</th>
            @endforeach
            <th width="{{ $scoreWidth }}%" rowspan="{{ $displayComponents->isEmpty() ? 1 : 2 }}" align="center"><strong>Nota acumulada</strong></th>
        </tr>
        @if($displayComponents->isNotEmpty())
        <tr style="background-color: #e8edf4;">
            @foreach($displayComponents as $component)
                @foreach($component->skills as $skill)
                    <th width="{{ $scoreWidth }}%" align="center">{{ $skill->name }}</th>
                @endforeach
                @if($component->skills->count() > 1)<th width="{{ $scoreWidth }}%" align="center">Promedio</th>@endif
            @endforeach
        </tr>
        @endif
    </thead>
    <tbody>
        @forelse($students as $row)
            <tr nobr="true">
                <td width="22%">{{ trim("{$row['student']->first_name} {$row['student']->paternal_surname} {$row['student']->maternal_surname}") }}<br>{{ $row['student']->identity_document }}</td>
                @foreach($displayComponents as $component)
                    @foreach($component->skills as $skill)
                        @php($score = $row['scores']->get($skill->id, ['average' => null, 'count' => 0]))
                        <td width="{{ $scoreWidth }}%" align="center">{{ $score['average'] !== null ? number_format($score['average'], 2, ',', '.') : '—' }}@if($score['average'] !== null && $component->frequency === \App\Enums\GradingFrequency::Daily)<br>×{{ $score['count'] }}@endif</td>
                    @endforeach
                    @if($component->skills->count() > 1)
                        @php($average = $row['component_averages']->get($component->id))
                        <td width="{{ $scoreWidth }}%" align="center">{{ $average !== null ? number_format($average, 2, ',', '.') : '—' }}</td>
                    @endif
                @endforeach
                <td width="{{ $scoreWidth }}%" align="center">@if($row['overall_score'] !== null)<strong>{{ number_format($row['overall_score'], 2, ',', '.') }}</strong><br>{{ $row['overall_score'] >= (float) ($scheme?->passing_score ?? 51) ? 'Aprobado' : 'Reprobado' }}@else — @endif</td>
            </tr>
        @empty
            <tr><td colspan="{{ $columnCount }}">Este módulo no tiene estudiantes asignados.</td></tr>
        @endforelse
    </tbody>
</table>
<p>La nota acumulada combina ponderaciones diarias y únicas según su peso configurado. Las habilidades pendientes aportan cero hasta ser evaluadas.</p>
