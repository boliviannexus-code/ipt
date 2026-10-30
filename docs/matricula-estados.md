# Flujo de estados de una matrícula

Referencia funcional indicada por el usuario el 20/10/2026. Aplicar este flujo al diseñar las siguientes funcionalidades. Este documento registra las reglas de negocio y el avance de su implementación.

La matrícula corresponde al contrato del estudiante en un programa. Su estado pertenece a esa matrícula, sin trasladarse automáticamente a otros contratos del mismo estudiante.

| Estado | Significado y condiciones |
| --- | --- |
| ACTIVO | El estudiante cursa el programa. Se activa al recibir el primer pago positivo de su contrato, aunque sea parcial; no es necesario completar la primera cuota. Se conserva la fecha de ese primer pago. Las matrículas gratuitas mantienen su activación automática existente. |
| FINALIZADO | Cumplió todos los pagos y superó todos los módulos del programa. Se habilita el proceso de certificación. |
| CERTIFICADO | Se completó el proceso de entrega del certificado académico. Concluye el programa. |
| INACTIVO | Al acumular 3 faltas (Attendance = 0) en clases finalizadas de un mismo módulo, la matrícula de ese programa pasa de ACTIVO a INACTIVO. Las faltas son acumulativas, no necesariamente consecutivas. El estudiante permanece visible y se resalta en naranja claro. Puede volver a ACTIVO con un compromiso de asistencia; asistir o pagar nuevamente no lo reactiva automáticamente. |
| SUSPENDIDO | El estudiante pone en pausa el contrato para retomarlo en otra fecha, luego de permanecer INACTIVO. Su reactivación considera un cargo adicional. |
| ABANDONO | No se reactiva tras la suspensión. Su reactivación también considera un cargo adicional. |
| BAJA | Termina el contrato y ya no admite reactivación. |

## Transiciones indicadas

- ACTIVO → FINALIZADO: todos los pagos cumplidos y todos los módulos superados.
- FINALIZADO → CERTIFICADO: proceso de entrega del certificado académico completado.
- ACTIVO → INACTIVO: asistencia irregular.
- INACTIVO → ACTIVO: compromiso de asistencia.
- INACTIVO → SUSPENDIDO: pausa del contrato para retomarlo posteriormente.
- SUSPENDIDO → ACTIVO: reactivación, considerando el cargo adicional correspondiente.
- SUSPENDIDO → ABANDONO: no se reactiva.
- ABANDONO → ACTIVO: reactivación, considerando el cargo adicional correspondiente.
- BAJA: cierre definitivo sin posibilidad de reactivación; falta definir desde qué estados y bajo qué condiciones se aplica.

## Detalles pendientes para futuras actividades

No inventar ni automatizar estas reglas hasta definirlas con el usuario:

- Antes del primer pago, las matrículas con costo conservan el estado PREINSCRITO. ACTIVO usa por compatibilidad el valor técnico `enrolled`. El primer pago positivo activa únicamente ese contrato; agregar un cargo gratuito no lo activa.
- Umbrales, plazos y responsables para suspensión, abandono y baja. La asistencia irregular ya tiene umbral de 3 faltas por módulo.
- Contenido, registro y aprobación del compromiso de asistencia.
- Solicitud de pausa, fecha prevista de retorno y conservación de avances y obligaciones económicas.
- Servicio, importe y exigencia de pago del cargo de reactivación antes de volver a ACTIVO. Usar el módulo de servicios/cargos adicionales; no asumir aún un cargo automático.
- Validación de módulos superados, incluidos los reconocimientos por examen de nivel, y alcance de «todos los pagos» para habilitar FINALIZADO.
- Registro y evidencia de la entrega del certificado.
- Compatibilidad y migración de los estados técnicos actuales: `pre_enrolled`, `enrolled` y `cancelled`. No equiparar automáticamente `cancelled` con BAJA ni con el pago de una matrícula.

La activación por primer pago y la inactivación por 3 faltas en un módulo ya están implementadas. Los demás umbrales y transiciones se implementarán progresivamente en las siguientes actividades, con historial de cambios cuando se diseñe esa funcionalidad.

## Cambios manuales de estado

Desde Estudiantes → Cambiar estado, usuarios con `students.manage` pueden registrar SUSPENDIDO, ABANDONO o BAJA para una matrícula ACTIVA, INACTIVA, SUSPENDIDA o en ABANDONO. Se exige un estado diferente, observación de hasta 2.000 caracteres y fecha de registro no futura ni anterior al último cambio manual. Para SUSPENDIDO se exige una fecha prevista de reactivación posterior a la de registro. Esa fecha no dispara reactivación ni cargos automáticos.

Cada cambio conserva el estado anterior, nuevo estado, observación, fechas y usuario que lo registró. El estudiante se retira de las asignaciones abiertas del programa seleccionado, conservando sus registros académicos. Los demás programas permanecen intactos. BAJA es terminal: este acceso no permite nuevos cambios ni reactivación desde BAJA. Los tres estados permanecen visibles en el listado de estudiantes.

## Finalización del programa

Al aprobar el último nivel activo, la matrícula pasa automáticamente a FINALIZADO (`finished`) si todos los niveles activos están aprobados o reconocidos por examen de nivel y todos los cargos no anulados están pagados. Se conserva la fecha `completed_at`. Si quedan pagos pendientes, se vuelve a evaluar al recibir el pago final o aplicar saldo a favor. Reprobar el último módulo no finaliza el programa. SUSPENDIDO, ABANDONO y BAJA no se finalizan automáticamente. FINALIZADO permanece visible en listados y kárdex, como estado previo a la certificación; no se implementa aún la entrega de certificados.
