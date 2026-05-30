@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-character-counter]').forEach((field) => {
                const target = document.querySelector(field.dataset.characterCounter);

                if (!target) {
                    return;
                }

                const min = Number(field.getAttribute('minlength') || 0);
                const max = Number(field.getAttribute('maxlength') || 0);
                const update = () => {
                    const length = field.value.length;
                    const minimum = min ? ` / minimo ${min}` : '';
                    const maximum = max ? ` / maximo ${max}` : '';
                    target.textContent = `${length} caracteres${minimum}${maximum}`;
                    target.classList.toggle('text-danger', (min && length < min) || (max && length > max));
                    target.classList.toggle('text-success', (!min || length >= min) && (!max || length <= max));
                };

                field.addEventListener('input', update);
                update();
            });
        </script>
    @endpush
@endonce
