{{-- resources/views/inventory/products/docs/privileges.blade.php --}}
@extends('layouts.master')

@section('title', 'Products SOP & Privileges')

@section('content')
@php
  /**
   * SOURCE OF TRUTH: sidebar permissions + routes
   * We ONLY show the view permissions because that is what your sidebar proves exists today.
   * (You can later expand to create/edit/delete once you add them to your permissions table.)
   */
  $resources = [
    ['key'=>'units',            'label'=>'Units',            'route'=>'admin.inventory.products.units.index',           'perm'=>'inventory.products.units.view',            'icon'=>'fas fa-ruler-combined'],
    ['key'=>'manufacturers',    'label'=>'Manufacturers',    'route'=>'admin.inventory.products.manufacturers.list',      'perm'=>'inventory.products.manufacturers.view',    'icon'=>'fas fa-industry'],
    ['key'=>'brands',           'label'=>'Brands',           'route'=>'admin.inventory.products.brands.list',             'perm'=>'inventory.products.brands.view',           'icon'=>'fas fa-copyright'],
    ['key'=>'attribute_types',  'label'=>'Attribute Types',  'route'=>'admin.inventory.products.attributes.types.index',  'perm'=>'inventory.products.attribute_types.view',  'icon'=>'fas fa-layer-group'],
    ['key'=>'attributes',       'label'=>'Attributes',       'route'=>'admin.inventory.products.attributes.index',        'perm'=>'inventory.products.attributes.view',       'icon'=>'fas fa-sliders-h'],
    ['key'=>'attribute_values', 'label'=>'Attribute Values', 'route'=>'admin.inventory.products.attributes.values.index', 'perm'=>'inventory.products.attribute_values.view', 'icon'=>'fas fa-list-ul'],
    ['key'=>'categories',       'label'=>'Categories',       'route'=>'admin.inventory.products.categories.index',        'perm'=>'inventory.products.categories.view',       'icon'=>'fas fa-sitemap'],
    ['key'=>'products',         'label'=>'Products',         'route'=>'admin.inventory.products.index',                   'perm'=>'inventory.products.view',                  'icon'=>'fas fa-box'],
    ['key'=>'variants',         'label'=>'Product Variants', 'route'=>'admin.inventory.products.variants.index',          'perm'=>'inventory.products.variants.view',         'icon'=>'fas fa-cubes'],
  ];

  $roleGuidance = [
    'Inventory Admin' => [
      'Full access to all Products menus + governance responsibility.',
      'Creates/maintains Units, Manufacturers, Brands, Attributes, Values, Categories.',
      'Approves major catalog changes and prevents duplicates.'
    ],
    'Inventory Officer' => [
      'Creates Products and Variants following the SOP.',
      'Maintains Values/Attributes as needed (if permitted).',
      'Avoids deleting — escalates changes to Admin.'
    ],
    'Viewer / Sales' => [
      'View-only access: Products + Variants only.',
      'No master-data edits to protect stock integrity.'
    ],
  ];
@endphp

<style>
  .doc-wrap { font-size: 15px; line-height: 1.7; color:#1f2937; }
  .doc-wrap .text-muted { font-size: 14px; }
  .hero{
    background: linear-gradient(135deg, rgba(78,115,223,.16), rgba(28,200,138,.12));
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 16px;
    padding: 18px;
  }
  .cardx{
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 16px;
    background: #fff;
  }
  .perm-pill{
    display:inline-flex; align-items:center; gap:6px;
    padding: 5px 10px;
    border-radius: 999px;
    border:1px solid rgba(0,0,0,.10);
    background:#fff;
    font-size: 13px;
    white-space: nowrap;
  }
  .perm-pill.y{ border-color: rgba(28,200,138,.25); color:#1cc88a; }
  .perm-pill.n{ border-color: rgba(231,74,59,.25); color:#e74a3b; }
  .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,"Liberation Mono","Courier New", monospace;
    font-size: 13px;
    color:#6b7280;
  }
</style>

<div class="container-fluid doc-wrap">

  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h3 mb-1 text-gray-800">Products — SOP & Privileges</h1>
      <div class="text-muted">
        This page shows who can access each Products menu item, based on sidebar permissions.
      </div>
    </div>
    <div class="d-flex flex-wrap" style="gap:.5rem;">
      <a href="{{ route('admin.inventory.stock.dashboard.index') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-chart-bar mr-1"></i> Inventory Dashboard
      </a>
      <a href="{{ route('admin.inventory.workflow.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-route mr-1"></i> Stock Workflow
      </a>
    </div>
  </div>

  <div class="hero mb-4">
    <div class="font-weight-bold text-gray-800">What “Privileges” means here</div>
    <div class="text-muted" style="font-size:15px;">
      Your sidebar currently proves these permission keys exist: <span class="mono">inventory.products.*.view</span>.
      So this matrix focuses on **menu access (view)**.
      <br>
      If you later add <span class="mono">create/edit/delete</span> permissions, we can expand this table to include them.
    </div>
  </div>

  {{-- Matrix --}}
  <div class="cardx mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
      <h6 class="m-0 font-weight-bold text-primary">Permission matrix</h6>
      <div class="mono">Source: sidebar permission checks</div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th style="width:30%">Menu Item</th>
              <th style="width:18%">Access</th>
              <th>Permission Key</th>
            </tr>
          </thead>
          <tbody>
            @foreach($resources as $r)
              <tr>
                <td class="font-weight-bold">
                  <i class="{{ $r['icon'] }} mr-2 text-primary"></i>
                  @can($r['perm'])
                    <a href="{{ route($r['route']) }}">{{ $r['label'] }}</a>
                  @else
                    {{ $r['label'] }}
                  @endcan
                </td>
                <td>
                  @can($r['perm'])
                    <span class="perm-pill y"><i class="fas fa-check-circle"></i> Allowed</span>
                  @else
                    <span class="perm-pill n"><i class="fas fa-times-circle"></i> Denied</span>
                  @endcan
                </td>
                <td class="mono">{{ $r['perm'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="alert alert-light mb-0" style="font-size:15px;">
        <strong>Control recommendation:</strong> Restrict master data (Units/Attributes/Values) to Admins.
        Allow Products/Variants to inventory officers. Sales users should be view-only.
      </div>
    </div>
  </div>

  {{-- Role guidance --}}
  <div class="cardx">
    <div class="card-header bg-white">
      <h6 class="m-0 font-weight-bold text-primary">Recommended role access model</h6>
    </div>
    <div class="card-body">
      <div class="row">
        @foreach($roleGuidance as $role => $points)
          <div class="col-lg-4 mb-3">
            <div class="card border-0" style="border:1px solid rgba(0,0,0,.06); border-radius:16px;">
              <div class="card-body">
                <div class="font-weight-bold" style="font-size:16px;">{{ $role }}</div>
                <ul class="mt-2 mb-0">
                  @foreach($points as $p)
                    <li>{{ $p }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

</div>
@endsection
