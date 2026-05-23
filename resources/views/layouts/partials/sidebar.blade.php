@php
    $catalogOpen = request()->routeIs('products.*', 'product-presentations.*', 'categories.*', 'measurement-units.*', 'payment-methods.*', 'suppliers.*');
    $operationsOpen = request()->routeIs('companies.*', 'branches.*', 'warehouses.*', 'point-of-sales.*');
    $inventoryOpen = request()->routeIs('inventory.*', 'purchases.*');
    $salesOpen = request()->routeIs('pos.*', 'sales.*');
    $reportsOpen = request()->routeIs('reports.*');
    $adminOpen = request()->routeIs('users.*', 'roles.*', 'permissions.*', 'audits.*');

    $canCatalog = auth()->user()?->can('products.view')
        || auth()->user()?->can('product-presentations.view')
        || auth()->user()?->can('categories.view')
        || auth()->user()?->can('measurement-units.view')
        || auth()->user()?->can('payment-methods.view')
        || auth()->user()?->can('suppliers.view');
    $canOperations = auth()->user()?->can('companies.view')
        || auth()->user()?->can('branches.view')
        || auth()->user()?->can('warehouses.view')
        || auth()->user()?->can('point-of-sales.view');
    $canInventory = auth()->user()?->can('inventory.view')
        || auth()->user()?->can('purchases.view');
    $canSales = auth()->user()?->can('pos.access')
        || auth()->user()?->can('sales.view');
    $canReports = auth()->user()?->can('reports.view');
    $canAdmin = auth()->user()?->can('users.view')
        || auth()->user()?->can('roles.view')
        || auth()->user()?->can('permissions.view')
        || auth()->user()?->can('audits.view');
    $sidebarCompany = \App\Support\CompanyContext::activeCompany(auth()->user());
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg app-sidebar" id="adminSidebar" data-bs-theme="dark">
    <div class="container-fluid">
        <h1 class="navbar-brand navbar-brand-autodark justify-content-start">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="lh-sm">
                    <span class="d-block text-truncate">{{ $sidebarCompany?->name ?? 'Inventario POS' }}</span>
                    @if ($sidebarCompany)
                        <span class="d-block small text-muted">Inventario POS</span>
                    @endif
                </span>
            </a>
        </h1>

        <div class="navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-dashboard"></i></span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                @if ($canSales)
                    <li class="nav-item app-menu-section {{ $salesOpen ? 'active' : '' }}">
                        <button class="nav-link app-menu-toggle {{ $salesOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#menu-sales" aria-expanded="{{ $salesOpen ? 'true' : 'false' }}" aria-controls="menu-sales">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-device-desktop-dollar"></i></span>
                            <span class="nav-link-title">Ventas</span>
                            <span class="menu-chevron"><i class="ti ti-chevron-down"></i></span>
                        </button>
                        <div class="collapse {{ $salesOpen ? 'show' : '' }}" id="menu-sales">
                            <ul class="nav app-submenu">
                                @can('pos.access')
                                    <li class="nav-item {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('pos.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-cash-register"></i></span>
                                            <span class="nav-link-title">Punto de venta</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('sales.view')
                                    <li class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('sales.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-receipt"></i></span>
                                            <span class="nav-link-title">Cajas y ventas</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endif

                @if ($canInventory)
                    <li class="nav-item app-menu-section {{ $inventoryOpen ? 'active' : '' }}">
                        <button class="nav-link app-menu-toggle {{ $inventoryOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#menu-inventory" aria-expanded="{{ $inventoryOpen ? 'true' : 'false' }}" aria-controls="menu-inventory">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building-warehouse"></i></span>
                            <span class="nav-link-title">Inventario</span>
                            <span class="menu-chevron"><i class="ti ti-chevron-down"></i></span>
                        </button>
                        <div class="collapse {{ $inventoryOpen ? 'show' : '' }}" id="menu-inventory">
                            <ul class="nav app-submenu">
                                @can('inventory.view')
                                    <li class="nav-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('inventory.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-transfer"></i></span>
                                            <span class="nav-link-title">Stock</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ request()->routeIs('inventory.kardex') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('inventory.kardex') }}">
                                            <span class="nav-link-icon"><i class="ti ti-history"></i></span>
                                            <span class="nav-link-title">Kardex</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('purchases.view')
                                    <li class="nav-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('purchases.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-shopping-cart"></i></span>
                                            <span class="nav-link-title">Compras</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endif

                @if ($canReports)
                    <li class="nav-item {{ $reportsOpen ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('reports.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-report-analytics"></i></span>
                            <span class="nav-link-title">Reportes</span>
                        </a>
                    </li>
                @endif

                @if ($canCatalog)
                    <li class="nav-item app-menu-section {{ $catalogOpen ? 'active' : '' }}">
                        <button class="nav-link app-menu-toggle {{ $catalogOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#menu-catalog" aria-expanded="{{ $catalogOpen ? 'true' : 'false' }}" aria-controls="menu-catalog">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-package"></i></span>
                            <span class="nav-link-title">Catalogos</span>
                            <span class="menu-chevron"><i class="ti ti-chevron-down"></i></span>
                        </button>
                        <div class="collapse {{ $catalogOpen ? 'show' : '' }}" id="menu-catalog">
                            <ul class="nav app-submenu">
                                @can('products.view')
                                    <li class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('products.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-package"></i></span>
                                            <span class="nav-link-title">Productos</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('product-presentations.view')
                                    <li class="nav-item {{ request()->routeIs('product-presentations.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('product-presentations.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-packages"></i></span>
                                            <span class="nav-link-title">Presentaciones</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('categories.view')
                                    <li class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('categories.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-category"></i></span>
                                            <span class="nav-link-title">Categorias</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('measurement-units.view')
                                    <li class="nav-item {{ request()->routeIs('measurement-units.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('measurement-units.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-ruler-measure"></i></span>
                                            <span class="nav-link-title">Unidades</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('payment-methods.view')
                                    <li class="nav-item {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('payment-methods.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-credit-card"></i></span>
                                            <span class="nav-link-title">Metodos de pago</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('suppliers.view')
                                    <li class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('suppliers.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-truck-delivery"></i></span>
                                            <span class="nav-link-title">Proveedores</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endif

                @if ($canOperations)
                    <li class="nav-item app-menu-section {{ $operationsOpen ? 'active' : '' }}">
                        <button class="nav-link app-menu-toggle {{ $operationsOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#menu-operations" aria-expanded="{{ $operationsOpen ? 'true' : 'false' }}" aria-controls="menu-operations">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building-store"></i></span>
                            <span class="nav-link-title">Operaciones</span>
                            <span class="menu-chevron"><i class="ti ti-chevron-down"></i></span>
                        </button>
                        <div class="collapse {{ $operationsOpen ? 'show' : '' }}" id="menu-operations">
                            <ul class="nav app-submenu">
                                @can('companies.view')
                                    <li class="nav-item {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('companies.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-building"></i></span>
                                            <span class="nav-link-title">Empresas</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('branches.view')
                                    <li class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('branches.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-building-store"></i></span>
                                            <span class="nav-link-title">Sucursales</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('warehouses.view')
                                    <li class="nav-item {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('warehouses.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-building-warehouse"></i></span>
                                            <span class="nav-link-title">Almacenes</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('point-of-sales.view')
                                    <li class="nav-item {{ request()->routeIs('point-of-sales.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('point-of-sales.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-cash-register"></i></span>
                                            <span class="nav-link-title">Puntos de venta</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endif

                @if ($canAdmin)
                    <li class="nav-item app-menu-section {{ $adminOpen ? 'active' : '' }}">
                        <button class="nav-link app-menu-toggle {{ $adminOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#menu-admin" aria-expanded="{{ $adminOpen ? 'true' : 'false' }}" aria-controls="menu-admin">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-settings"></i></span>
                            <span class="nav-link-title">Administracion</span>
                            <span class="menu-chevron"><i class="ti ti-chevron-down"></i></span>
                        </button>
                        <div class="collapse {{ $adminOpen ? 'show' : '' }}" id="menu-admin">
                            <ul class="nav app-submenu">
                                @can('users.view')
                                    <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('users.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-users"></i></span>
                                            <span class="nav-link-title">Usuarios</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('roles.view')
                                    <li class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('roles.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-lock-access"></i></span>
                                            <span class="nav-link-title">Roles</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('permissions.view')
                                    <li class="nav-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('permissions.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-shield-check"></i></span>
                                            <span class="nav-link-title">Permisos</span>
                                        </a>
                                    </li>
                                @endcan

                                @can('audits.view')
                                    <li class="nav-item {{ request()->routeIs('audits.*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ route('audits.index') }}">
                                            <span class="nav-link-icon"><i class="ti ti-list-search"></i></span>
                                            <span class="nav-link-title">Auditoria</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                @endif

            </ul>
        </div>
    </div>
</aside>
