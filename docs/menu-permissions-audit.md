# Revisión de accesos del menú

Revisión: 26/10/2026.

Los permisos se asignan en **Administración → Roles → Configurar acceso**. El catálogo se consulta en **Administración → Permisos**. Para operar la pantalla de roles se necesitan `roles.view` y `roles.assign-permissions`. La consulta del catálogo requiere `permissions.view`; no exige crear, editar ni eliminar permisos.

## Correcciones

- Crear o editar un rol ya no permite asignar permisos sin `roles.assign-permissions`. La matriz se muestra únicamente a quien puede configurar accesos.

- Al desmarcar todos los permisos en «Editar rol», ahora se guardan sin accesos. Antes, al no enviarse ninguna casilla, se conservaba la selección anterior.

- Áreas, Cargos, Personal y Permisos aplicaban todos los permisos del recurso a todas sus rutas. Ahora cada acción exige únicamente su permiso correspondiente.
- Pruebas de facturación ya no depende de `invoices.issue` para aparecer en el menú: usa `invoice-tests.run`, igual que su ruta.
- Las secciones Facturación y SIAT reconocen la empresa activa seleccionada por el superadministrador.
- Dashboard se muestra cuando el usuario tiene `dashboard.view`, igual que la autorización de su controlador.
- Se ocultan los botones de escritura de Áreas y Cargos cuando el acceso es de consulta.
- Los nombres del menú aparecen y se pueden buscar en la configuración de permisos de Roles, incluidos Ponderaciones, Promover módulo, Seguimiento y Examen de nivel.
- Un catálogo central registra todos los permisos existentes mediante una migración. No elimina permisos personalizados ni cambia asignaciones de roles o usuarios.
- Se protegen los nombres y la eliminación de permisos del sistema para evitar romper las comprobaciones del código. Sus asignaciones siguen siendo configurables desde Roles.

## Mapa del menú

| Opción | Ruta | Permiso de acceso |
| --- | --- | --- |
| Dashboard | `dashboard` | `dashboard.view (controlador)` |
| Listado | `rectorate.index` | `rectorate.create` |
| Nuevo | `rectorate.new` | `rectorate.create` |
| Ponderaciones | `academic.weightings.index` | `academic-control.view` |
| Control académico | `academic.control.index` | `academic-control.view` |
| Módulos | `academic.modules.index` | `academic-modules.view` |
| Promover módulo | `academic.promotions.index` | `academic-modules.view` |
| Datos de titular y estudiante | `academic.personal-data.index` | `academic-personal-data.edit` |
| Examen de nivel | `placement-exams.index` | `students.view` |
| Estudiantes | `students.index` | `students.view` |
| Matrículas | `reports.enrollments.index` | `enrollment-reports.view` |
| Gestión de todos los módulos | `all-modules.modules.index` | `all-modules.manage` |
| Historial mis módulos | `teacher.modules.history` | `teaching.view` |
| Mis módulos | `teacher.modules.index` | `teaching.view` |
| Seguimiento | `teacher.tracking.index` | `teaching.view` |
| Empresas | `companies.index` | `companies.view` |
| Sedes | `campuses.index` | `campuses.view` |
| Programa | `parameters.programs.index` | `programs.view` |
| Planes | `parameters.plans.index` | `plans.view` |
| Origen comercial | `parameters.commercial-origins.index` | `commercial-origins.view` |
| Productos | `parameters.products.index` | `products.view` |
| Autorizacion | `parameters.authorization.index` | `sin-authorizations.view` |
| Configuracion | `billing.invoice-print-settings.edit` | `invoices.issue` |
| Categorias | `parameters.categories.index` | `product-categories.view` |
| Clientes | `parameters.customers.index` | `customers.view` |
| Servicios WSDL | `siat.wsdl-services.index` | `sin-api-tokens.view` |
| Token API | `sin-api-token.index` | `sin-api-tokens.view` |
| Verificar comunicacion | `siat.communication.index` | `siat-communication.view` |
| CUIS | `siat.cuis.index` | `siat-cuis.view` |
| Sucursales | `siat.branches.index` | `siat-branches.view` |
| Catalogos SIAT | `siat.catalogs.index` | `siat-catalogs.view` |
| Mi caja | `cash-registers.index` | `cash-registers.view` |
| Historial | `cash-registers.history` | `cash-registers.view` |
| Servicios adicionales | `additional-services.index` | `additional-services.view` |
| Cargos por estudiante | `additional-charges.index` | `additional-charges.view` |
| Cobrar | `rectorate.collectible-accounts.index` | `accounts.collect` |
| Facturas | `billing.invoices.index` | `invoices.view` |
| Contingencias | `billing.contingencies.index` | `contingencies.view` |
| Eventos significativos | `billing.significant-events.index` | `invoices.issue` |
| Emitir factura | `billing.invoices.issue.index` | `invoices.issue` |
| Pruebas de facturación | `billing.invoice-tests.index` | `invoice-tests.run` |
| Contingencias 2 | `billing.cafc-contingencies.index` | `cafc-ranges.view` |
| Rangos CAFC | `billing.cafc-ranges.index` | `cafc-ranges.view` |
| Facturas manuales | `billing.manual-cafc.index` | `manual-cafc.view` |
| Contingencias | `billing.contingencies.index` | `contingencies.view` |
| Personal | `personnel.index` | `personnel.view` |
| Áreas | `areas.index` | `areas.view` |
| Cargos | `positions.index` | `positions.view` |
| Usuarios | `users.index` | `users.view` |
| Roles | `roles.index` | `roles.view` |
| Permisos | `permissions.index` | `permissions.view` |
| Auditoria | `audits.index` | `audits.view` |
| Respaldos | `backups.index` | `backups.view` |
| Super admin · Contratos | `super-admin.contracts.index` | `Rol super_admin (acceso global reservado)` |

