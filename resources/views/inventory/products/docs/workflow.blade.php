{{-- resources/views/inventory/products/docs/workflow.blade.php --}}
@extends('layouts.master')

@section('title', 'Products SOP & Workflow')

@section('content')
@php
  /**
   * SOURCE OF TRUTH: your sidebar
   * Routes + permissions are taken exactly from the sidebar snippet you posted.
   */
  $items = [
    [
      'title' => 'Units',
      'desc'  => 'Define measurement units used across products and stock movements (e.g., pcs, kg, carton).',
      'route' => 'admin.inventory.products.units.index',
      'perm'  => 'inventory.products.units.view',
      'icon'  => 'fas fa-ruler-combined',
      'actions'=> [
        'Create/Update unit name & symbol',
        'Assign units to Products',
        'Standardise reporting & stock accuracy'
      ]
    ],
    [
      'title' => 'Manufacturers',
      'desc'  => 'Maintain manufacturers/suppliers of origin for product identity and reporting.',
      'route' => 'admin.inventory.products.manufacturers.list',
      'perm'  => 'inventory.products.manufacturers.view',
      'icon'  => 'fas fa-industry',
      'actions'=> [
        'Create/Update manufacturer',
        'Link brands under a manufacturer',
        'Support product sourcing reports'
      ]
    ],
    [
      'title' => 'Brands',
      'desc'  => 'Maintain brands tied to manufacturers for structured product cataloguing.',
      'route' => 'admin.inventory.products.brands.list',
      'perm'  => 'inventory.products.brands.view',
      'icon'  => 'fas fa-copyright',
      'actions'=> [
        'Create/Update brand',
        'Link brand to manufacturer',
        'Filter products by brand'
      ]
    ],
    [
      'title' => 'Attribute Types',
      'desc'  => 'Define attribute groups like Size, Color, Material, Model, Strength.',
      'route' => 'admin.inventory.products.attributes.types.index',
      'perm'  => 'inventory.products.attribute_types.view',
      'icon'  => 'fas fa-layer-group',
      'actions'=> [
        'Create attribute groups (types)',
        'Control how attributes are organised',
        'Enable structured variants later'
      ]
    ],
    [
      'title' => 'Attributes',
      'desc'  => 'Define attributes under types (e.g., Color, Size, Material).',
      'route' => 'admin.inventory.products.attributes.index',
      'perm'  => 'inventory.products.attributes.view',
      'icon'  => 'fas fa-sliders-h',
      'actions'=> [
        'Create attributes',
        'Attach attribute to a type',
        'Prepare for variant generation'
      ]
    ],
    [
      'title' => 'Attribute Values',
      'desc'  => 'Create the values used by attributes (e.g., Red/Blue, Small/Medium).',
      'route' => 'admin.inventory.products.attributes.values.index',
      'perm'  => 'inventory.products.attribute_values.view',
      'icon'  => 'fas fa-list-ul',
      'actions'=> [
        'Create values per attribute',
        'Keep values consistent and reusable',
        'Support variant combinations'
      ]
    ],
    [
      'title' => 'Categories',
      'desc'  => 'Organise products into category trees for navigation and reporting.',
      'route' => 'admin.inventory.products.categories.index',
      'perm'  => 'inventory.products.categories.view',
      'icon'  => 'fas fa-sitemap',
      'actions'=> [
        'Create/update categories',
        'Assign products to categories',
        'Enable category-level reporting'
      ]
    ],
    [
      'title' => 'Products',
      'desc'  => 'Create the main product record (name, code, unit, brand, category, etc.).',
      'route' => 'admin.inventory.products.index',
      'perm'  => 'inventory.products.view',
      'icon'  => 'fas fa-box',
      'actions'=> [
        'Create/update product master data',
        'Assign brand, category, unit',
        'Control active/inactive products'
      ]
    ],
    [
      'title' => 'Product Variants',
      'desc'  => 'Create sellable/stockable SKUs derived from product + attribute values.',
      'route' => 'admin.inventory.products.variants.index',
      'perm'  => 'inventory.products.variants.view',
      'icon'  => 'fas fa-cubes',
      'actions'=> [
        'Create/update SKU variants',
        'Assign attribute values per variant',
        'Variants drive stock-level tracking'
      ]
    ],
  ];

  $diagramSteps = [
    ['t' => 'Units', 'i' => 'fas fa-ruler-combined'],
    ['t' => 'Manufacturers', 'i' => 'fas fa-industry'],
    ['t' => 'Brands', 'i' => 'fas fa-copyright'],
    ['t' => 'Categories', 'i' => 'fas fa-sitemap'],
    ['t' => 'Products', 'i' => 'fas fa-box'],
    ['t' => 'Attribute Types', 'i' => 'fas fa-layer-group'],
    ['t' => 'Attributes', 'i' => 'fas fa-sliders-h'],
    ['t' => 'Attribute Values', 'i' => 'fas fa-list-ul'],
    ['t' => 'Variants (SKUs)', 'i' => 'fas fa-cubes'],
  ];
@endphp

