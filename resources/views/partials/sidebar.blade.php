{{-- resources/views/layouts/partials/sidebar.blade.php --}}
{{-- SB Admin 2 Sidebar (Bootstrap 5) + Sidebar Search + Mobile Overlay + Finance Optimisation + Collapse-all on search clear --}}

{{-- ===================== 1) STYLES ===================== --}}
<style>
  :root{
    --sb-sidebar-width: 14rem;
    --sb-sidebar-width-mobile: 16rem;
    --sb-sidebar-collapsed-width: 6.5rem;
  }

  .collapse-inner .collapse-item{
    display:flex;
    align-items:center;
    gap:.55rem;
    white-space: nowrap;
  }

  .collapse-inner .collapse-item i{
    width:18px;
    text-align:center;
    opacity:.85;
    flex: 0 0 18px;
  }

  /* Overlay hidden by default */
  .sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    z-index:1040;
  }

  /* ===== COMMON SIDEBAR SCROLL RULES ===== */
  .sidebar{
    height:100vh !important;
    overflow-y:auto !important;
    overflow-x:hidden !important;
    -webkit-overflow-scrolling: touch;
  }

  /* MOBILE ONLY */
  @media (max-width: 767.98px){
    body.sidebar-mobile-open .sidebar-overlay{
      display:block;
    }

    .sidebar{
      position:fixed !important;
      top:0;
      left:0;
      width:var(--sb-sidebar-width-mobile) !important;
      transform:translateX(-105%);
      transition:transform .2s ease-in-out;
      z-index:1050;
    }

    .sidebar.mobile-open{
      transform:translateX(0);
    }
  }

  /* DESKTOP */
  @media (min-width: 768px){
    .sidebar{
      position:fixed !important;
      top:0;
      left:0;
      width:var(--sb-sidebar-width) !important;
      z-index:1030;
    }

    body.sidebar-toggled .sidebar{
      width:var(--sb-sidebar-collapsed-width) !important;
      overflow-y:auto !important;
      overflow-x:hidden !important;
    }

    /* Never show overlay on desktop */
    .sidebar-overlay{
      display:none !important;
    }
  }

  .finance-subtoggle{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:.35rem .75rem;
    margin:.15rem .25rem;
    border-radius:.35rem;
    color:#4e73df;
    cursor:pointer;
    font-weight:700;
    user-select:none;
  }

  .finance-subtoggle:hover{
    background:rgba(78,115,223,.08);
  }

  .finance-submenu{
    padding-left:.15rem;
    padding-right:.15rem;
  }
</style>

{{-- ===================== 2) MOBILE OVERLAY ===================== --}}
<div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