## Condiciones y permisos compartidos

Las opciones de un mismo módulo pueden compartir permisos. Ponderaciones y Control académico usan `academic-control.*`; Módulos y Promover módulo usan `academic-modules.*`; Examen de nivel y Estudiantes usan `students.*`; Mis módulos, Historial mis módulos y Seguimiento usan `teaching.*`. La matriz de Roles indica los nombres del menú asociados a cada grupo.

La empresa activa y el perfil docente siguen siendo requisitos cuando corresponde. Otorgar un permiso no asigna una empresa ni vincula un perfil docente. El acceso global a Contratos sigue reservado al rol `super_admin`; no es delegable con un permiso de operación de empresa.

Los administradores conservan la protección existente que exige `roles.view`, `roles.edit` y `roles.assign-permissions` para no perder la administración de roles.

## Despliegue y verificación

Ejecutar `php artisan migrate --force` para completar el catálogo sin resembrar roles. Si el despliegue cachea rutas o vistas, regenerar esas cachés. No ejecutar el seeder de roles para reparar permisos faltantes: ese seeder establece asignaciones iniciales.

`tests/Feature/Access/MenuPermissionAuditTest.php` comprueba cada enlace del menú con los permisos de su ruta, disponibilidad de todo el catálogo en Roles, asignación y revocación, consulta independiente de escritura, empresa seleccionada y protección de permisos del sistema.

Resultado final de las pruebas focalizadas: **79 aprobadas, 1392 aserciones**, incluyendo Accesos, Roles, Académico y filtros de Personal. Plantillas Blade compiladas correctamente y revisión de diferencias sin errores de formato.

La ejecución de la suite completa realizada durante la revisión no quedó en verde: registró 3 fallos y 67 errores, principalmente permisos del filesystem de pruebas, una referencia a `users.change-password` que no existe y expectativas de pruebas de usuarios/personal. Esos resultados no deben confundirse con la validación focalizada de accesos; requieren una revisión aparte.


## Gestión de todos los módulos

Menú separado en Académico, protegido por `all-modules.manage` (Gestión de todos los módulos → Administrar). Incluye los cursos abiertos y su historial, notas diarias y únicas, observaciones, centralizadores, PDF y cierre de cursos. No requiere perfil docente del operador. El alcance se limita a la empresa activa; no modifica la asignación de docentes ni amplía el acceso de «Mis módulos».

Las nuevas clases conservan el docente asignado al módulo y registran al operador en `started_by`; las notas y cierres también identifican al usuario que realizó la acción. Para iniciar clases se requiere un docente asignado. Se mantienen las validaciones de fechas, matrícula y cierre existentes.

Registrar el permiso mediante `php artisan migrate --force`. Se habilita inicialmente para los roles admin y super_admin; los demás roles lo reciben solo si se asigna desde Roles.
