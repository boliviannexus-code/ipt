<?php

if (! function_exists('money_format_decimal')) {
    function money_format_decimal(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}

if (! function_exists('role_label')) {
    function role_label(string $name): string
    {
        $labels = [
            'admin' => 'Administrador',
            'super_admin' => 'Super administrador',
            'manager' => 'Gerente',
            'cashier' => 'Cajero',
            'warehouse' => 'Almacen',
            'inventory_manager' => 'Encargado de inventario',
            'viewer' => 'Visualizador',
        ];

        return $labels[$name] ?? str($name)->replace(['-', '_'], ' ')->headline()->toString();
    }
}

if (! function_exists('permission_module_label')) {
    function permission_module_label(string $module): string
    {
        $labels = [
            'dashboard' => 'Panel principal',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'permissions' => 'Permisos',
            'branches' => 'Sucursales',
            'warehouses' => 'Almacenes',
            'point-of-sales' => 'Puntos de venta',
            'suppliers' => 'Proveedores',
            'products' => 'Productos',
            'product-presentations' => 'Presentaciones de producto',
            'categories' => 'Categorias',
            'measurement-units' => 'Unidades de medida',
            'payment-methods' => 'Metodos de pago',
            'inventory' => 'Inventario',
            'purchases' => 'Compras',
            'sales' => 'Ventas',
            'pos' => 'Punto de venta',
            'reports' => 'Reportes',
        ];

        return $labels[$module] ?? str($module)->replace(['-', '_'], ' ')->headline()->toString();
    }
}

if (! function_exists('permission_action_label')) {
    function permission_action_label(string $action): string
    {
        $labels = [
            'view' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'restore' => 'Restaurar',
            'change-password' => 'Cambiar contrasena',
            'assign-roles' => 'Asignar roles',
            'assign-permissions' => 'Asignar permisos',
            'movements' => 'Registrar movimientos',
            'access' => 'Acceder',
            'manage' => 'Administrar',
        ];

        return $labels[$action] ?? str($action)->replace(['-', '_'], ' ')->headline()->toString();
    }
}

if (! function_exists('permission_label')) {
    function permission_label(string $name): string
    {
        if (! str_contains($name, '.')) {
            return permission_action_label($name);
        }

        [$module, $action] = explode('.', $name, 2);

        return permission_module_label($module).': '.permission_action_label($action);
    }
}
