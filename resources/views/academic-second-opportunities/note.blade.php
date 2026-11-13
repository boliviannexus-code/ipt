@if($attempt)
<br><small>2T · Original: {{ number_format((float) $attempt->original_score, 2, ',', '.') }} · 2T: {{ number_format((float) $attempt->score, 2, ',', '.') }}</small>
@endif
