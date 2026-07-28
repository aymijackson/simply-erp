<div class="modal fade" id="settingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="settingModalTitle">New Setting</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="settingForm">
          <input type="hidden" id="setting_id" name="id">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Group</label>
              <select class="form-control" name="setting_group_id" id="setting_group_id" required>
                @foreach($groups as $g)
                  <option value="{{ $g->id }}">{{ strtoupper($g->module) }} / {{ $g->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Key</label>
              <input type="text" class="form-control" name="key" id="key" required placeholder="e.g. receipt_footer_note">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Label</label>
              <input type="text" class="form-control" name="label" id="label" placeholder="e.g. Receipt Footer Note">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Type</label>
              <select class="form-control" name="value_type" id="value_type" required>
                <option value="string">string</option>
                <option value="text">text</option>
                <option value="int">int</option>
                <option value="decimal">decimal</option>
                <option value="bool">bool</option>
                <option value="json">json</option>
                <option value="date">date</option>
                <option value="datetime">datetime</option>
                <option value="file">file</option>
                <option value="email">email</option>
                <option value="phone">phone</option>
                <option value="url">url</option>
              </select>
            </div>

            <div class="col-md-12 mb-3">
              <label class="text-muted small">Description</label>
              <input type="text" class="form-control" name="description" id="description" placeholder="Explain what this setting controls...">
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Scope</label>
              <select class="form-control" name="scope" id="scope" required>
                <option value="global">global</option>
                <option value="company">company</option>
                <option value="location">location</option>
                <option value="user">user</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="text-muted small">Scope ID</label>
              <input type="number" class="form-control" name="scope_id" id="scope_id" placeholder="company_id / location_id / user_id">
            </div>

            <div class="col-md-12 mb-2">
              <label class="text-muted small">Value</label>
              <div id="valueInputWrap"></div>
            </div>

            <div class="col-md-12 mb-3 d-none" id="fileUploadWrap">
              <div class="border rounded p-2">
                <div class="d-flex align-items-center gap-2">
                  <input type="file" class="form-control" id="settingFile">
                  <button type="button" class="btn btn-outline-primary" id="uploadFileBtn">
                    <i class="fas fa-upload"></i> Upload
                  </button>
                </div>
                <input type="hidden" id="uploadedFilePath">
                <small class="text-muted">Uploads to: <code>storage/settings</code></small>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                <label class="custom-control-label" for="is_active">Active</label>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_required" name="is_required" value="1">
                <label class="custom-control-label" for="is_required">Required</label>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_sensitive" name="is_sensitive" value="1">
                <label class="custom-control-label" for="is_sensitive">Sensitive</label>
              </div>
            </div>

            <div class="col-md-3 mb-2">
              <label class="text-muted small">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" id="sort_order" value="0">
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveSettingBtn">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>
