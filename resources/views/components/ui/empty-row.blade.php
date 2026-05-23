@props([
    'colspan',
    'message' => 'No hay registros disponibles.',
])

<tr>
    <td class="text-center text-body-secondary py-4" colspan="{{ $colspan }}">
        {{ $message }}
    </td>
</tr>
