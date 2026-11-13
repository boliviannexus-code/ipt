# 2T · Segunda oportunidad

En Académico → 2T se registra una sola evaluación adicional por resultado reprobado de un curso cerrado. El permiso `second-opportunities.manage` se entrega inicialmente a `admin` y `super_admin` y puede administrarse desde Roles.

El estudiante debe estar activo, con matrícula `enrolled` del mismo programa y nivel que el cierre, sin asignación activa a ningún módulo, sin asignaciones posteriores al cierre, sin resultados posteriores del programa y sin intento 2T previo. El listado, formulario y guardado verifican estas condiciones; el guardado vuelve a validarlas bajo bloqueo transaccional.

Se ingresa una nota entre 0 y 100 (hasta dos decimales) y un motivo. La pantalla de revisión muestra el resultado y sus consecuencias antes de confirmar. El mínimo es el guardado en el cierre original, aunque cambie la configuración de ponderaciones.

- Aprobado: avanza al siguiente nivel activo, disponible para asignación manual. Si era el último nivel, se evalúan los requisitos académicos y de pagos para finalizar el programa.
- Reprobado: permanece en el mismo nivel, disponible para otro módulo del mismo nivel.
- No se reabre el curso ni se crean módulos, asignaciones o cargos.

`academic_second_opportunities` guarda matrícula, resultado, nota y estado originales, nota y estado 2T, mínimo, motivo, operador y fecha. Una restricción única por resultado evita un segundo intento. La nota final efectiva se actualiza en `academic_module_student_results`; las ponderaciones, asistencias y metadatos del cierre permanecen intactos. Resultados, centralizador y kárdex (incluidos PDF) identifican la nota 2T y la original. Las matrículas y módulos con registros 2T no pueden eliminarse para conservar la auditoría.

## Despliegue

Ejecutar `php artisan migrate --force` en el entorno destino. La migración `2026_11_13_100000_create_academic_second_opportunities` crea la tabla y otorga el permiso a los roles administrativos existentes; no modifica resultados anteriores. No se debe ejecutar una reversión si existen intentos 2T que se necesitan conservar.

## Verificación

`php artisan test tests/Feature/Academic/SecondOpportunityTest.php`

Incluye aprobación al mínimo histórico, reprobación, intento único, revisión previa, asignación manual tras aprobar, último nivel y pagos, cambios concurrentes de elegibilidad, autorización, aislamiento por empresa y reportes HTML/PDF.