<style>
  /* Bigger, clearer text */
  .doc-wrap { font-size: 15px; line-height: 1.7; color:#1f2937; }
  .doc-wrap .text-muted { font-size: 14px; }
  .hero {
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
  .pill{
    display:inline-flex; align-items:center; gap:.5rem;
    padding: 5px 10px;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,.10);
    background:#fff;
    font-size: 13px;
    white-space: nowrap;
  }
  .pill.ok{ border-color: rgba(28,200,138,.25); color:#1cc88a; }
  .pill.warn{ border-color: rgba(246,194,62,.35); color:#b58a00; }
  .pill.info{ border-color: rgba(78,115,223,.25); color:#4e73df; }
  .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,"Liberation Mono","Courier New", monospace;
    font-size: 13px;
    color:#6b7280;
  }

  /* Diagram */
  .flow{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:center;
  }
  .node{
    display:flex; align-items:center; gap:10px;
    padding: 10px 12px;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 14px;
    background:#fff;
    box-shadow: 0 6px 12px rgba(2,6,23,.04);
    min-width: 180px;
  }
  .node .ic{
    width: 38px; height: 38px;
    border-radius: 12px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(78,115,223,.12);
    color:#4e73df;
  }
  .arrow{
    font-size: 20px;
    color: rgba(78,115,223,.7);
    padding: 0 2px;
  }
  @media (max-width: 992px){
    .arrow{ display:none; }
    .node{ min-width: 100%; }
  }
</style>

<div class="container-fluid doc-wrap">

  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h3 mb-1 text-gray-800">Products — SOP & Workflow</h1>
      <div class="text-muted">
        This explains how <strong>Inventory → Products</strong> works, and the correct order to set up master data.
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
    <div class="d-flex flex-wrap justify-content-between" style="gap:1rem;">
      <div style="max-width: 800px;">
        <h5 class="mb-2 text-gray-800">Golden Rule</h5>
        <div class="alert alert-info mb-0" style="font-size:15px;">
          Build product master data in the right order so variants, stock entries and reports remain consistent.
          <br>
          <strong>Variants (SKUs) are what stock is tracked against</strong>.
        </div>
      </div>
      <div class="d-flex flex-wrap" style="gap:.5rem; align-items:flex-start;">
        <span class="pill info"><i class="fas fa-project-diagram"></i> Setup first</span>
        <span class="pill ok"><i class="fas fa-check-circle"></i> Keep consistent</span>
        <span class="pill warn"><i class="fas fa-exclamation-triangle"></i> Avoid duplicates</span>
      </div>
    </div>
  </div>

  {{-- Diagram --}}
  <div class="cardx mb-4">
    <div class="card-header bg-white">
      <h6 class="m-0 font-weight-bold text-primary">Workflow Diagram</h6>
      <div class="text-muted">Suggested setup sequence for clean data and reliable reporting.</div>
    </div>
    <div class="card-body">
      <div class="flow">
        @foreach($diagramSteps as $idx => $s)
          <div class="node">
            <div class="ic"><i class="{{ $s['i'] }}"></i></div>
            <div>
              <div class="font-weight-bold">{{ $s['t'] }}</div>
              <div class="text-muted" style="font-size:13px;">Step {{ $idx + 1 }}</div>
            </div>
          </div>
          @if($idx < count($diagramSteps)-1)
            <div class="arrow"><i class="fas fa-long-arrow-alt-right"></i></div>
          @endif
        @endforeach
      </div>
      <hr>
      <div class="text-muted">
        Notes: You may create Categories earlier or later depending on your catalog design.
        Attributes/values can be maintained continuously as new SKUs are introduced.
      </div>
    </div>
  </div>

  {{-- Detailed SOP cards --}}
  <div class="cardx">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
      <h6 class="m-0 font-weight-bold text-primary">Detailed SOP (by menu item)</h6>
      <div class="mono">Visibility uses your sidebar permissions: inventory.products.*.view</div>
    </div>

    <div class="card-body">
      <div class="row">
        @foreach($items as $it)
          <div class="col-lg-6 mb-3">
            <div class="card border-0" style="border-radius:16px; border:1px solid rgba(0,0,0,.06) !important;">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="d-flex" style="gap:10px;">
                    <div class="ic" style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(78,115,223,.12);color:#4e73df;">
                      <i class="{{ $it['icon'] }}"></i>
                    </div>
                    <div>
                      <div class="font-weight-bold" style="font-size:16px;">{{ $it['title'] }}</div>
                      <div class="text-muted">{{ $it['desc'] }}</div>
                    </div>
                  </div>
                  @can($it['perm'])
                    <span class="pill ok"><i class="fas fa-check-circle"></i> You can view</span>
                  @else
                    <span class="pill warn"><i class="fas fa-lock"></i> No access</span>
                  @endcan
                </div>

                <hr>
                <div class="font-weight-bold mb-1">Typical actions</div>
                <ul class="mb-3">
                  @foreach($it['actions'] as $a)
                    <li>{{ $a }}</li>
                  @endforeach
                </ul>

                <div class="d-flex flex-wrap" style="gap:.5rem;">
                  @can($it['perm'])
                    <a class="btn btn-sm btn-outline-primary" href="{{ route($it['route']) }}">
                      <i class="fas fa-external-link-alt mr-1"></i> Open {{ $it['title'] }}
                    </a>
                  @endcan
                  <span class="pill" title="Permission key">{{ $it['perm'] }}</span>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="alert alert-light mb-0" style="font-size:15px;">
        <strong>Operational control:</strong> Avoid deleting master data that is already used in Products/Variants/Stock.
        Prefer deactivation (recommended) so history remains intact.
      </div>
    </div>
  </div>

</div>
@endsection
