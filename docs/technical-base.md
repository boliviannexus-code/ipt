# Base Administrativa - Base Tecnica

## Stack

- PHP 8.3+
- Laravel 13
- Laravel Sail con runtime PHP 8.5
- PostgreSQL 17
- Redis 8
- Docker Compose
- Blade + Bootstrap 5
- CoreUI
- Laravel Sanctum
- Spatie Laravel Permission

## Arquitectura inicial

La base sigue MVC con controladores delgados, validacion por Form Requests, salida API mediante Resources, reglas de autorizacion con Policies, logica de negocio en Services y acceso a datos en Repositories.

```txt
app/
  Http/Controllers/Web
  Http/Controllers/Api/V1
  Http/Requests
  Http/Resources
  Services
  Repositories
  Policies
  Traits
  Enums
  Helpers
resources/views/
  components/admin
  components/ui
  layouts
```

## Modulos implementados

- Autenticacion web inicial.
- Autenticacion API por token Sanctum.
- Roles y permisos iniciales.
- Empresas con CRUD web.
- Auditoria basica.
- Registro y aprobacion de alojamientos.
- Catalogos globales de alojamientos.
- Dashboard administrativo base.
- Layout administrativo CoreUI reutilizable.
- Componentes Blade para sidebar, header, alerts, cards, stats, tablas y formularios.

## Frontend administrativo

El panel usa CoreUI sobre Bootstrap 5 mediante Vite. La estructura esta preparada para Livewire futuro porque el layout centraliza el shell visual y los componentes Blade encapsulan piezas repetibles.

- `resources/views/layouts/admin.blade.php`: shell administrativo.
- `resources/views/components/admin/sidebar.blade.php`: sidebar responsive.
- `resources/views/components/admin/header.blade.php`: navbar superior.
- `resources/views/components/admin/flash.blade.php`: alertas del sistema.
- `resources/views/components/ui/card.blade.php`: tarjeta base.
- `resources/views/components/ui/table-card.blade.php`: tabla responsive.
- `resources/views/components/ui/form-panel.blade.php`: panel de formulario.
- `resources/views/components/ui/stat-card.blade.php`: metrica de dashboard.

## Usuario inicial

```txt
Email: boliviannexus@gmail.com
Password: configurado en `DatabaseSeeder`
Rol: super_admin
```

## Formato API

Todas las respuestas controladas usan:

```json
{
  "success": true,
  "message": "Operación realizada correctamente",
  "data": {}
}
```

## Rutas principales

Web:

- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /`
- `resource /companies`
- `resource /users`
- `resource /roles`
- `resource /permissions`
- `GET /audits`
- `GET /spaces`

API:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `apiResource /api/v1/users`
- `apiResource /api/v1/roles`
- `apiResource /api/v1/permissions`

## Modulos retirados para reconstruccion

Se retiraron artefactos incompletos de sucursales, almacenes, productos, clientes, ventas, POS, compras, inventario, metodos de pago y reportes para evitar dependencias rotas. Esos dominios deben reingresar mediante migraciones, modelos, servicios, rutas y pruebas nuevas cuando se defina la arquitectura modular de facturacion.

## Siguiente paso recomendado

Crear desde cero los modulos comerciales minimos antes de integrar SIAT.

## Entorno local

La infraestructura de desarrollo se define unicamente en `compose.yaml` y se
opera mediante Laravel Sail:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
```

Los servicios internos se resuelven mediante los hosts `pgsql`, `redis` y
`mailpit`. El proyecto no usa un `Dockerfile` propio. Los comandos Artisan se
ejecutan con `./vendor/bin/sail artisan`; la ejecucion directa con `php artisan`
desde el host no forma parte del entorno soportado.
