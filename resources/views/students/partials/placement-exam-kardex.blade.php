@if(isset($placementExams) && $placementExams->isNotEmpty())
    @if(!($forPdf ?? false))<div class="card mb-3"><div class="card-header"><h3 class="card-title">Examen de nivel · Reconocimientos</h3></div><div class="card-body">@else<h3>Examen de nivel · Reconocimientos</h3>@endif
    @foreach($placementExams as $exam)
        <p><b>{{ $exam->program->title }}</b> · {{ $exam->examined_at->format('d/m/Y H:i') }}<br>
        Nivel destino: <b>{{ $exam->target_level_name }}</b> · Nota del examen: {{ number_format($exam->score, 2, ',', '.') }}<br>
        Docente evaluador: {{ $exam->teacher?->full_name ?? '—' }}<br>
        Observación: {{ $exam->observation }}</p>
        <table class="table table-sm table-bordered" border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead><tr><th>Nivel</th><th>Nota</th><th>Resultado</th></tr></thead>
            <tbody>
                @forelse($exam->levels as $recognition)
                    <tr><td>{{ $recognition->level_name }}</td><td>{{ number_format($exam->score, 2, ',', '.') }}</td><td>Reconocido por examen de nivel</td></tr>
                @empty
                    <tr><td colspan="3">Los niveles anteriores ya tenían aprobación o reconocimiento.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
    @if(!($forPdf ?? false))</div></div>@endif
@endif
