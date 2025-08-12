{{-- ───────────────────────── Modal ───────────────────────── --}}
<div class="modal fade" id="entryModal" tabindex="-1" aria-labelledby="entryModalLabel" aria-hidden="true">
<div class="modal-dialog modal-fullscreen-lg-down modal-xl">
    <form id="entryForm" class="modal-content">
    @csrf
    <input type="hidden" id="entryId">

    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="entryModalLabel">Add Stock Entry</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        {{-- ---------- header ---------- --}}
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Store *</label>
                <select name="store_id" id="store_id" class="form-select form-control" required>
                    <option value="">-- Select Store --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Entry Date *</label>
                <input type="date" name="entry_date" id="entry_date"
                    class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Reference #</label>
                <input type="text" name="reference" id="reference" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Entry Type *</label>
                <select name="entry_type" id="entry_type" class="form-select form-control" required>
                    <option value="normal"   selected>Normal Entry</option>
                    <option value="cust_return">Customer Return</option>
                </select>
            </div>

            {{-- Supplier select2 (normal only) --}}
            <div class="col-md-4 entry-supplier">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" id="supplier_id"
                        class="form-select"></select>
            </div>

            {{-- Customer select2 (return only) --}}
            <div class="col-md-4 entry-customer d-none">
                <label class="form-label">Customer *</label>
                <select name="customer_id" id="customer_id"
                        class="form-select"></select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-select  form-control">
                    <option value="draft"    selected>Draft</option>
                    <option value="approved">Approved</option>
                    <option value="posted">Posted</option>
                </select>
            </div>
        </div>

        <hr>

        {{-- ---------- Lines ---------- --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Lines</h5>
            <button type="button" id="addLineBtn" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Add Line
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="linesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:35%">Variant</th>
                        <th style="width:15%">Qty</th>
                        <th style="width:20%">Unit Cost</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" id="cancelEntryBtn" class="btn btn-secondary"  data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save Entry</button>
    </div>
    </form>
</div>
</div>