{{-- ===================== 3) SIDEBAR ===================== --}}
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

  {{-- Brand --}}
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard.index') }}">
    <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-laugh-wink"></i></div>
    <div class="sidebar-brand-text mx-3">{{ env('APP_NAME','Simply-ERP') }}</div>
  </a>

  <hr class="sidebar-divider my-0">

  {{-- Sidebar Search --}}
  <li class="nav-item px-3 mt-3 mb-2">
    <div class="sidebar-search-wrap">
      <i class="fas fa-search sidebar-search-icon"></i>

      <input
        type="text"
        id="sidebarSearch"
        class="form-control form-control-sm sidebar-search-input"
        placeholder="Search menu..."
        autocomplete="off"
      />

      <button
        type="button"
        id="sidebarSearchClear"
        class="sidebar-search-clear d-none"
        aria-label="Clear search"
      >
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div id="sidebarSearchEmpty" class="sidebar-search-empty mt-2 d-none">
      No matches.
    </div>
  </li>

  {{-- Dashboard --}}
  <li class="nav-item">
    <a class="nav-link" href="{{ route('admin.dashboard.index') }}">
      <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
    </a>
    <a class="nav-link" href="{{ route('admin.dashboard.ceo') }}">
        <i class="fas fa-chalkboard"></i>
        <span>CEO Dashboard</span>
    </a>
    <a class="nav-link" href="{{ route('admin.dashboard.cfo') }}">
        <i class="fas fa-coins"></i>
        <span>CFO Dashboard</span>
    </a>
    <a class="nav-link" href="{{ route('admin.dashboard.coo') }}">
        <i class="fas fa-industry"></i>
        <span>COO Dashboard</span>
    </a>
    <a class="nav-link" href="{{ route('admin.control_center') }}">
        <i class="fas fa-satellite-dish"></i>
        <span>Control Center</span>
    </a>
    <a class="nav-link" href="{{ route('admin.notifications.index') }}">
        <i class="fas fa-bell"></i>
        <span>Notifications</span>
    </a>
  </li>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">Core ERP</div>

  {{-- ===================== Geography ===================== --}}
  @php
    $geoPerms = [
      'core.geography.regions.view',
      'core.geography.subregions.view',
      'core.geography.countries.view',
      'core.geography.states.view',
      'core.geography.cities.view',
    ];
  @endphp

  @canany($geoPerms)
    <li class="nav-item">
      <a href="#" class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseGeography"
         aria-expanded="false" aria-controls="collapseGeography">
        <i class="fas fa-map-marked-alt"></i><span>Geography</span>
      </a>

      <div id="collapseGeography" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.geography.regions.view')
            <a class="collapse-item" href="{{ route('admin.regions.index') }}">
              <i class="fas fa-draw-polygon"></i><span>Regions</span>
            </a>
          @endcan

          @can('core.geography.subregions.view')
            <a class="collapse-item" href="{{ route('admin.subregions.index') }}">
              <i class="fas fa-bezier-curve"></i><span>Sub-regions</span>
            </a>
          @endcan

          @can('core.geography.countries.view')
            <a class="collapse-item" href="{{ route('admin.countries.index') }}">
              <i class="fas fa-flag"></i><span>Countries</span>
            </a>
          @endcan

          @can('core.geography.states.view')
            <a class="collapse-item" href="{{ route('admin.states.index') }}">
              <i class="fas fa-map"></i><span>States</span>
            </a>
          @endcan

          @can('core.geography.cities.view')
            <a class="collapse-item" href="{{ route('admin.cities.index') }}">
              <i class="fas fa-city"></i><span>Cities</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===================== Locations ===================== --}}
  @canany([
    'core.locations.types.view',
    'core.locations.locations.view',
    'core.locations.blocks.view',
    'core.locations.floors.view',
    'core.locations.rooms.view'
  ])
    <li class="nav-item">
      <a href="#" class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseLocation">
        <i class="fas fa-building"></i><span>Locations</span>
      </a>
      <div id="collapseLocation" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.locations.types.view')
            <a class="collapse-item" href="{{ route('admin.location_types.index') }}">
              <i class="fas fa-tags"></i><span>Location Types</span>
            </a>
          @endcan
          @can('core.locations.locations.view')
            <a class="collapse-item" href="{{ route('admin.locations.index') }}">
              <i class="fas fa-map-marker-alt"></i><span>Locations</span>
            </a>
          @endcan
          @can('core.locations.blocks.view')
            <a class="collapse-item" href="{{ route('admin.location_blocks.index') }}">
              <i class="fas fa-th-large"></i><span>Blocks</span>
            </a>
          @endcan
          @can('core.locations.floors.view')
            <a class="collapse-item" href="{{ route('admin.location_floors.index') }}">
              <i class="fas fa-layer-group"></i><span>Floors</span>
            </a>
          @endcan
          @can('core.locations.rooms.view')
            <a class="collapse-item" href="{{ route('admin.location_rooms.index') }}">
              <i class="fas fa-door-open"></i><span>Rooms</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===================== Storage ===================== --}}
  @canany(['core.storage.stores.view','core.storage.shelves.view'])
    <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStorage">
        <i class="fas fa-warehouse"></i><span>Storage</span>
      </a>
      <div id="collapseStorage" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.storage.stores.view')
            <a class="collapse-item" href="{{ route('admin.location_stores.index') }}">
              <i class="fas fa-store"></i><span>Stores</span>
            </a>
          @endcan
          @can('core.storage.shelves.view')
            <a class="collapse-item" href="{{ route('admin.store_shelves.index') }}">
              <i class="fas fa-grip-lines"></i><span>Shelves</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===================== Core Settings ===================== --}}
  @canany(['core.master_data.customers.view','core.master_data.companies.view'])
    <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings">
        <i class="fas fa-cogs"></i><span>Core Settings</span>
      </a>
      <div id="collapseSettings" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.master_data.customers.view')
            <a class="collapse-item" href="{{ route('admin.settings.index') }}">
              <i class="fas fa-sliders-h"></i><span>All Settings</span>
            </a>
          @endcan
          @can('core.master_data.companies.view')
            <a class="collapse-item" href="{{ route('admin.setting_groups.index') }}">
              <i class="fas fa-layer-group"></i><span>Setting Groups</span>
            </a>
          @endcan
          <a class="collapse-item" href="{{ route('admin.workflows.index') }}">
              <i class="fas fa-random"></i>
              <span>Workflow Automation</span>
          </a>
          @can('finance.data_flush.view')
            <a class="collapse-item" href="{{ route('admin.finance.data_flush.index') }}">
              <i class="fas fa-broom me-1 text-danger"></i><span class="text-danger">Flush Finance Data </span>
            </a>
          @endcan
          @can('inventory.flush.view')
            <a class="collapse-item" href="{{ route('admin.inventory.flush.index') }}">
              <i class="fas fa-broom me-1 text-danger"></i><span class="text-danger">Flush Inventory Data</span>
            </a>
          @endcan
          @can('sales.flush.manage')
            <a class="collapse-item {{ request()->routeIs('admin.sales.data-flush.*') ? 'active' : '' }}"
             href="{{ route('admin.sales.data-flush.index') }}">
              <i class="fas fa-broom fa-fw me-1 text-danger"></i><span class="text-danger">Flush Sales Data</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===================== Master Data ===================== --}}
  @canany([
    'core.master_data.customers.view',
    'core.master_data.companies.view',
    'core.master_data.departments.view',
    'core.master_data.users.view'
  ])
    <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMasterData">
        <i class="fas fa-database"></i><span>Master Data</span>
      </a>
      <div id="collapseMasterData" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.master_data.customers.view')
            <a class="collapse-item" href="{{ route('admin.customers.index') }}">
              <i class="fas fa-user-tag"></i><span>Customers</span>
            </a>
          @endcan
          @can('core.master_data.companies.view')
            <a class="collapse-item" href="{{ route('admin.companies.index') }}">
              <i class="fas fa-building"></i><span>Companies</span>
            </a>
          @endcan
          @can('core.master_data.departments.view')
            <a class="collapse-item" href="{{ route('admin.companies.departments.index') }}">
              <i class="fas fa-sitemap"></i><span>Departments</span>
            </a>
          @endcan
          @can('core.master_data.users.view')
            <a class="collapse-item" href="{{ route('admin.users.index') }}">
              <i class="fas fa-users"></i><span>Users</span>
            </a>
          @endcan

          @can('core.master_data.vehicles.view')
            <a class="collapse-item" href="{{ route('admin.vehicles.index') }}">
              <i class="fas fa-car"></i><span>Vehicles</span>
            </a>
          @endcan
          @can('core.master_data.drivers.view')
            <a class="collapse-item" href="{{ route('admin.drivers.index') }}">
              <i class="fas fa-user-tie"></i><span>Drivers</span>
            </a>
          @endcan

          @can('core.master_data.suppliers.view')
            <a class="collapse-item" href="{{ route('admin.suppliers.index') }}">
              <i class="fas fa-people-carry"></i><span>Suppliers</span>
            </a>
            <a class="collapse-item" href="{{ route('admin.suppliers.addresses.index') }}">
              <i class="fas fa-map-marker-alt"></i><span>Supplier Addresses</span>
            </a>
            <a class="collapse-item" href="{{ route('admin.suppliers.contacts.index') }}">
              <i class="fas fa-user-friends"></i><span>Supplier Contacts</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- Audit --}}
  @can('core.audit.view')
    <li class="nav-item">
      <a class="nav-link" href="{{ route('admin.audit.index') }}">
        <i class="fas fa-user-shield"></i><span>Audit Logs</span>
      </a>
    </li>
  @endcan

  @can('core.audit.view.analytics')
    <li class="nav-item">
      <a class="nav-link" href="{{ route('admin.audit.analytics') }}">
        <i class="fas fa-chart-line"></i><span>Audit Analytics</span>
      </a>
    </li>
  @endcan

  {{-- Parameters --}}
  @canany([
    'core.parameters.customers.view',
    'core.parameters.companies.view',
    'core.parameters.modules.view',
    'core.parameters.roles.view',
    'core.parameters.permissions.view',
    'core.parameters.departments.view',
    'core.parameters.users.view'
  ])
    <li class="nav-item">
      <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseParameters">
        <i class="fas fa-sliders-h"></i><span>Parameters</span>
      </a>
      <div id="collapseParameters" class="collapse" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          @can('core.parameters.customers.view')
            <a class="collapse-item" href="{{ route('admin.customers.index') }}">
              <i class="fas fa-user-tag"></i><span>Customers</span>
            </a>
          @endcan
          @can('core.parameters.companies.view')
            <a class="collapse-item" href="{{ route('admin.companies.index') }}">
              <i class="fas fa-building"></i><span>Companies</span>
            </a>
          @endcan
          @can('core.parameters.departments.view')
            <a class="collapse-item" href="{{ route('admin.companies.departments.index') }}">
              <i class="fas fa-sitemap"></i><span>Departments</span>
            </a>
          @endcan
          @can('core.parameters.modules.view')
            <a class="collapse-item" href="{{ route('admin.modules.index') }}">
              <i class="fas fa-puzzle-piece"></i><span>Modules</span>
            </a>
          @endcan
          @can('core.parameters.permissions.view')
            <a class="collapse-item" href="{{ route('admin.permissions.index') }}">
              <i class="fas fa-key"></i><span>Permissions</span>
            </a>
          @endcan
          @can('core.parameters.roles.view')
            <a class="collapse-item" href="{{ route('admin.roles.index') }}">
              <i class="fas fa-user-check"></i><span>Roles</span>
            </a>
          @endcan
          @can('core.parameters.users.view')
            <a class="collapse-item" href="{{ route('admin.users.index') }}">
              <i class="fas fa-users"></i><span>Users</span>
            </a>
          @endcan
        </div>
      </div>
    </li>
  @endcanany

  {{-- ===================== Inventory ===================== --}}
  @if(canAccessModule('inventory'))
    @canany([
      'inventory.products.units.view',
      'inventory.products.manufacturers.view',
      'inventory.products.brands.view',
      'inventory.products.attribute_types.view',
      'inventory.products.attributes.view',
      'inventory.products.attribute_values.view',
      'inventory.products.categories.view',
      'inventory.products.view',
      'inventory.products.variants.view',
      'inventory.stock.entries.view',
      'inventory.stock.transactions.view',
      'inventory.stock.levels.view',
      'inventory.stock.levels.low.view',
      'inventory.stock.aging.view',
      'inventory.stock.transfers.view',
      'inventory.stock.issues.view',
      'inventory.stock.dashboard.view'
    ])
      <div class="sidebar-heading">Inventory Management</div>
    @endcanany

    {{-- Products --}}
    @canany([
      'inventory.products.units.view',
      'inventory.products.manufacturers.view',
      'inventory.products.brands.view',
      'inventory.products.attribute_types.view',
      'inventory.products.attributes.view',
      'inventory.products.attribute_values.view',
      'inventory.products.categories.view',
      'inventory.products.view',
      'inventory.products.variants.view'
    ])
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts">
          <i class="fas fa-boxes"></i><span>Products</span>
        </a>
        <div id="collapseProducts" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('inventory.products.units.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.units.index') }}">
                <i class="fas fa-ruler-combined"></i><span>Units</span>
              </a>
            @endcan
            @can('inventory.products.manufacturers.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.manufacturers.list') }}">
                <i class="fas fa-industry"></i><span>Manufacturers</span>
              </a>
            @endcan
            @can('inventory.products.brands.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.brands.list') }}">
                <i class="fas fa-copyright"></i><span>Brands</span>
              </a>
            @endcan
            @can('inventory.products.attribute_types.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.types.index') }}">
                <i class="fas fa-layer-group"></i><span>Attribute Types</span>
              </a>
            @endcan
            @can('inventory.products.attributes.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.index') }}">
                <i class="fas fa-sliders-h"></i><span>Attributes</span>
              </a>
            @endcan
            @can('inventory.products.attribute_values.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.values.index') }}">
                <i class="fas fa-list-ul"></i><span>Attribute Values</span>
              </a>
            @endcan
            @can('inventory.products.categories.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.categories.index') }}">
                <i class="fas fa-sitemap"></i><span>Categories</span>
              </a>
            @endcan
            @can('inventory.products.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.index') }}">
                <i class="fas fa-box"></i><span>Products</span>
              </a>
            @endcan
            @can('inventory.products.variants.view')
              <a class="collapse-item" href="{{ route('admin.inventory.products.variants.index') }}">
                <i class="fas fa-cubes"></i><span>Product Variants</span>
              </a>
            @endcan

            <a class="collapse-item" href="{{ route('admin.inventory.products.docs.workflow') }}">
              <i class="fas fa-book"></i><span>SOP & Workflow</span>
            </a>
            <a class="collapse-item" href="{{ route('admin.inventory.products.docs.privileges') }}">
              <i class="fas fa-user-shield"></i><span>SOP & Privileges</span>
            </a>
          </div>
        </div>
      </li>
    @endcanany

    {{-- Stock Management --}}
    @canany([
      'inventory.stock.entries.view',
      'inventory.stock.entry_lines.view',
      'inventory.stock.transactions.view',
      'inventory.stock.levels.view',
      'inventory.stock.levels.low.view',
      'inventory.stock.aging.view',
      'inventory.stock.transfers.view',
      'inventory.stock.issues.view'
    ])
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStocks">
          <i class="fas fa-box-open"></i><span>Stock Management</span>
        </a>
        <div id="collapseStocks" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('inventory.stock.entries.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.index') }}">
                <i class="fas fa-arrow-down"></i><span>Stock Entries</span>
              </a>
            @endcan
            @can('inventory.stock.transactions.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.transactions.index') }}">
                <i class="fas fa-exchange-alt"></i><span>Stock Transactions</span>
              </a>
            @endcan
            @can('inventory.stock.levels.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.levels.index') }}">
                <i class="fas fa-layer-group"></i><span>Stock Levels</span>
              </a>
            @endcan
            @can('inventory.stock.levels.low.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.levels.low.index') }}">
                <i class="fas fa-exclamation-triangle"></i><span>Low Stock Levels</span>
              </a>
            @endcan
            @can('inventory.stock.aging.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.aging.index') }}">
                <i class="fas fa-hourglass-half"></i><span>Stock Aging</span>
              </a>
            @endcan
            @can('inventory.stock.transfers.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.transfers.index') }}">
                <i class="fas fa-random"></i><span>Stock Transfer</span>
              </a>
            @endcan
            @can('inventory.stock.issues.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock_issues.index') }}">
                <i class="fas fa-arrow-up"></i><span>Stock Issues</span>
              </a>
            @endcan
            @can('inventory.stock.workflow.view')
              <a class="collapse-item" href="{{ route('admin.inventory.workflow.index') }}">
                <i class="fas fa-route"></i><span>Work Flow Help</span>
              </a>
            @endcan
            @can('inventory.stock.workflow.sop.export')
              <a class="collapse-item" href="{{ route('admin.inventory.workflow.sop.index') }}">
                <i class="fas fa-print"></i><span>Print / Export SOP</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany

    {{-- Stock Dashboard --}}
    @can('inventory.stock.dashboard.view')
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseDashboards">
          <i class="fas fa-chart-bar"></i><span>Stock Dashboard</span>
        </a>
        <div id="collapseDashboards" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('inventory.stock_entries.analytics.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.analytics.index') }}">
                <i class="fas fa-chart-area"></i><span>Stock Entry</span>
              </a>
            @endcan
            @can('inventory.stock_inventory.analytics.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.dashboard.index') }}">
                <i class="fas fa-chart-pie"></i><span>Stock Inventory</span>
              </a>
              <a class="collapse-item" href="{{ route('admin.inventory.stock.levels.dashboard.index') }}">
                <i class="fas fa-chart-pie"></i><span>Stock Levels</span>
              </a>
            @endcan
            @can('inventory.stock.transfers.dashboard.view')
              <a class="collapse-item" href="{{ route('admin.inventory.stock.transfers.dashboard.index') }}">
                <i class="fas fa-random"></i><span>Stock Transfers</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcan

    {{-- Returns --}}
    @canany(['inventory.returns.customer.view','inventory.returns.supplier.view'])
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseReturns" href="#">
          <i class="fas fa-undo"></i><span>Returns</span>
        </a>
        <div id="collapseReturns" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('inventory.returns.customer.view')
              <a class="collapse-item" href="{{ route('admin.inventory.returns.customer.index') }}">
                <i class="fas fa-undo-alt"></i><span>Customer Returns</span>
              </a>
            @endcan
            @can('inventory.returns.supplier.view')
              <a class="collapse-item" href="{{ route('admin.inventory.returns.supplier.index') }}">
                <i class="fas fa-truck-loading"></i><span>Supplier Returns</span>
              </a>
              <a class="collapse-item" href="{{ route('admin.supplier_analytics.index') }}">
                <i class="fas fa-chart-bar"></i><span>Supp-Ret. Analytics</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== Finance (Optimised) ===================== --}}
  @if (canAccessModule('finance'))
    @canany([
      'finance.chart_of_accounts.view',
      'finance.accounts.mappings.view',
      'finance.bank_accounts.view',
      'finance.periods.view',
      'finance.journals.view',
      'finance.invoices.view',
      'finance.payments.view',
      'finance.expense_categories.view',
      'finance.expenses.view',
      'finance.ar.view',
      'finance.reports.ar_ageing.view',
      'finance.ap.view',
      'finance.ap.credits.view',
      'finance.ap.aging.view',
      'finance.bank_reconciliation.view',
      'finance.budgets.view',
      'finance.reports.general_ledger.view',
      'finance.reports.trial_balance.view',
      'finance.reports.profit_loss.view',
      'finance.reports.balance_sheet.view',
      'finance.reports.cash_flow.view',

      /* fixed assets perms you used */
      'finance.fixed_asset_categories.view',
      'finance.fixed_assets.view',
      'finance.fixed_asset_transfers.view',
      'finance.fixed_asset_depreciation.view',
      'finance.fixed_asset_components.view',
      'finance.fixed_asset_maintenance.view',
      'finance.asset_capitalisations.view',
      'finance.fixed_asset_revaluations.view',
      'finance.fixed_asset_impairments.view',
      'finance.fixed_asset_writeoffs.view',
      'finance.fixed_asset_reports.view'
    ])
      <div class="sidebar-heading">Finance</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFinance"
           aria-expanded="false" aria-controls="collapseFinance">
          <i class="fas fa-file-invoice-dollar"></i><span>Financials</span>
        </a>

        <div id="collapseFinance" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">

            {{-- Setup --}}
            <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finSetup">
              <span>Setup</span><i class="fas fa-chevron-down small"></i>
            </div>
            <div id="finSetup" class="collapse finance-submenu">
                <div class="sidebar-heading text-black my-1">Acc. Setup</div>
              @can('finance.chart_of_accounts.view')
              <a class="collapse-item" href="{{ route('admin.finance.account_types.index') }}">
                  <i class="fas fa-sitemap me-1"></i>
                  <span>Account Types</span>
              </a>
              @endcan
              @can('finance.chart_of_accounts.view')
                <a class="collapse-item" href="{{ route('admin.finance.chart_of_accounts.index') }}">
                  <i class="fas fa-book"></i><span>Chart of Accounts</span>
                </a>
              @endcan
              @can('finance.accounts.mappings.view')
                <a class="collapse-item" href="{{ route('admin.finance.accounts.mappings.index') }}">
                  <i class="fas fa-random"></i><span>Account Mappings</span>
                </a>
              @endcan
              @can('finance.bank_accounts.view')
                <a class="collapse-item" href="{{ route('admin.finance.bank_accounts.index') }}">
                  <i class="fas fa-university"></i><span>Bank &amp; Cash Acc.</span>
                </a>
              @endcan
              @can('finance.periods.view')
                <a class="collapse-item" href="{{ route('admin.finance.periods.index') }}">
                  <i class="fas fa-calendar-alt"></i><span>Fiscal Periods</span>
                </a>
                <a class="collapse-item" href="{{ route('admin.finance.year_close.index') }}">
                  <i class="fas fa-calendar"></i><span>Year Close</span>
                </a>
              @endcan
              {{-- TAX CONFIGURATION --}}
                <div class="sidebar-heading text-black my-1">Tax Setup</div>
                
                @can('finance.tax.rates.view')
                <a class="collapse-item" href="{{ route('admin.finance.tax.rates.index') }}">
                  <i class="fas fa-percentage me-1"></i>
                  <span>Tax Rates</span>
                </a>
                @endcan
                
                @can('finance.tax.codes.view')
                <a class="collapse-item" href="{{ route('admin.finance.tax.codes.index') }}">
                  <i class="fas fa-tags me-1"></i>
                  <span>Tax Codes</span>
                </a>
                @endcan
                
                @can('finance.tax.settings.view')
                <a class="collapse-item" href="{{ route('admin.finance.tax.settings.index') }}">
                  <i class="fas fa-cogs me-1"></i>
                  <span>VAT Settings</span>
                </a>
                @endcan
                @can('finance.exchange_rates.view')
                 <a class="collapse-item" href="{{ route('admin.finance.exchange_rates.index') }}">
                   <i class="fas fa-exchange-alt me-1"></i>
                   <span>Exchange Rates</span>
                 </a>
               @endcan
            <div class="sidebar-heading text-black my-1">Data Setup</div>
              @can('finance.initialisation.view')
                <a class="collapse-item" href="{{ route('admin.finance.initialisation.index') }}">
                  <i class="fas fa-cogs me-1"></i><span>Fin. Data Init.</span>
                </a>
              @endcan
              @can('finance.health_check.view')
                 <a class="collapse-item" href="{{ route('admin.finance.health_check.index') }}">
                   <i class="fas fa-stethoscope me-1"></i><span>Fin. Health Check</span>
                 </a>
              @endcan
            </div>

            {{-- Operations --}}
            <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finOps">
              <span>Operations</span><i class="fas fa-chevron-down small"></i>
            </div>
            <div id="finOps" class="collapse finance-submenu">
              @can('finance.journals.view')
                <a class="collapse-item" href="{{ route('admin.finance.journal_entries.index') }}">
                  <i class="fas fa-clipboard-list"></i><span>Journal Entries</span>
                </a>
              @endcan
              @can('finance.invoices.view')
                <a class="collapse-item" href="{{ route('admin.finance.invoices.index') }}">
                  <i class="fas fa-file-invoice"></i><span>Invoices</span>
                </a>
              @endcan
              @can('finance.payments.view')
                <a class="collapse-item" href="{{ route('admin.finance.payments.index') }}">
                  <i class="fas fa-credit-card"></i><span>Payments</span>
                </a>
              @endcan
              @can('finance.expense_categories.view')
                <a class="collapse-item" href="{{ route('admin.finance.expense_categories.index') }}">
                  <i class="fas fa-tags"></i><span>Expense Categories</span>
                </a>
              @endcan
              @can('finance.expenses.view')
                <a class="collapse-item" href="{{ route('admin.finance.expenses.index') }}">
                  <i class="fas fa-receipt"></i><span>Expenses</span>
                </a>
              @endcan
              @canany([
                'finance.petty_cash.view',
                'finance.petty_cash.create',
                'finance.petty_cash.accounts.manage',
                'finance.petty_cash.reconcile',
                'finance.petty_cash.audit'
            ])
            <a class="collapse-item" href="{{ route('admin.finance.petty_cash.index') }}">
                <i class="fas fa-wallet"></i>
                <span>Petty Cash</span>
            </a>
            
            <a class="collapse-item" href="{{ route('admin.finance.petty_cash.accounts') }}">
                <i class="fas fa-cash-register"></i>
                <span>Petty Cash Accounts</span>
            </a>
            
            <a class="collapse-item" href="{{ route('admin.finance.petty_cash.reconciliations') }}">
                <i class="fas fa-balance-scale"></i>
                <span>Petty Cash Reconciliation</span>
            </a>
            
            <a class="collapse-item" href="{{ route('admin.finance.petty_cash.audit') }}">
                <i class="fas fa-history"></i>
                <span>Petty Cash Audit</span>
            </a>
            @endcanany
            </div>

            {{-- Fixed Assets --}}
            <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finAssets">
              <span>Fixed Assets</span><i class="fas fa-chevron-down small"></i>
            </div>
            <div id="finAssets" class="collapse finance-submenu">
              @can('finance.fixed_asset_categories.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.categories.index') }}">
                  <i class="fas fa-tags"></i><span>Asset Categories</span>
                </a>
              @endcan
              @can('finance.fixed_assets.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.index') }}">
                  <i class="fas fa-warehouse"></i><span>Fixed Assets</span>
                </a>
              @endcan
              @can('finance.fixed_asset_transfers.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.transfers.index') }}">
                  <i class="fas fa-exchange-alt"></i><span>Asset Transfers</span>
                </a>
              @endcan
              @can('finance.fixed_asset_depreciation.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.depreciation.index') }}">
                  <i class="fas fa-percent"></i><span>Depreciation</span>
                </a>
              @endcan
              @can('finance.fixed_asset_components.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.components.index') }}">
                  <i class="fas fa-puzzle-piece"></i><span>Asset Components</span>
                </a>
              @endcan
              @can('finance.fixed_asset_maintenance.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.maintenance.index') }}">
                  <i class="fas fa-tools"></i><span>Maintenance Logs</span>
                </a>
              @endcan
              @can('finance.asset_capitalisations.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.capitalisations.index') }}">
                  <i class="fas fa-dolly"></i><span>Capitalisation Queue</span>
                </a>
              @endcan
              @can('finance.fixed_asset_revaluations.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.revaluations.index') }}">
                  <i class="fas fa-balance-scale"></i><span>Revaluations</span>
                </a>
              @endcan
              @can('finance.fixed_asset_impairments.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.impairments.index') }}">
                  <i class="fas fa-exclamation-triangle"></i><span>Impairments</span>
                </a>
              @endcan
              @can('finance.fixed_asset_writeoffs.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.writeoffs.index') }}">
                  <i class="fas fa-times-circle"></i><span>Write-Offs</span>
                </a>
              @endcan
              @can('finance.fixed_asset_reports.view')
                <a class="collapse-item" href="{{ route('admin.finance.fixed_assets.reports.index') }}">
                  <i class="fas fa-file-pdf"></i><span>FA Reports</span>
                </a>
              @endcan
            </div>

            {{-- Payables --}}
            @canany(['finance.ap.view','finance.ap.credits.view','finance.ap.aging.view'])
              <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finAP">
                <span>Payables (AP)</span><i class="fas fa-chevron-down small"></i>
              </div>
              <div id="finAP" class="collapse finance-submenu">
                @can('finance.ap.view')
                  <a class="collapse-item" href="{{ route('admin.finance.supplier_bills.index') }}">
                    <i class="fas fa-file-signature"></i><span>Supplier Bills</span>
                  </a>
                @endcan
                @can('finance.ap.credits.view')
                  <a class="collapse-item" href="{{ url('admin/finance/supplier-credits') }}">
                    <i class="fas fa-file-invoice-dollar"></i><span>Supplier Credits</span>
                  </a>
                  <a class="collapse-item" href="{{ route('admin.finance.reports.supplier_statements.index') }}">
                    <i class="fas fa-file-invoice"></i><span>Supplier Statements</span>
                  </a>
                @endcan
                @can('finance.ap.aging.view')
                  <a class="collapse-item" href="{{ url('admin/finance/ap-aging') }}">
                    <i class="fas fa-hourglass-half"></i><span>AP Ageing</span>
                  </a>
                @endcan
              </div>
            @endcanany

            {{-- Banking --}}
            @canany(['finance.bank_reconciliation.view','finance.bank_accounts.view','finance.bank_transactions.view'])
              <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finBanking">
                <span>Banking</span><i class="fas fa-chevron-down small"></i>
              </div>
              <div id="finBanking" class="collapse finance-submenu">
                @can('finance.bank_transactions.view')
                  <a class="collapse-item" href="{{ route('admin.finance.bank_transactions.index') }}">
                    <i class="fas fa-exchange-alt"></i><span>Bank Transactions</span>
                  </a>
                @endcan
                @can('finance.bank_reconciliation.view')
                  <a class="collapse-item" href="{{ route('admin.finance.bank_reconciliations.index') }}">
                    <i class="fas fa-random"></i><span>Bank Reconciliation</span>
                  </a>
                @endcan
              </div>
            @endcanany

            {{-- Planning --}}
            @can('finance.budgets.view')
              <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finPlanning">
                <span>Planning</span><i class="fas fa-chevron-down small"></i>
              </div>
              <div id="finPlanning" class="collapse finance-submenu">
                <a class="collapse-item" href="{{ route('admin.finance.budgets.index') }}">
                  <i class="fas fa-coins"></i><span>Budgets</span>
                </a>
              </div>
            @endcan

            {{-- Receivables --}}
            @canany(['finance.ar.view','finance.reports.ar_ageing.view'])
              <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finAR">
                <span>Receivables (AR)</span><i class="fas fa-chevron-down small"></i>
              </div>
              <div id="finAR" class="collapse finance-submenu">
                @can('finance.reports.ar_ageing.view')
                  <a class="collapse-item" href="{{ route('admin.finance.ar.ageing') }}">
                    <i class="fas fa-user-clock"></i><span>AR Ageing</span>
                  </a>
                @endcan
                @can('finance.ar.view')
                  <a class="collapse-item" href="{{ route('admin.finance.customer_statements.index') }}">
                    <i class="fas fa-file-invoice"></i><span>Customer Statements</span>
                  </a>
                @endcan
              </div>
            @endcanany

            {{-- Reports --}}
            @canany([
              'finance.reports.general_ledger.view',
              'finance.reports.trial_balance.view',
              'finance.reports.profit_loss.view',
              'finance.reports.balance_sheet.view',
              'finance.reports.cash_flow.view'
            ])
              <div class="finance-subtoggle" data-bs-toggle="collapse" data-bs-target="#finReports">
                <span>Reports</span><i class="fas fa-chevron-down small"></i>
              </div>
              <div id="finReports" class="collapse finance-submenu">
                @can('finance.reports.general_ledger.view')
                  <a class="collapse-item" href="{{ route('admin.finance.reports.general_ledger.index') }}">
                    <i class="fas fa-book"></i><span>General Ledger</span>
                  </a>
                @endcan
                @can('finance.reports.trial_balance.view')
                  <a class="collapse-item" href="{{ route('admin.finance.reports.trial_balance.index') }}">
                    <i class="fas fa-balance-scale"></i><span>Trial Balance</span>
                  </a>
                @endcan
                @can('finance.reports.profit_loss.view')
                  <a class="collapse-item" href="{{ route('admin.finance.reports.profit_loss.index') }}">
                    <i class="fas fa-chart-line"></i><span>Profit &amp; Loss</span>
                  </a>
                @endcan
                @can('finance.reports.balance_sheet.view')
                  <a class="collapse-item" href="{{ route('admin.finance.reports.balance_sheets.index') }}">
                    <i class="fas fa-layer-group"></i><span>Balance Sheet</span>
                  </a>
                @endcan
                @can('finance.reports.cash_flow.view')
                  <a class="collapse-item" href="{{ route('admin.finance.reports.cash_flow.index') }}">
                    <i class="fas fa-water"></i><span>Cash Flow</span>
                  </a>
                @endcan
                @canany(['finance.reports.view','finance.chart_of_accounts.view'])
                  <a class="collapse-item" href="{{ route('admin.finance.docs.index') }}">
                    <i class="fas fa-file-alt"></i><span>Finance SOP & Docs</span>
                  </a>
                @endcanany
              </div>
            @endcanany

          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== Sales ===================== --}}
  {{-- IMPORTANT FIX: this should be canAccessModule('sales'), not procurement --}}
  @if(canAccessModule('sales'))
    @canany([
      'sales.orders.view',
      'sales.delivery.view',
      'sales.delivery.create',
      'sales.delivery.store',
      'sales.invoices.view',
      'sales.payments.view',
      'sales.credit_notes.view'
    ])
      <div class="sidebar-heading">Sales</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSales">
          <i class="fas fa-shopping-cart"></i><span>Sales</span>
        </a>

        <div id="collapseSales" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('sales.price_lists.view')
                <a class="collapse-item" href="{{ route('admin.sales.price-lists.index') }}">
                    <i class="fas fa-tags me-1"></i><span>Price Lists</span>
                </a>
            @endcan
             
            @can('sales.pricing_rules.view')
                <a class="collapse-item" href="{{ route('admin.sales.pricing-rules.index') }}">
                    <i class="fas fa-percentage me-1"></i><span>Pricing Rules</span>
                </a>
            @endcan
            @can('sales.orders.view')
              <a class="collapse-item" href="{{ route('admin.sales.orders.index') }}">
                <i class="fas fa-file-signature"></i><span>Sales Orders</span>
              </a>
            @endcan
            @can('sales.delivery.view')
              <a class="collapse-item" href="{{ route('admin.sales.deliveries.index') }}">
                <i class="fas fa-truck"></i><span>Delivery Notes</span>
              </a>
            @endcan
            @canany(['sales.delivery.create','sales.delivery.store'])
              <a class="collapse-item" href="{{ route('admin.sales.deliveries.create') }}">
                <i class="fas fa-plus"></i><span>Create Delivery Note</span>
              </a>
            @endcanany
            @can('sales.invoices.view')
              <a class="collapse-item" href="{{ route('admin.sales.invoices.index') }}">
                <i class="fas fa-file-invoice-dollar"></i><span>Invoices</span>
              </a>
            @endcan
            @can('sales.payments.view')
              <a class="collapse-item" href="{{ route('admin.sales.payments.index') }}">
                <i class="fas fa-money-bill-wave"></i><span>Payments</span>
              </a>
            @endcan
            @can('sales.credit_notes.view')
              <a class="collapse-item" href="{{ route('admin.sales.credit-notes.index') }}">
                <i class="fas fa-undo-alt"></i><span>Credit Notes</span>
              </a>
            @endcan

            <a class="collapse-item" href="{{ route('admin.help.sales-module') }}">
              <i class="fas fa-book"></i><span>Sales Guide</span>
            </a>
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- Sales Analytics --}}
  @if(canAccessModule('sales'))
    @can('sales.analytics.view')
      <div class="sidebar-heading">Sales Analytics</div>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSalesAnalytics">
          <i class="fas fa-chart-line"></i><span>Sales Analytics</span>
        </a>
        <div id="collapseSalesAnalytics" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="{{ route('admin.sales.analytics.index') }}">
              <i class="fas fa-chart-line"></i><span>Sales Analytics</span>
            </a>
          </div>
        </div>
      </li>
    @endcan
  @endif

  {{-- ===================== Procurement ===================== --}}
  @if(canAccessModule('procurement'))
    @canany([
        'procurement.requisitions.view',
        'procurement.rfqs.view',
        'procurement.quotations.view',
        'procurement.purchase_orders.view',
        'procurement.goods_receipts.view'
    ])
      <div class="sidebar-heading">Procurement</div>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProcurement">
          <i class="fas fa-file-signature"></i><span>Procurement</span>
        </a>
        <div id="collapseProcurement" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('procurement.requisitions.view')
              <a class="collapse-item" href="{{ route('admin.procurement.purchase_requisitions.index') }}">
                <i class="fas fa-file-alt me-2"></i> Purchase Reqs.
              </a>
            @endcan
            @can('procurement.rfqs.view')
            <a class="collapse-item" href="{{ route('admin.procurement.rfqs.index') }}">
              <i class="fas fa-file-signature me-2"></i> RFQs
            </a>
            @endcan
            @can('procurement.supplier_quotations.view')
            <a class="collapse-item" href="{{ route('admin.procurement.supplier_quotations.index') }}">
                <i class="fas fa-file-invoice-dollar me-2"></i> Supplier Quotes
            </a>
            @endcan
            @can('procurement.purchase_orders.view')
            <a class="collapse-item" href="{{ route('admin.procurement.purchase_orders.index') }}">
                <i class="fas fa-shopping-cart me-2"></i> Purchase Orders
            </a>
            @endcan
            @can('procurement.goods_receipts.view')
            <a class="collapse-item" href="{{ route('admin.procurement.goods_receipts.index') }}">
                <i class="fas fa-truck-loading me-2"></i> Goods Receipts
            </a>
            @endcan
            @can('procurement.guide.view')
            <a class="collapse-item" href="{{ route('admin.procurement.guide.index') }}">
                <i class="fas fa-book me-2"></i>
                <span>Procurement Guide</span>
            </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== Manufacturing ===================== --}}
  @if(canAccessModule('production'))
    @canany([
      'production.work_orders.cost_types.view',
      'production.routings.view',
      'production.routings.steps.view'
    ])
      <div class="sidebar-heading">Manufacturing</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseManufacturingParameters">
          <i class="fas fa-industry"></i><span>Production Parameters</span>
        </a>
        <div id="collapseManufacturingParameters" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('production.work_orders.cost_types.view')
              <a class="collapse-item" href="{{ route('admin.production.work-orders.cost_types.index') }}">
                <i class="fas fa-coins"></i><span>WO Cost Types</span>
              </a>
            @endcan
            @can('production.routings.view')
              <a class="collapse-item" href="{{ route('admin.production.routings.index') }}">
                <i class="fas fa-route"></i><span>Routings</span>
              </a>
            @endcan
            @can('production.routings.steps.view')
              <a class="collapse-item" href="{{ route('admin.production.routings.steps.index') }}">
                <i class="fas fa-shoe-prints"></i><span>Routing Steps</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany

    @canany([
      'production.raw_materials.view',
      'production.boms.view',
      'production.bom_items.view',
      'production.bom_deficits.view',
      'production.bom_deficit_transactions.view',
      'production.work_orders.view',
      'production.work_order_materials.view',
      'production.work_order_steps.view'
    ])
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseManufacturing">
          <i class="fas fa-industry"></i><span>Production</span>
        </a>
        <div id="collapseManufacturing" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('production.raw_materials.view')
              <a class="collapse-item" href="{{ route('admin.production.raw-materials.index') }}">
                <i class="fas fa-cubes"></i><span>Raw Materials</span>
              </a>
            @endcan
            @can('production.boms.view')
              <a class="collapse-item" href="{{ route('admin.production.boms.index') }}">
                <i class="fas fa-stream"></i><span>Bill of Materials (BOM)</span>
              </a>
            @endcan
            @can('production.bom_items.view')
              <a class="collapse-item" href="{{ route('admin.production.boms.items.index') }}">
                <i class="fas fa-list"></i><span>BOM Items</span>
              </a>
            @endcan
            @can('production.bom_deficits.view')
              <a class="collapse-item" href="{{ route('admin.production.boms.deficits.index') }}">
                <i class="fas fa-exclamation-circle"></i><span>BOM Deficits</span>
              </a>
            @endcan
            @can('production.bom_deficit_transactions.view')
              <a class="collapse-item" href="{{ route('admin.production.boms.deficits.transactions.index') }}">
                <i class="fas fa-exchange-alt"></i><span>BOM Deficit Txns</span>
              </a>
            @endcan
            @can('production.work_orders.view')
              <a class="collapse-item" href="{{ route('admin.production.work-orders.index') }}">
                <i class="fas fa-tasks"></i><span>Work Orders</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== CRM ===================== --}}
  @if(canAccessModule('crm'))
    @canany([
      'crm.leads.view',
      'crm.opportunities.view',
      'crm.activities.view',
      'crm.notes.view',
      'crm.interactions.view',
      'crm.support_tickets.view',
    ])
      <div class="sidebar-heading">CRM</div>

      <li class="nav-item my-0">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCRM">
          <i class="fas fa-user-friends"></i><span>CRM</span>
        </a>
        <div id="collapseCRM" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white collapse-inner rounded">
            @can('crm.leads.view')
              <a class="collapse-item" href="{{ route('admin.crm.leads.index') }}">
                <i class="fas fa-user-plus"></i><span>Leads</span>
              </a>
            @endcan
            @can('crm.opportunities.view')
              <a class="collapse-item" href="{{ route('admin.crm.opportunities.index') }}">
                <i class="fas fa-bullseye"></i><span>Opportunities</span>
              </a>
            @endcan
            @can('crm.activities.view')
              <a class="collapse-item" href="{{ route('admin.crm.activities.index') }}">
                <i class="fas fa-calendar-check"></i><span>Activities</span>
              </a>
            @endcan
            @can('crm.notes.view')
              <a class="collapse-item" href="{{ route('admin.crm.notes.index') }}">
                <i class="fas fa-sticky-note"></i><span>Notes</span>
              </a>
            @endcan
            @can('crm.interactions.view')
              <a class="collapse-item" href="{{ route('admin.crm.interactions.index') }}">
                <i class="fas fa-comments"></i><span>Interactions</span>
              </a>
            @endcan
            @can('crm.support_tickets.view')
              <a class="collapse-item" href="{{ route('admin.crm.support_tickets.index') }}">
                <i class="fas fa-life-ring"></i><span>Support Tickets</span>
              </a>
            @endcan
            @can('crm.docs.view')
              <a class="collapse-item" href="{{ route('admin.crm.docs.workflow_privileges.index') }}">
                <i class="fas fa-book"></i><span>CRM SOP & Privileges</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany

    {{-- CRM Analytics --}}
    @canany([
      'crm.leads.analytics.view',
      'crm.opportunities.analytics.view',
      'crm.activities.analytics.view',
      'crm.notes.analytics.view',
      'crm.interactions.analytics.view',
      'crm.support_tickets.analytics.view',
      'crm.customers.analytics.view',
      'crm.dashboard.view',
      'crm.analytics.customer_segmentation.view',
    ])
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCRMAnalytics">
          <i class="fas fa-chart-pie"></i><span>CRM Analytics</span>
        </a>
        <div id="collapseCRMAnalytics" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white collapse-inner rounded">
            @can('crm.leads.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.leads.analytics.index') }}">
                <i class="fas fa-chart-pie"></i><span>Leads</span>
              </a>
            @endcan
            @can('crm.opportunities.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.opportunities.analytics.index') }}">
                <i class="fas fa-funnel-dollar"></i><span>Opportunities</span>
              </a>
            @endcan
            @can('crm.activities.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.activities.analytics.index') }}">
                <i class="fas fa-chart-line"></i><span>Activities</span>
              </a>
            @endcan
            @can('crm.notes.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.notes.analytics.index') }}">
                <i class="fas fa-chart-line"></i><span>Notes</span>
              </a>
            @endcan
            @can('crm.interactions.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.interactions.analytics.index') }}">
                <i class="fas fa-comments"></i><span>Interactions</span>
              </a>
            @endcan
            @can('crm.support_tickets.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.support_tickets.analytics.index') }}">
                <i class="fas fa-chart-line"></i><span>Tickets</span>
              </a>
            @endcan
            @can('crm.customers.analytics.view')
              <a class="collapse-item" href="{{ route('admin.crm.customers.analytics.index') }}">
                <i class="fas fa-chart-pie"></i><span>Customers</span>
              </a>
            @endcan
            @can('crm.dashboard.view')
              <a class="collapse-item" href="{{ route('admin.crm.dashboard.index') }}">
                <i class="fas fa-tachometer-alt"></i><span>CRM Executive</span>
              </a>
            @endcan
            @can('crm.customers.segmentation_presets.view')
              <a class="collapse-item" href="{{ route('admin.crm.customers.segmentation_presets.index') }}">
                <i class="fas fa-sliders-h"></i><span>Segment Presets</span>
              </a>
            @endcan
            @can('crm.analytics.customer_segmentation.view')
              <a class="collapse-item" href="{{ route('admin.crm.analytics.customer_segmentation.index') }}">
                <i class="fas fa-chart-pie"></i><span>Customer Segmentation</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== HRM ===================== --}}
  @if(canAccessModule('hrm'))
    @canany([
      'hrm.employees.view',
      'hrm.attendance.view',
      'hrm.leaves.view',
      'hrm.payroll.view',
      'hrm.training.view',
      'hrm.performance.view'
    ])
      <div class="sidebar-heading">Human Resources</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseHR">
          <i class="fas fa-users-cog"></i><span>HRM</span>
        </a>
        <div id="collapseHR" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('hrm.employees.view')
              <a class="collapse-item" href="{{ route('admin.hrm.employees.index') }}">
                <i class="fas fa-id-badge"></i><span>Employees</span>
              </a>
            @endcan
            @can('hrm.leave_types.view')
            <a class="collapse-item" href="{{ route('admin.hrm.leave-types.index') }}">
                <i class="fas fa-calendar-times me-1"></i><span>Leave Types</span>
            </a>
            @endcan
             
            @can('hrm.shifts.view')
            <a class="collapse-item" href="{{ route('admin.hrm.shifts.index') }}">
                <i class="fas fa-clock me-1"></i><span>Shifts</span>
            </a>
            @endcan
             
            @can('hrm.rosters.view')
            <a class="collapse-item" href="{{ route('admin.hrm.rosters.index') }}">
                <i class="fas fa-calendar-alt me-1"></i><span>Rosters</span>
            </a>
            @endcan
             
            @can('hrm.job_grades.view')
            <a class="collapse-item" href="{{ route('admin.hrm.job-grades.index') }}">
                <i class="fas fa-layer-group me-1"></i><span>Job Grades</span>
            </a>
            @endcan
             
            @can('hrm.job_positions.view')
            <a class="collapse-item" href="{{ route('admin.hrm.job-positions.index') }}">
                <i class="fas fa-briefcase me-1"></i><span>Job Positions</span>
            </a>
            @endcan
             
            @can('hrm.contracts.view')
            <a class="collapse-item" href="{{ route('admin.hrm.contracts.index') }}">
                <i class="fas fa-file-contract me-1"></i><span>Contracts</span>
            </a>
            @endcan
             
            @can('hrm.recruitment.openings.view')
            <a class="collapse-item" href="{{ route('admin.hrm.recruitment.openings.index') }}">
                <i class="fas fa-user-plus me-1"></i><span>Recruitment</span>
            </a>
            @endcan
             
            @can('hrm.payroll_runs.view')
            <a class="collapse-item" href="{{ route('admin.hrm.payroll-runs.index') }}">
                <i class="fas fa-money-check-alt me-1"></i><span>Payroll Runs</span>
            </a>
            @endcan
            @can('hrm.attendance.view')
              <a class="collapse-item" href="{{ route('admin.hrm.employees.attendance.index') }}">
                <i class="fas fa-user-clock"></i><span>Attendance</span>
              </a>
            @endcan
            @can('hrm.leaves.view')
              <a class="collapse-item" href="{{ route('admin.hrm.employees.leaves.index') }}">
                <i class="fas fa-plane-departure"></i><span>Leaves</span>
              </a>
            @endcan
            @can('hrm.payroll.view')
              <a class="collapse-item" href="{{ route('admin.hrm.payroll.index') }}">
                <i class="fas fa-money-check-alt"></i><span>Payroll</span>
              </a>
            @endcan
            @can('hrm.training.view')
              <a class="collapse-item" href="{{ route('admin.hrm.employees.trainings.index') }}">
                <i class="fas fa-chalkboard-teacher"></i><span>Training</span>
              </a>
            @endcan
            @can('hrm.performance.view')
              <a class="collapse-item" href="{{ route('admin.hrm.employees.performances.index') }}">
                <i class="fas fa-chart-line"></i><span>Performance</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  {{-- ===================== Projects & Assets ===================== --}}
  @if(canAccessModule('projects'))
    @canany(['projects.projects.view','projects.maintenance.view','projects.assets.view'])
      <div class="sidebar-heading">Projects & Assets</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProjects">
          <i class="fas fa-project-diagram"></i><span>Projects & Maintenance</span>
        </a>
        <div id="collapseProjects" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('projects.projects.view')
              <a class="collapse-item" href="{{ route('admin.projects.index') }}">
                  <i class="fas fa-project-diagram"></i>
                  <span>Projects</span>
              </a>
            @endcan
            @can('projects.projects.tasks.view')
            <a class="collapse-item" href="{{ route('admin.project_tasks.index') }}">
                <i class="fas fa-tasks"></i>
                <span>Project Tasks</span>
            </a>
            @endcan
            @can('projects.projects.milestones.view')
            <a class="collapse-item" href="{{ route('admin.project_milestones.index') }}">
                <i class="fas fa-flag-checkered"></i>
                <span>Project Milestones</span>
            </a>
            @endcan
            @can('projects.projects.costs.view')
            <a class="collapse-item" href="{{ route('admin.project_costs.index') }}">
                <i class="fas fa-money-bill-wave"></i>
                <span>Project Costs</span>
            </a>
            @endcan
            @can('projects.projects.timesheets.view')
            <a class="collapse-item" href="{{ route('admin.project_timesheets.index') }}">
                <i class="fas fa-clock"></i>
                <span>Project Timesheets</span>
            </a>
            @endcan
            @can('projects.projects.budgets.view')
            <a class="collapse-item" href="{{ route('admin.project_budgets.index') }}">
                <i class="fas fa-wallet"></i>
                <span>Project Budgets</span>
            </a>
            @endcan
            @can('projects.projects.invoices.view')
            <a class="collapse-item" href="{{ route('admin.project_invoices.index') }}">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Project Invoices</span>
            </a>
            @endcan
            @can('projects.projects.view')
            <a class="collapse-item" href="{{ route('admin.projects.docs.index') }}">
                <i class="fas fa-book-open"></i>
                <span>Module Guide</span>
            </a>
            @endcan
            <!--
            @can('projects.maintenance.view')
              <a class="collapse-item" href="{{ route('admin.maintenance.index') }}">
                <i class="fas fa-tools"></i><span>Maintenance</span>
              </a>
            @endcan
            @can('projects.assets.view')
              <a class="collapse-item" href="{{ route('admin.assets.index') }}">
                <i class="fas fa-toolbox"></i><span>Assets</span>
              </a>
            @endcan
            -->
          </div>
        </div>
      </li>
    @endcanany
  @endif

    @if (canAccessModule('documents') || auth()->user()->canany([
        'documents.view',
        'documents.categories.view',
        'documents.types.view'
    ]))
    <div class="sidebar-heading">Document Management</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseDocuments"
           aria-expanded="false" aria-controls="collapseDocuments">
            <i class="fas fa-folder-open"></i>
            <span>Document Management</span>
        </a>
        <div id="collapseDocuments" class="collapse" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                @can('documents.view.index')
                <a class="collapse-item" href="{{ route('admin.documents.index') }}">
                    <i class="fas fa-file"></i> Documents
                </a>
                @endcan
    
                @can('documents.categories.view.index')
                <a class="collapse-item" href="{{ route('admin.document-categories.index') }}">
                    <i class="fas fa-tags"></i> Categories
                </a>
                @endcan
    
                @can('documents.types.view.index')
                <a class="collapse-item" href="{{ route('admin.document-types.index') }}">
                    <i class="fas fa-layer-group"></i> Types
                </a>
                @endcan
            </div>
        </div>
    </li>
    @endif
    
  {{-- ===================== Reports ===================== --}}
  @if(canAccessModule('reports'))
    @canany([
      'reports.sales.view',
      'reports.inventory.view',
      'reports.finance.view',
      'reports.hrm.view',
      'reports.projects.view'
    ])
      <div class="sidebar-heading">Reports</div>

      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports">
          <i class="fas fa-chart-line"></i><span>Reports</span>
        </a>
        <div id="collapseReports" class="collapse" data-bs-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            @can('reports.sales.view')
              <a class="collapse-item" href="{{ route('admin.reports.sales.index') }}">
                <i class="fas fa-chart-bar"></i><span>Sales</span>
              </a>
            @endcan
            @can('reports.inventory.view')
              <a class="collapse-item" href="{{ route('admin.reports.inventory.index') }}">
                <i class="fas fa-boxes"></i><span>Inventory</span>
              </a>
            @endcan
            @can('reports.finance.view')
              <a class="collapse-item" href="{{ route('admin.reports.finance.index') }}">
                <i class="fas fa-coins"></i><span>Finance</span>
              </a>
            @endcan
            @can('reports.hrm.view')
              <a class="collapse-item" href="{{ route('admin.reports.hr.index') }}">
                <i class="fas fa-users"></i><span>HR</span>
              </a>
            @endcan
            @can('reports.projects.view')
              <a class="collapse-item" href="{{ route('admin.project_profitability.index') }}">
                  <i class="fas fa-chart-line"></i>
                  <span>Project Profitability</span>
              </a>
            @endcan
          </div>
        </div>
      </li>
    @endcanany
  @endif

  <hr class="sidebar-divider d-none d-md-block">

  {{-- Desktop collapse button (SB Admin 2 default) --}}
  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

