<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
       /**
     * Display a listing of the resource.
     */
  
        /**
     * Display the inventory dashboard.
     */
    public function index()
    {
        // Fetch stock statistics
        $totalStock = Product::sum('quantity');
        $lowStockCount = Product::whereColumn('quantity', '<=', 'reorder_level')->count();
        $outOfStockCount = Product::where('quantity', '=', 0)->count();
        $newOrdersCount = Order::whereDate('created_at', '>=', Carbon::now()->subMonth())->count();

        // Fetch recent stock movements (last 5 transactions)
        $recentTransactions = StockMovement::latest()
            ->take(5)
            ->get(['product_id', 'movement_type', 'quantity', 'created_at'])
            ->map(function ($transaction) {
                return [
                    'item' => $transaction->product->name,
                    'type' => $transaction->movement_type,
                    'quantity' => $transaction->quantity,
                    'date' => $transaction->created_at->format('Y-m-d'),
                ];
            });

        // Stock level trends for chart (last 6 months)
        $stockTrends = $this->getStockTrendData();

        return view('inventory::dashboard', compact(
            'totalStock',
            'lowStockCount',
            'outOfStockCount',
            'newOrdersCount',
            'recentTransactions',
            'stockTrends'
        ));
    }

    /**
     * Generate stock trend data for the last 6 months.
     */
    private function getStockTrendData()
    {
        $stockData = [];
        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('M');
        })->reverse();

        foreach ($months as $month) {
            $stockData[] = [
                'month' => $month,
                'stock_level' => StockMovement::whereMonth('created_at', Carbon::parse($month)->month)
                    ->sum('quantity'),
            ];
        }

        return $stockData;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inventory::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('inventory::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
