<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationStore;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Procurement\Models\GoodsReceipt;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\PurchaseOrderLine;
use Yajra\DataTables\Facades\DataTables;

class GoodsReceiptController extends Controller
{
    private const GRN_STATUS_DRAFT     = 'draft';
    private const GRN_STATUS_APPROVED  = 'approved';
    private const GRN_STATUS_POSTED    = 'posted';
    private const GRN_STATUS_CANCELLED = 'cancelled';

    private const GRN_RECEIPT_PARTIAL  = 'partial';
    private const GRN_RECEIPT_COMPLETE = 'complete';

    private const PO_STATUS_DRAFT        = 'draft';
    private const PO_STATUS_APPROVED     = 'approved';
    private const PO_STATUS_ISSUED       = 'issued';
    private const PO_STATUS_PARTIAL_RCV  = 'partially_rcv';
    private const PO_STATUS_FULLY_RCV    = 'fully_rcv';
    private const PO_STATUS_CLOSED       = 'closed';
    private const PO_STATUS_CANCELLED    = 'cancelled';

    public function index()
    {
        return view('procurement.goods_receipts.index');
    }

    public function datatable(Request $request)
    {
        $query = GoodsReceipt::query()
            ->with(['purchaseOrder', 'supplier', 'deliveryLocation'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->receipt_status, fn ($q) => $q->where('receipt_status', $request->receipt_status))
            ->when($request->date_from, fn ($q) => $q->whereDate('receipt_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('receipt_date', '<=', $request->date_to))
            ->when($request->q, function ($q) use ($request) {
                $term = trim($request->q);

                $q->where(function ($sub) use ($term) {
                    $sub->where('grn_no', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('supplier_delivery_note_no', 'like', "%{$term}%")
                        ->orWhereHas('purchaseOrder', function ($po) use ($term) {
                            $po->where('po_no', 'like', "%{$term}%");
                        })
                        ->orWhereHas('supplier', function ($sp) use ($term) {
                            $sp->where('name', 'like', "%{$term}%");
                        });
                });
            });

        return DataTables::of($query)
            ->addColumn('po_no', function ($row) {
                return $row->purchaseOrder->po_no ?? ('PO #' . $row->purchase_order_id);
            })
            ->addColumn('supplier', fn ($row) => $row->supplier->name ?? '—')
            ->addColumn('location', fn ($row) => $row->deliveryLocation->name ?? '—')
            ->editColumn('receipt_date', function ($row) {
                return $row->receipt_date ? date('d-m-Y', strtotime($row->receipt_date)) : '—';
            })
            ->editColumn('status', function ($row) {
                $map = [
                    self::GRN_STATUS_DRAFT     => 'secondary',
                    self::GRN_STATUS_APPROVED  => 'info',
                    self::GRN_STATUS_POSTED    => 'success',
                    self::GRN_STATUS_CANCELLED => 'danger',
                ];

                $cls = $map[$row->status] ?? 'secondary';

                return '<span class="badge bg-' . $cls . '">' . strtoupper($row->status) . '</span>';
            })
            ->addColumn('receipt_state', function ($row) {
                if (!$row->receipt_status) {
                    return '—';
                }

                $map = [
                    self::GRN_RECEIPT_PARTIAL  => 'warning',
                    self::GRN_RECEIPT_COMPLETE => 'success',
                ];

                $cls = $map[$row->receipt_status] ?? 'secondary';

                return '<span class="badge bg-' . $cls . '">' . strtoupper($row->receipt_status) . '</span>';
            })
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->addColumn('actions', function ($row) {
                return view('procurement.goods_receipts.partials.actions', compact('row'))->render();
            })
            ->rawColumns(['status', 'receipt_state', 'actions'])
            ->make(true);
    }

    public function lookupPurchaseOrders(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = PurchaseOrder::query()
            ->with('supplier')
            ->whereIn('status', [
                self::PO_STATUS_APPROVED,
                self::PO_STATUS_ISSUED,
                self::PO_STATUS_PARTIAL_RCV,
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('po_no', 'like', "%{$q}%")
                        ->orWhereHas('supplier', fn ($sp) => $sp->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function ($po) {
                return [
                    'id'            => $po->id,
                    'text'          => ($po->po_no ?? ('PO #' . $po->id)) . ' - ' . ($po->supplier->name ?? 'No Supplier'),
                    'supplier_id'   => $po->supplier_id,
                    'supplier_name' => $po->supplier->name ?? null,
                ];
            });

        return response()->json(['results' => $rows]);
    }

    public function lookupSuppliers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = Supplier::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn ($s) => [
                'id'   => $s->id,
                'text' => $s->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupLocations(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = Location::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn ($l) => [
                'id'   => $l->id,
                'text' => $l->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupStores(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $locationId = $request->get('location_id');

        $rows = LocationStore::query()
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn ($s) => [
                'id'   => $s->id,
                'text' => $s->name,
            ]);

        return response()->json(['results' => $rows]);
    }

    public function lookupProductVariants(Request $request)
    {
        $productId = $request->get('product_id');
        $q = trim((string) $request->get('q', ''));

        $query = ProductVariant::query()->where('product_id', $productId);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('sku', 'like', "%{$q}%")
                    ->orWhere('item_type', 'like', "%{$q}%");
            });
        }

        $variants = $query
            ->orderBy('sku')
            ->limit(50)
            ->get()
            ->map(function ($v) {
                return [
                    'id'             => $v->id,
                    'text'           => $v->sku . ' (' . strtoupper($v->item_type) . ')',
                    'sku'            => $v->sku,
                    'item_type'      => $v->item_type,
                    'price'          => $v->price,
                    'stock_quantity' => $v->stock_quantity,
                ];
            });

        return response()->json(['results' => $variants]);
    }

    public function createFromPurchaseOrder($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'deliveryLocation',
            'deliveryStore',
            'lines.product',
            'lines.unit',
            'lines.productVariant',
        ])->findOrFail($id);

        $lines = $po->lines->map(function ($line) {
            $orderedQty = (float) ($line->qty ?? 0);
            $prevRecv = (float) ($line->received_qty ?? 0);
            $remaining = max(0, $orderedQty - $prevRecv);

            return [
                'purchase_order_line_id'   => $line->id,
                'product_id'               => $line->product_id,
                'product_variant_id'       => $line->product_variant_id ?? null,
                'product_variant_label'    => $line->productVariant
                    ? ($line->productVariant->sku . ' (' . strtoupper($line->productVariant->item_type) . ')')
                    : null,
                'unit_id'                  => $line->unit_id,
                'product_label'            => trim(($line->product->product_code ?? '') . ' - ' . ($line->product->product_name ?? 'Unknown Product'), ' -'),
                'description'              => $line->description ?? $line->product->product_description ?? null,
                'unit_label'               => $line->unit->name ?? $line->unit->symbol ?? '',
                'ordered_qty'              => $orderedQty,
                'previously_received_qty'  => $prevRecv,
                'received_qty'             => 0,
                'remaining_qty'            => $remaining,
                'unit_cost'                => (float) ($line->unit_price ?? 0),
                'line_total'               => 0,
                'accepted_qty'             => 0,
                'rejected_qty'             => 0,
                'damage_qty'               => 0,
                'batch_no'                 => null,
                'serial_no'                => null,
                'expiry_date'              => null,
                'remarks'                  => null,
            ];
        })->values();

        return response()->json([
            'header' => [
                'purchase_order_id'         => $po->id,
                'purchase_order_label'      => $po->po_no,
                'supplier_id'               => $po->supplier_id,
                'supplier_label'            => $po->supplier->name ?? null,
                'receipt_date'              => now()->format('Y-m-d'),
                'supplier_delivery_note_no' => null,
                'delivery_location_id'      => $po->delivery_location_id ?? null,
                'delivery_location_label'   => $po->deliveryLocation->name ?? null,
                'delivery_store_id'         => $po->delivery_store_id ?? null,
                'delivery_store_label'      => $po->deliveryStore->name ?? null,
                'reference'                 => $po->po_no,
                'notes'                     => null,
            ],
            'lines' => $lines,
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->lines as $line) {
                $this->validateVariantBelongsToProduct($line);
                $this->validateLineQuantities($line);
            }

            $grn = new GoodsReceipt();
            $grn->company_id = $this->resolveCompanyId();
            $grn->purchase_order_id = $request->purchase_order_id;
            $grn->supplier_id = $request->supplier_id;
            $grn->grn_no = $this->generateGrnNo();
            $grn->receipt_date = $request->receipt_date;
            $grn->supplier_delivery_note_no = $request->supplier_delivery_note_no;
            $grn->delivery_location_id = $request->delivery_location_id;
            $grn->delivery_store_id = $request->delivery_store_id;
            $grn->reference = $request->reference;
            $grn->notes = $request->notes;
            $grn->status = self::GRN_STATUS_DRAFT;
            $grn->receipt_status = null;
            $grn->received_by = Auth::id();
            $grn->created_by = Auth::id();
            $grn->updated_by = Auth::id();
            $grn->subtotal = 0;
            $grn->save();

            $this->syncGoodsReceiptLines($grn, $request->lines);

            DB::commit();

            return response()->json([
                'message' => 'Goods receipt saved successfully.',
                'id'      => $grn->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        $grn = GoodsReceipt::with([
            'purchaseOrder',
            'supplier',
            'deliveryLocation',
            'deliveryStore',
            'lines.product',
            'lines.productVariant',
            'lines.unit',
        ])->findOrFail($id);

        return response()->json([
            'goods_receipt' => [
                'id'                        => $grn->id,
                'purchase_order_id'         => $grn->purchase_order_id,
                'purchase_order_label'      => $grn->purchaseOrder->po_no ?? ('PO #' . $grn->purchase_order_id),
                'supplier_id'               => $grn->supplier_id,
                'supplier_label'            => $grn->supplier->name ?? null,
                'receipt_date'              => $grn->receipt_date ? date('Y-m-d', strtotime($grn->receipt_date)) : null,
                'supplier_delivery_note_no' => $grn->supplier_delivery_note_no,
                'delivery_location_id'      => $grn->delivery_location_id,
                'delivery_location_label'   => $grn->deliveryLocation->name ?? null,
                'delivery_store_id'         => $grn->delivery_store_id,
                'delivery_store_label'      => $grn->deliveryStore->name ?? null,
                'reference'                 => $grn->reference,
                'notes'                     => $grn->notes,
                'status'                    => $grn->status,
                'receipt_status'            => $grn->receipt_status,
                'subtotal'                  => $grn->subtotal,
            ],
            'lines' => $grn->lines->map(function ($line) {
                return [
                    'id'                       => $line->id,
                    'purchase_order_line_id'   => $line->purchase_order_line_id,
                    'product_id'               => $line->product_id,
                    'product_variant_id'       => $line->product_variant_id,
                    'product_variant_label'    => $line->productVariant
                        ? ($line->productVariant->sku . ' (' . strtoupper($line->productVariant->item_type) . ')')
                        : null,
                    'unit_id'                  => $line->unit_id,
                    'product_label'            => trim(($line->product->product_code ?? '') . ' - ' . ($line->product->product_name ?? ''), ' -'),
                    'description'              => $line->description,
                    'unit_label'               => $line->unit->name ?? $line->unit->symbol ?? '',
                    'ordered_qty'              => $line->ordered_qty,
                    'previously_received_qty'  => $line->previously_received_qty,
                    'received_qty'             => $line->received_qty,
                    'remaining_qty'            => $line->remaining_qty,
                    'unit_cost'                => $line->unit_cost,
                    'line_total'               => $line->line_total,
                    'accepted_qty'             => $line->accepted_qty,
                    'rejected_qty'             => $line->rejected_qty,
                    'damage_qty'               => $line->damage_qty,
                    'batch_no'                 => $line->batch_no,
                    'serial_no'                => $line->serial_no,
                    'expiry_date'              => $line->expiry_date ? date('Y-m-d', strtotime($line->expiry_date)) : null,
                    'remarks'                  => $line->remarks,
                ];
            })->values(),
        ]);
    }

    public function details($id)
    {
        $grn = GoodsReceipt::with([
            'purchaseOrder',
            'supplier',
            'deliveryLocation',
            'deliveryStore',
            'receiver',
            'poster',
            'approver',
            'lines.product',
            'lines.productVariant',
            'lines.unit',
        ])->findOrFail($id);

        return response()->json([
            'header' => [
                'id'                        => $grn->id,
                'grn_no'                    => $grn->grn_no,
                'receipt_date'              => $grn->receipt_date ? date('d-m-Y', strtotime($grn->receipt_date)) : '—',
                'supplier_delivery_note_no' => $grn->supplier_delivery_note_no,
                'po_no'                     => $grn->purchaseOrder->po_no ?? ('PO #' . $grn->purchase_order_id),
                'supplier'                  => $grn->supplier->name ?? '—',
                'delivery_location'         => $grn->deliveryLocation->name ?? '—',
                'delivery_store'            => $grn->deliveryStore->name ?? '—',
                'reference'                 => $grn->reference,
                'notes'                     => $grn->notes,
                'status'                    => strtoupper($grn->status),
                'receipt_status'            => $grn->receipt_status ? strtoupper($grn->receipt_status) : '—',
                'subtotal'                  => $grn->subtotal,
                'received_by'               => $grn->receiver->name ?? '—',
                'posted_by'                 => $grn->poster->name ?? '—',
                'posted_at'                 => $grn->posted_at ? date('d-m-Y', strtotime($grn->posted_at)) : '—',
            ],
            'lines' => $grn->lines->map(function ($line) {
                return [
                    'product_code'            => $line->product->product_code ?? null,
                    'product_name'            => $line->product->product_name ?? null,
                    'variant_sku'             => $line->productVariant->sku ?? null,
                    'variant_type'            => $line->productVariant->item_type ?? null,
                    'description'             => $line->description,
                    'unit_name'               => $line->unit->name ?? null,
                    'unit_symbol'             => $line->unit->symbol ?? null,
                    'ordered_qty'             => $line->ordered_qty,
                    'previously_received_qty' => $line->previously_received_qty,
                    'received_qty'            => $line->received_qty,
                    'remaining_qty'           => $line->remaining_qty,
                    'accepted_qty'            => $line->accepted_qty,
                    'rejected_qty'            => $line->rejected_qty,
                    'damage_qty'              => $line->damage_qty,
                    'batch_no'                => $line->batch_no,
                    'serial_no'               => $line->serial_no,
                ];
            })->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);

        if ($grn->status !== self::GRN_STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft goods receipts can be edited.',
            ], 422);
        }

        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->lines as $line) {
                $this->validateVariantBelongsToProduct($line);
                $this->validateLineQuantities($line);
            }

            $grn->purchase_order_id = $request->purchase_order_id;
            $grn->supplier_id = $request->supplier_id;
            $grn->receipt_date = $request->receipt_date;
            $grn->supplier_delivery_note_no = $request->supplier_delivery_note_no;
            $grn->delivery_location_id = $request->delivery_location_id;
            $grn->delivery_store_id = $request->delivery_store_id;
            $grn->reference = $request->reference;
            $grn->notes = $request->notes;
            $grn->updated_by = Auth::id();
            $grn->save();

            $grn->lines()->delete();
            $this->syncGoodsReceiptLines($grn, $request->lines);

            DB::commit();

            return response()->json([
                'message' => 'Goods receipt updated successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $grn = GoodsReceipt::findOrFail($id);

        if ($grn->status !== self::GRN_STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft goods receipts can be deleted.',
            ], 422);
        }

        $grn->delete();

        return response()->json([
            'message' => 'Goods receipt deleted successfully.',
        ]);
    }

    public function approve($id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);

        if ($grn->status !== self::GRN_STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft goods receipts can be approved.',
            ], 422);
        }

        foreach ($grn->lines as $line) {
            if ((float) $line->received_qty > 0 && !$line->product_variant_id) {
                return response()->json([
                    'message' => 'Cannot approve. One or more received lines are missing product variants.',
                ], 422);
            }
        }

        $grn->status = self::GRN_STATUS_APPROVED;
        $grn->approved_by = Auth::id();
        $grn->approved_at = now();
        $grn->updated_by = Auth::id();
        $grn->save();

        return response()->json([
            'message' => 'Goods receipt approved successfully.',
        ]);
    }

    public function receive($id)
    {
        return $this->post($id);
    }

    public function post($id)
    {
        $grn = GoodsReceipt::with([
            'purchaseOrder',
            'purchaseOrder.lines',
            'lines',
        ])->findOrFail($id);

        if ($grn->status !== self::GRN_STATUS_APPROVED) {
            return response()->json([
                'message' => 'Only approved goods receipts can be posted.',
            ], 422);
        }

        if (!$grn->delivery_store_id) {
            return response()->json([
                'message' => 'Delivery store is required before posting this goods receipt.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($grn->lines as $line) {
                if ((float) $line->accepted_qty > 0 && !$line->product_variant_id) {
                    throw new \Exception('Cannot post goods receipt. One or more lines are missing product variants.');
                }
            }

            $stockEntry = new StockEntry();
            $stockEntry->document_no = $grn->grn_no;
            $stockEntry->reference = $grn->reference;
            $stockEntry->reference_type = 'goods_receipt';
            $stockEntry->reference_id = $grn->id;
            $stockEntry->store_id = $grn->delivery_store_id;
            $stockEntry->supplier_id = $grn->supplier_id;
            $stockEntry->purchase_order_id = $grn->purchase_order_id;
            $stockEntry->status = 'approved';
            $stockEntry->entry_type = 'normal';
            $stockEntry->remarks = $grn->notes;
            $stockEntry->entry_date = $grn->receipt_date ?: now()->toDateString();
            $stockEntry->approved_by = $grn->approved_by ?: Auth::id();
            $stockEntry->approved_at = $grn->approved_at ?: now();
            //$stockEntry->posted_by = Auth::id();
           // $stockEntry->posted_at = now();
            $stockEntry->save();

            foreach ($grn->lines as $line) {
                $acceptedQty = (float) $line->accepted_qty;

                if ($acceptedQty <= 0) {
                    continue;
                }

                StockEntryLine::create([
                    'stock_entry_id'     => $stockEntry->id,
                    'product_variant_id' => $line->product_variant_id,
                    'qty'                => $acceptedQty,
                    'unit_cost'          => $line->unit_cost ?? 0,
                ]);

                $variant = ProductVariant::find($line->product_variant_id);
                if ($variant) {
                    $variant->stock_quantity = (float) $variant->stock_quantity + $acceptedQty;

                    if (is_null($variant->price) || (float) $variant->price <= 0) {
                        $variant->price = $line->unit_cost ?? 0;
                    }

                    $variant->save();
                }

                if ($line->product_id) {
                    DB::table('products')
                        ->where('id', $line->product_id)
                        ->increment('product_stock_quantity', $acceptedQty);
                }

                PurchaseOrderLine::where('id', $line->purchase_order_line_id)
                    ->update([
                        'received_qty' => DB::raw('COALESCE(received_qty, 0) + ' . $acceptedQty),
                        'updated_at'   => now(),
                    ]);
            }

            $this->refreshPurchaseOrderReceiptStatus($grn->purchase_order_id);

            $grn->stock_entry_id = $stockEntry->id;
            $grn->status = self::GRN_STATUS_POSTED;
            $grn->receipt_status = $this->determineGoodsReceiptStatus($grn);
            $grn->posted_by = Auth::id();
            $grn->posted_at = now();
            $grn->updated_by = Auth::id();
            $grn->save();

            DB::commit();

            return response()->json([
                'message' => 'Goods receipt posted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel($id)
    {
        $grn = GoodsReceipt::findOrFail($id);

        if (!in_array($grn->status, [self::GRN_STATUS_DRAFT, self::GRN_STATUS_APPROVED])) {
            return response()->json([
                'message' => 'Only draft or approved goods receipts can be cancelled.',
            ], 422);
        }

        $grn->status = self::GRN_STATUS_CANCELLED;
        $grn->receipt_status = null;
        $grn->cancelled_by = Auth::id();
        $grn->cancelled_at = now();
        $grn->updated_by = Auth::id();
        $grn->save();

        return response()->json([
            'message' => 'Goods receipt cancelled successfully.',
        ]);
    }

    public function pdf($id)
    {
        $grn = GoodsReceipt::with([
            'purchaseOrder',
            'supplier',
            'deliveryLocation',
            'deliveryStore',
            'receiver',
            'poster',
            'approver',
            'lines.product',
            'lines.productVariant',
            'lines.unit',
        ])->findOrFail($id);

        return view('procurement.goods_receipts.pdf', compact('grn'));
    }

    protected function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'purchase_order_id'               => ['required', 'integer', 'exists:proc_purchase_orders,id'],
            'supplier_id'                     => ['required', 'integer', 'exists:suppliers,id'],
            'receipt_date'                    => ['required', 'date'],
            'supplier_delivery_note_no'       => ['nullable', 'string', 'max:100'],
            'delivery_location_id'            => ['nullable', 'integer', 'exists:locations,id'],
            'delivery_store_id'               => ['nullable', 'integer', 'exists:location_stores,id'],
            'reference'                       => ['nullable', 'string', 'max:100'],
            'notes'                           => ['nullable', 'string'],
            'lines'                           => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id'  => ['required', 'integer'],
            'lines.*.product_id'              => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.product_variant_id'      => ['required', 'integer', 'exists:product_variants,id'],
            'lines.*.description'             => ['nullable', 'string'],
            'lines.*.unit_id'                 => ['nullable', 'integer'],
            'lines.*.ordered_qty'             => ['required', 'numeric', 'min:0'],
            'lines.*.previously_received_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.received_qty'            => ['required', 'numeric', 'min:0'],
            'lines.*.accepted_qty'            => ['nullable', 'numeric', 'min:0'],
            'lines.*.rejected_qty'            => ['nullable', 'numeric', 'min:0'],
            'lines.*.damage_qty'              => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost'               => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_no'                => ['nullable', 'string', 'max:100'],
            'lines.*.serial_no'               => ['nullable', 'string', 'max:255'],
            'lines.*.expiry_date'             => ['nullable', 'date'],
            'lines.*.remarks'                 => ['nullable', 'string', 'max:255'],
        ]);
    }

    protected function syncGoodsReceiptLines(GoodsReceipt $grn, array $lines): void
    {
        $subtotal = 0;

        foreach ($lines as $line) {
            $receivedQty = (float) ($line['received_qty'] ?? 0);
            $unitCost = (float) ($line['unit_cost'] ?? 0);
            $orderedQty = (float) ($line['ordered_qty'] ?? 0);
            $prevRecv = (float) ($line['previously_received_qty'] ?? 0);
            $remainingQty = max(0, $orderedQty - $prevRecv - $receivedQty);
            $lineTotal = $receivedQty * $unitCost;

            $grn->lines()->create([
                'purchase_order_line_id'   => $line['purchase_order_line_id'],
                'product_id'               => $line['product_id'] ?? null,
                'product_variant_id'       => $line['product_variant_id'] ?? null,
                'description'              => $line['description'] ?? null,
                'unit_id'                  => $line['unit_id'] ?? null,
                'ordered_qty'              => $orderedQty,
                'previously_received_qty'  => $prevRecv,
                'received_qty'             => $receivedQty,
                'remaining_qty'            => $remainingQty,
                'unit_cost'                => $unitCost,
                'line_total'               => $lineTotal,
                'accepted_qty'             => (float) ($line['accepted_qty'] ?? 0),
                'rejected_qty'             => (float) ($line['rejected_qty'] ?? 0),
                'damage_qty'               => (float) ($line['damage_qty'] ?? 0),
                'batch_no'                 => $line['batch_no'] ?? null,
                'serial_no'                => $line['serial_no'] ?? null,
                'expiry_date'              => $line['expiry_date'] ?? null,
                'remarks'                  => $line['remarks'] ?? null,
            ]);

            $subtotal += $lineTotal;
        }

        $grn->subtotal = $subtotal;
        $grn->save();
    }

    protected function refreshPurchaseOrderReceiptStatus(int $purchaseOrderId): void
    {
        $po = PurchaseOrder::with('lines')->findOrFail($purchaseOrderId);

        $lines = $po->lines;

        if ($lines->isEmpty()) {
            return;
        }

        $allReceived = $lines->every(function ($line) {
            return (float) $line->received_qty >= (float) $line->qty;
        });

        $anyReceived = $lines->contains(function ($line) {
            return (float) $line->received_qty > 0;
        });

        if ($allReceived) {
            $po->status = self::PO_STATUS_FULLY_RCV;
        } elseif ($anyReceived) {
            $po->status = self::PO_STATUS_PARTIAL_RCV;
        } else {
            if (!in_array($po->status, [self::PO_STATUS_APPROVED, self::PO_STATUS_ISSUED])) {
                $po->status = self::PO_STATUS_APPROVED;
            }
        }

        $po->updated_at = now();
        $po->save();
    }

    protected function determineGoodsReceiptStatus(GoodsReceipt $grn): string
    {
        $grn->loadMissing('lines');

        if ($grn->lines->isEmpty()) {
            return self::GRN_RECEIPT_PARTIAL;
        }

        $allComplete = $grn->lines->every(function ($line) {
            $remainingBeforeThisReceipt = max(
                0,
                (float) $line->ordered_qty - (float) $line->previously_received_qty
            );

            return (float) $line->received_qty >= $remainingBeforeThisReceipt;
        });

        return $allComplete ? self::GRN_RECEIPT_COMPLETE : self::GRN_RECEIPT_PARTIAL;
    }

    protected function resolveCompanyId()
    {
        if (
            Schema::hasColumn('proc_goods_receipts', 'company_id') &&
            Schema::hasTable('companies') &&
            Auth::user() &&
            isset(Auth::user()->company_id)
        ) {
            return Auth::user()->company_id;
        }

        return 1;
    }

    protected function generateGrnNo(): string
    {
        $lastId = (GoodsReceipt::max('id') ?? 0) + 1;
        return 'GRN-' . now()->format('Ymd') . '-' . str_pad($lastId, 5, '0', STR_PAD_LEFT);
    }

    protected function validateVariantBelongsToProduct(array $line): void
    {
        $productId = $line['product_id'] ?? null;
        $variantId = $line['product_variant_id'] ?? null;

        if (!$productId || !$variantId) {
            throw new \Exception('Product and product variant are required on each line.');
        }

        $variant = ProductVariant::where('id', $variantId)
            ->where('product_id', $productId)
            ->first();

        if (!$variant) {
            throw new \Exception('One or more selected variants do not belong to their products.');
        }
    }

    public function select2PurchaseOrders(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->get('q', ''));

        $rows = DB::table('proc_purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('po.company_id', $companyId)
            ->whereNull('po.deleted_at')
            ->whereIn('po.status', ['approved', 'issued', 'partially_received'])
            ->whereExists(function ($x) {
                $x->select(DB::raw(1))
                    ->from('proc_purchase_order_lines as l')
                    ->whereColumn('l.purchase_order_id', 'po.id')
                    ->whereRaw('(COALESCE(l.qty,0) - COALESCE(l.received_qty,0)) > 0');
            })
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('po.po_no', 'like', "%{$q}%")
                      ->orWhere('s.name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('po.id')
            ->limit(30)
            ->get([
                'po.id',
                'po.po_no',
                'po.supplier_id',
                's.name as supplier_name',
            ]);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => trim($r->po_no.' - '.($r->supplier_name ?? '')),
                'supplier_id' => $r->supplier_id,
                'supplier_name' => $r->supplier_name,
            ])->values(),
        ]);
    }


    protected function validateLineQuantities(array $line): void
    {
        $ordered = (float) ($line['ordered_qty'] ?? 0);
        $prev = (float) ($line['previously_received_qty'] ?? 0);
        $received = (float) ($line['received_qty'] ?? 0);
        $accepted = (float) ($line['accepted_qty'] ?? 0);
        $rejected = (float) ($line['rejected_qty'] ?? 0);
        $damaged = (float) ($line['damage_qty'] ?? 0);

        $maxRemaining = max(0, $ordered - $prev);

        if ($received > $maxRemaining + 0.0001) {
            throw new \Exception('Received quantity cannot exceed remaining quantity.');
        }

        if (($accepted + $rejected + $damaged) > ($received + 0.0001)) {
            throw new \Exception('Accepted + Rejected + Damaged cannot exceed Received Qty.');
        }
    }
}