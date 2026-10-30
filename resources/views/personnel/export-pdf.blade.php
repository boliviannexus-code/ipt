<h1>Listado de personal</h1>
<p>Generado: {{ now()->format('d/m/Y H:i') }} · Total: {{ count($rows) }}</p>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background-color: #e9ecef; font-weight: bold;">
            @foreach ($headings as $heading)<th>{{ $heading }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="8">No hay personal registrado.</td></tr>
        @endforelse
    </tbody>
</table>
