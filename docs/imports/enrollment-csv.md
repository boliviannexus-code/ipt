# Importación de inscripciones históricas

`enrollments:import-csv` lee CSV UTF-8 o Windows-1252. Por defecto ejecuta el flujo completo en una transacción y lo revierte. `--commit` guarda todo el lote; cualquier error revierte todas las filas.

Ejemplo ejecutado para LPM:

```bash
php artisan enrollments:import-csv storage/app/private/imports/estudiantes.csv \
  --campus=3 --batch=lpm-estudiantes-2026 --gender=Masculino \
  --holder-surname=9981693=RONDANO \
  --report=storage/app/private/imports/matriculas-migradas.csv --commit
```

El lote y número de origen identifican cada fila en `enrollment_import_rows`. Un reintento con los mismos datos devuelve la matrícula existente; cambios en una fila ya importada se rechazan. Usar el mismo lote en reintentos. Los estudiantes existentes fuera del lote se rechazan para evitar sobrescribirlos.

Los catálogos se resuelven dentro de la empresa de la sede, normalizando espacios, mayúsculas y tildes; se exige una coincidencia única y vínculo válido de programa/plan. El ejecutivo debe estar activo y habilitado para ventas.

Los titulares/clientes se reutilizan por CI (tipo de documento 1, sin complemento). Los nombres contradictorios de un titular repetido requieren corrección explícita mediante `--holder-surname=CI=APELLIDO`. Los clientes existentes se conservan; los nuevos reciben razón social igual al paterno, correo migracion@gmail.com y el teléfono fuente sin decimales. Se conservan contactos múltiples y sus anotaciones.

Nacimiento de titulares y estudiantes: 1985-12-11. Correo: migracion@gmail.com. Género especificado explícitamente mediante opción. Igual CI requiere igual nombre y establece parentesco Titular; el resto usa Otro. Contacto principal: Titular.

La fecha fuente se interpreta como mes/día/año y se utiliza al ejecutar el flujo existente de inscripción: fechas de inscripción/confirmación e historial de plan históricas, cargo inicial del mes de inscripción (vencimiento al inicio de ese mes). Se genera un cargo inicial por matrícula; no se inventan pagos ni se generan mensualidades adicionales retroactivas. Los planes con costo quedan PREINSCRITOS conforme al flujo actual; planes gratuitos quedan activos.

La secuencia de matrícula usa programa y sede, con el generador habitual. La simulación revierte las secuencias de matrícula; PostgreSQL puede consumir IDs internos durante una simulación, sin crear registros persistentes.

Reporte privado en CSV con titular, estudiante, IDs, parentesco, fecha, programa, plan, código de matrícula, contrato, estado y monto del cargo. Los archivos fuente y reportes no se incluyen en Git.

Verificación automatizada en base `testing`:

```bash
php artisan test --filter=EnrollmentCsvImportTest
```
