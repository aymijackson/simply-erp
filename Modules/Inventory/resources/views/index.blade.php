@extends('inventory::layouts.master')

@section('title', 'Inventory Dashboard')

@section('content')
    <!-- Inventory Dashboard Content -->
    <div class="row">
      <!-- Metrics Cards -->
      <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
          <span class="mask bg-primary opacity-10 border-radius-lg"></span>
          <div class="card-body p-3 position-relative">
            <div class="row">
              <div class="col-8 text-start">
                <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                  <i class="fas fa-box text-dark text-gradient text-lg opacity-10"></i>
                </div>
                <h5 class="text-white font-weight-bolder mb-0 mt-3">
                  {{ $itemsInStock }}
                </h5>
                <span class="text-white text-sm">Items in Stock</span>
              </div>
              <div class="col-4 text-end">
                <p class="text-white text-sm font-weight-bolder mt-auto mb-0">+8%</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
          <span class="mask bg-warning opacity-10 border-radius-lg"></span>
          <div class="card-body p-3 position-relative">
            <div class="row">
              <div class="col-8 text-start">
                <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                  <i class="fas fa-chart-bar text-dark text-gradient text-lg opacity-10"></i>
                </div>
                <h5 class="text-white font-weight-bolder mb-0 mt-3">
                  {{ $lowStockCount }}
                </h5>
                <span class="text-white text-sm">Low Stock</span>
              </div>
              <div class="col-4 text-end">
                <p class="text-white text-sm font-weight-bolder mt-auto mb-0">-3%</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
          <span class="mask bg-danger opacity-10 border-radius-lg"></span>
          <div class="card-body p-3 position-relative">
            <div class="row">
              <div class="col-8 text-start">
                <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                  <i class="fas fa-shopping-basket text-dark text-gradient text-lg opacity-10"></i>
                </div>
                <h5 class="text-white font-weight-bolder mb-0 mt-3">
                  {{ $outOfStockCount }}
                </h5>
                <span class="text-white text-sm">Out of Stock</span>
              </div>
              <div class="col-4 text-end">
                <p class="text-white text-sm font-weight-bolder mt-auto mb-0">+5%</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
          <span class="mask bg-info opacity-10 border-radius-lg"></span>
          <div class="card-body p-3 position-relative">
            <div class="row">
              <div class="col-8 text-start">
                <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                  <i class="fas fa-shopping-cart text-dark text-gradient text-lg opacity-10"></i>
                </div>
                <h5 class="text-white font-weight-bolder mb-0 mt-3">
                  {{ $newOrders }}
                </h5>
                <span class="text-white text-sm">New Orders</span>
              </div>
              <div class="col-4 text-end">
                <p class="text-white text-sm font-weight-bolder mt-auto mb-0">+12%</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions / Stock Movements -->
    <div class="row">
      <div class="col-lg-8 col-md-12 mb-4">
        <div class="card">
          <div class="card-header pb-0">
            <h6>Recent Transactions</h6>
          </div>
          <div class="card-body px-0 pb-2">
            <div class="table-responsive">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recentTransactions as $transaction)
                  <tr>
                    <td>{{ $transaction->product->product_name }}</td>
                    <td>
                      <span class="badge {{ $transaction->movement_type == 'inbound' ? 'bg-success' : 'bg-danger' }}">
                        {{ ucfirst($transaction->movement_type) }}
                      </span>
                    </td>
                    <td class="text-center">{{ $transaction->quantity }}</td>
                    <td class="text-center">{{ $transaction->created_at->format('Y-m-d') }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales or Stock Chart -->
      <div class="col-lg-4 col-md-12">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h6>Stock Overview</h6>
            <p class="text-sm">
              <i class="fa fa-arrow-up text-success"></i>
              <span class="font-weight-bold">24%</span> increase this month
            </p>
          </div>
          <div class="card-body p-3">
            <div class="chart">
              <canvas id="stock-chart" class="chart-canvas" height="300"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
@endsection