</ul>

{{-- ===================== 4) SIDEBAR JS (SB Admin 2 style) ===================== --}}
<script>
(function () {
  const body = document.body;
  const sidebar = document.getElementById('accordionSidebar');
  const overlay = document.getElementById('sidebarOverlay');

  const toggleTop = document.getElementById('sidebarToggleTop');
  const toggleBtn = document.getElementById('sidebarToggle');

  const searchInput = document.getElementById('sidebarSearch');
  const searchClear = document.getElementById('sidebarSearchClear');

  function isMobile() {
    return window.matchMedia('(max-width: 767.98px)').matches;
  }

  function openMobileSidebar() {
    body.classList.add('sidebar-mobile-open');
    sidebar?.classList.add('mobile-open');
  }

  function closeMobileSidebar() {
    body.classList.remove('sidebar-mobile-open');
    sidebar?.classList.remove('mobile-open');
  }

  function toggleMobileSidebar() {
    const isOpen = body.classList.contains('sidebar-mobile-open');
    isOpen ? closeMobileSidebar() : openMobileSidebar();
  }

  function collapseAllSidebarMenus() {
    if (!sidebar) return;
    sidebar.querySelectorAll('.collapse.show').forEach(el => {
      const inst = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
      inst.hide();
    });
  }

  function refreshSidebarScroll() {
    if (!sidebar) return;
    sidebar.style.overflowY = 'hidden';
    void sidebar.offsetHeight;
    sidebar.style.overflowY = 'auto';
  }

  toggleTop?.addEventListener('click', function (e) {
    e.preventDefault();
    if (isMobile()) toggleMobileSidebar();
  });

  toggleBtn?.addEventListener('click', function (e) {
    e.preventDefault();

    if (isMobile()) {
      toggleMobileSidebar();
      return;
    }

    body.classList.toggle('sidebar-toggled');
    sidebar?.classList.toggle('toggled');

    refreshSidebarScroll();
  });

  overlay?.addEventListener('click', function () {
    closeMobileSidebar();
  });

  sidebar?.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href') || '';
    const isCollapseToggle = link.getAttribute('data-bs-toggle') === 'collapse';

    if (isCollapseToggle) return;

    if (isMobile() && href && href !== '#' && href !== 'javascript:void(0)') {
      closeMobileSidebar();
    }
  });

  function handleSearchCleared() {
    collapseAllSidebarMenus();
    refreshSidebarScroll();
  }

  searchClear?.addEventListener('click', function () {
    handleSearchCleared();
  });

  searchInput?.addEventListener('input', function () {
    if ((searchInput.value || '').trim() === '') {
      handleSearchCleared();
    }
  });

  window.addEventListener('resize', function () {
    if (!isMobile()) closeMobileSidebar();
    refreshSidebarScroll();
  });
})();
</script>