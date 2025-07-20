<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="{{ route('inventory.dashboard') }}">
            <img src="{{ asset('modules/inventory/img/logo-ct-dark.png') }}" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-1 font-weight-bold">ERP Inventory</span>
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('inventory.dashboard') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-tachometer-alt text-dark text-gradient text-lg"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>

            <!-- Inventory Management -->
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#inventoryMenu" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-boxes text-dark text-gradient text-lg"></i>
                    </div>
                    <span class="nav-link-text ms-1">Inventory</span>
                </a>
                <div class="collapse" id="inventoryMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('inventory.manufacturers.list') }}">
                                <i class="fas fa-industry text-xs"></i>
                                <span class="nav-link-text ms-1">Manufacturers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('inventory.brands.list') }}">
                                <i class="fas fa-industry text-xs"></i>
                                <span class="nav-link-text ms-1">Brands</span>
                            </a>
                        </li>
                        <!-- Raw Materials -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#rawMaterialsMenu" role="button" aria-expanded="false">
                                <i class="fas fa-cube text-sm"></i>
                                <span class="nav-link-text ms-1">Raw Materials</span>
                            </a>
                            <div class="collapse" id="rawMaterialsMenu">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.raw-materials.list') }}">
                                            <i class="fas fa-list text-xs"></i>
                                            <span class="nav-link-text ms-1">All Raw Materials</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.raw-materials.categories.list') }}">
                                            <i class="fas fa-tags text-xs"></i>
                                            <span class="nav-link-text ms-1">Categories</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.raw-materials.attributes') }}">
                                            <i class="fas fa-cogs text-xs"></i>
                                            <span class="nav-link-text ms-1">Attributes</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Products -->
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#productsMenu" role="button" aria-expanded="false">
                                <i class="fas fa-box text-sm"></i>
                                <span class="nav-link-text ms-1">Products</span>
                            </a>
                            <div class="collapse" id="productsMenu">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.products.list') }}">
                                            <i class="fas fa-list text-xs"></i>
                                            <span class="nav-link-text ms-1">All Products</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.products.categories.list') }}">
                                            <i class="fas fa-tags text-xs"></i>
                                            <span class="nav-link-text ms-1">Categories</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('inventory.products.attributes') }}">
                                            <i class="fas fa-cogs text-xs"></i>
                                            <span class="nav-link-text ms-1">Attributes</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Sales & Orders -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('sales.index') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-shopping-cart text-dark text-gradient text-lg"></i>
                    </div>
                    <span class="nav-link-text ms-1">Sales & Orders</span>
                </a>
            </li>

            <!-- Suppliers -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('inventory.suppliers') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-truck text-dark text-gradient text-lg"></i>
                    </div>
                    <span class="nav-link-text ms-1">Suppliers</span>
                </a>
            </li>

            <!-- Reports -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('reports.index') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-chart-bar text-dark text-gradient text-lg"></i>
                    </div>
                    <span class="nav-link-text ms-1">Reports</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
