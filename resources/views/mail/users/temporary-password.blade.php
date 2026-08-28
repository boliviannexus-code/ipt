<x-mail::message>
# Bienvenido, {{ $name }}

Tu cuenta de acceso a **{{ config('app.name') }}** está lista.

<x-mail::panel>
**Tu usuario es tu correo electrónico:**  
{{ $email }}

**Contraseña temporal:**  
{{ $temporaryPassword }}
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Ingresar al sistema
</x-mail::button>

## Importante

Esta contraseña es temporal. Cuando ingreses, abre **Mi cuenta** y cámbiala inmediatamente.

## Recomendaciones de seguridad

- No compartas tu contraseña con otras personas.
- Utiliza una contraseña única de al menos 8 caracteres.
- Combina letras mayúsculas, minúsculas, números y símbolos.
- No respondas este correo enviando tus credenciales.
- Si no reconoces esta cuenta, comunícate con el administrador.

Saludos,  
El equipo de {{ config('app.name') }}
</x-mail::message>
