<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">{{ env('APP_NAME', 'Simply-ERP') }}</div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Core ERP -->
    <div class="sidebar-heading">Core ERP</div>

    <!-- Geography -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseGeography"
        aria-expanded="true" aria-controls="collapseGeography">
            <i class="fas fa-map-marked-alt"></i>
            <span>Geography</span>
        </a>
        <div id="collapseGeography" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.regions.index') }}">Regions</a>
                <a class="collapse-item" href="{{ route('admin.subregions.index') }}">Subregions</a>
                <a class="collapse-item" href="{{ route('admin.countries.index') }}">Countries</a>
                <a class="collapse-item" href="{{ route('admin.states.index') }}">States</a>
                <a class="collapse-item" href="{{ route('admin.cities.index') }}">Cities</a>
            </div>
        </div>
    </li>

    <!-- Locations -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLocation"
        aria-expanded="true" aria-controls="collapseLocation">
            <i class="fas fa-building"></i>
            <span>Locations</span>
        </a>
        <div id="collapseLocation" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.location_types.index') }}">Location Types</a>
                <a class="collapse-item" href="{{ route('admin.locations.index') }}">Locations</a>
                <a class="collapse-item" href="{{ route('admin.location_blocks.index') }}">Blocks</a>
                <a class="collapse-item" href="{{ route('admin.location_floors.index') }}">Floors</a>
                <a class="collapse-item" href="{{ route('admin.location_rooms.index') }}">Rooms</a>
            </div>
        </div>
    </li>

    <!-- Storage -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStorage"
        aria-expanded="true" aria-controls="collapseStorage">
            <i class="fas fa-warehouse"></i>
            <span>Storage</span>
        </a>
        <div id="collapseStorage" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.location_stores.index') }}">Stores</a>
                <a class="collapse-item" href="{{ route('admin.store_shelves.index') }}">Shelves</a>
            </div>
        </div>
    </li>

    <!-- Parameters -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseParameters"
        aria-expanded="true" aria-controls="collapseParameters">
            <i class="fas fa-sliders-h"></i>
            <span>Parameters</span>
        </a>
        <div id="collapseParameters" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.modules.index') }}">Modules</a>
                <a class="collapse-item" href="{{ route('admin.roles.index') }}">Roles</a>
                <a class="collapse-item" href="{{ route('admin.permissions.index') }}">Permissions</a>
                <a class="collapse-item" href="{{ route('admin.companies.index') }}">Companies</a>
                <a class="collapse-item" href="{{ route('admin.companies.departments.index') }}">Departments</a>
            </div>
        </div>
    </li>

    <!-- Inventory -->
    <div class="sidebar-heading">Inventory Management</div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts"
        aria-expanded="true" aria-controls="collapseProducts">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        <div id="collapseProducts" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.inventory.products.manufacturers.list') }}">Manufacturers</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.brands.list') }}">Brands</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.types.index') }}">Attribute Types</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.index') }}">Attributes</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.attributes.values.index') }}">Attribute Values</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.index') }}">Products</a>
                <a class="collapse-item" href="{{ route('admin.inventory.products.variants.index') }}">Product Variants</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStocks"
        aria-expanded="true" aria-controls="collapseStocks">
            <i class="fas fa-box-open"></i>
            <span>Stock Management</span>
        </a>
        <div id="collapseStocks" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.index') }}">Stock Entries</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.lines.index') }}" class="collapse-item" href="#">Stock Entry Lines</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock_entries.transactions.index') }}">Stock Transactions</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock.levels.index') }}">Stock Levels</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock.levels.low.index') }}">Low Stock Levels</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock.aging.index') }}">Stock Aging</a>
                <a class="collapse-item" href="{{ route('admin.inventory.stock.transfers.index') }}">Stock Transfer</a>
            </div>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseDashboards"
        aria-expanded="true" aria-controls="collapseDashboards">
            <i class="fas fa-tachometer-alt"></i>
            <span>Stock Dashboard</span>
        </a>
        <div id="collapseDashboards" class="collapse" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.inventory.stock.dashboard.index') }}">Stock Dashboard</a>
            </div>
        </div>
    </li>
    <!-- Finance -->
    <div class="sidebar-heading">Finance</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFinance">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Financials</span>
        </a>
        <div id="collapseFinance" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.finance.chart_of_accounts.index') }}">Chart of Accounts</a>
                <a class="collapse-item" href="{{ route('admin.finance.journals.index') }}">Journal Entries</a>
                <a class="collapse-item" href="{{ route('admin.finance.invoices.index') }}">Invoices</a>
                <a class="collapse-item" href="{{ route('admin.finance.payments.index') }}">Payments</a>
                <a class="collapse-item" href="{{ route('admin.finance.expenses.index') }}">Expenses</a>
                <a class="collapse-item" href="{{ route('admin.finance.budgets.index') }}">Budgets</a>
            </div>
        </div>
    </li>

    <!-- Sales & Orders -->
    <div class="sidebar-heading">Sales</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSales">
            <i class="fas fa-shopping-cart"></i>
            <span>Sales</span>
        </a>
        <div id="collapseSales" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.sales.orders.index') }}">Orders</a>
                <a class="collapse-item" href="{{ route('admin.sales.quotations.index') }}">Quotations</a>
                <a class="collapse-item" href="{{ route('admin.sales.deliveries.index') }}">Delivery Notes</a>
                <a class="collapse-item" href="{{ route('admin.sales.returns.index') }}">Returns</a>
            </div>
        </div>
    </li>

    <!-- Procurement -->
    <div class="sidebar-heading">Procurement</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProcurement">
            <i class="fas fa-file-signature"></i>
            <span>Procurement</span>
        </a>
        <div id="collapseProcurement" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.procurement.purchase_orders.index') }}">Purchase Orders</a>
                <a class="collapse-item" href="{{ route('admin.procurement.receiving.index') }}">Receiving</a>
                <a class="collapse-item" href="{{ route('admin.procurement.invoices.index') }}">Supplier Invoices</a>
            </div>
        </div>
    </li>

    <!-- Manufacturing -->
    <div class="sidebar-heading">Manufacturing</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseManufacturing">
            <i class="fas fa-industry"></i>
            <span>Production</span>
        </a>
        <div id="collapseManufacturing" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.production.raw-materials.index') }}">Raw Materials</a>
                <a class="collapse-item" href="{{ route('admin.production.boms.index') }}">Bill of Materials</a>
                <a class="collapse-item" href="{{ route('admin.production.boms.items.index') }}">Bill of Material Items</a>
                <a class="collapse-item" href="{{ route('admin.production.routings.index') }}">Routings</a>
                <a class="collapse-item" href="{{ route('admin.production.routings.steps.index') }}">Routing Steps</a>
                <a class="collapse-item" href="{{ route('admin.production.work-orders.index') }}">Work Orders</a>
                <a class="collapse-item" href="{{ route('admin.production.work-orders.materials.index') }}">Work Order Materials</a>
                <a class="collapse-item" href="{{ route('admin.production.work-orders.steps.index') }}">Work Order Steps</a>
            </div>
        </div>
    </li>

    <!-- CRM -->
    <div class="sidebar-heading">CRM</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCRM">
            <i class="fas fa-user-friends"></i>
            <span>CRM</span>
        </a>
        <div id="collapseCRM" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.crm.customers.index') }}">Customers</a>
                <a class="collapse-item" href="{{ route('admin.crm.leads.index') }}">Leads</a>
                <a class="collapse-item" href="{{ route('admin.crm.opportunities.index') }}">Opportunities</a>
                <a class="collapse-item" href="{{ route('admin.crm.activities.index') }}">Activities</a>
                <a class="collapse-item" href="{{ route('admin.crm.notes.index') }}">Notes</a>
                <a class="collapse-item" href="{{ route('admin.crm.interactions.index') }}">Interactions</a>
                <a class="collapse-item" href="{{ route('admin.crm.support-tickets.index') }}">Support Tickets</a>
            </div>
        </div>
    </li>

    <!-- HR -->
    <div class="sidebar-heading">Human Resources</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseHR">
            <i class="fas fa-users-cog"></i>
            <span>HRM</span>
        </a>
        <div id="collapseHR" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.hrm.employees.index') }}">Employees</a>
                <a class="collapse-item" href="{{ route('admin.hrm.employees.attendance.index') }}">Attendance</a>
                <a class="collapse-item" href="{{ route('admin.hrm.employees.leaves.index') }}">Leaves</a>
                <a class="collapse-item" href="{{ route('admin.hrm.payroll.index') }}">Payroll</a>
                <a class="collapse-item" href="{{ route('admin.hrm.employees.trainings.index') }}">Training</a>
                <a class="collapse-item" href="{{ route('admin.hrm.employees.performances.index') }}">Performance</a>
            </div>
        </div>
    </li>

    <!-- Projects & Assets -->
    <div class="sidebar-heading">Projects & Assets</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProjects">
            <i class="fas fa-project-diagram"></i>
            <span>Projects & Maintenance</span>
        </a>
        <div id="collapseProjects" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.projects.index') }}">Projects</a>
                <a class="collapse-item" href="{{ route('admin.maintenance.index') }}">Maintenance</a>
                <a class="collapse-item" href="{{ route('admin.assets.index') }}">Assets</a>
            </div>
        </div>
    </li>

    <!-- Reports -->
    <div class="sidebar-heading">Reports</div>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports">
            <i class="fas fa-chart-line"></i>
            <span>Reports</span>
        </a>
        <div id="collapseReports" class="collapse">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('admin.reports.sales.index') }}">Sales</a>
                <a class="collapse-item" href="{{ route('admin.reports.inventory.index') }}">Inventory</a>
                <a class="collapse-item" href="{{ route('admin.reports.finance.index') }}">Finance</a>
                <a class="collapse-item" href="{{ route('admin.reports.hr.index') }}">HR</a>
                <a class="collapse-item" href="{{ route('admin.reports.projects.index') }}">Projects</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
