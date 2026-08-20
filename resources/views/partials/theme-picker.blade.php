{{--
    Shared appearance picker (Mode / Accent / Sidebar style).

    Params:
      $mode      - raw (unresolved) theme_mode for this layer: null|'light'|'dark'
      $accent    - resolved theme_accent for this layer (one of the 6 keys below)
      $sidebar   - resolved theme_sidebar for this layer: 'dark'|'light'
      $idSuffix  - string, unique per include (avoids duplicate DOM ids if ever
                   used twice on one page)
      $autosave  - bool. true: each click saves immediately via fetch() to
                   $saveUrl (Profile page). false: each click just updates a
                   hidden input in the surrounding form - the form's own Save
                   button persists it (Company edit modal).
      $saveUrl   - required when $autosave is true.
--}}
@php
    $accents = [
        'indigo'  => '#4338CA',
        'emerald' => '#059669',
        'sky'     => '#0284C7',
        'rose'    => '#BE123C',
        'amber'   => '#D97706',
        'slate'   => '#2563EB',
    ];
@endphp

<div class="theme-picker" id="themePicker-{{ $idSuffix }}"
     @if($autosave) data-autosave="1" data-save-url="{{ $saveUrl }}" @endif>

    <div class="theme-picker-group mb-3">
        <label class="form-label small fw-semibold d-block mb-2">Mode</label>
        <div class="btn-group theme-picker-buttons" data-field="theme_mode" role="group">
            <button type="button" class="btn btn-sm btn-outline-secondary theme-picker-btn {{ is_null($mode) ? 'active' : '' }}" data-value="">
                <i class="fas fa-desktop me-1"></i> System
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary theme-picker-btn {{ $mode === 'light' ? 'active' : '' }}" data-value="light">
                <i class="fas fa-sun me-1"></i> Light
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary theme-picker-btn {{ $mode === 'dark' ? 'active' : '' }}" data-value="dark">
                <i class="fas fa-moon me-1"></i> Dark
            </button>
        </div>
    </div>

    <div class="theme-picker-group mb-3">
        <label class="form-label small fw-semibold d-block mb-2">Accent Color</label>
        <div class="theme-picker-swatches" data-field="theme_accent">
            @foreach($accents as $key => $hex)
                <button type="button" class="theme-picker-swatch {{ $accent === $key ? 'active' : '' }}"
                        data-value="{{ $key }}" style="background-color: {{ $hex }};"
                        title="{{ ucfirst($key) }}" aria-label="{{ ucfirst($key) }} accent">
                </button>
            @endforeach
        </div>
    </div>

    <div class="theme-picker-group">
        <label class="form-label small fw-semibold d-block mb-2">Sidebar Style</label>
        <div class="btn-group theme-picker-buttons" data-field="theme_sidebar" role="group">
            <button type="button" class="btn btn-sm btn-outline-secondary theme-picker-btn {{ $sidebar === 'dark' ? 'active' : '' }}" data-value="dark">
                Dark
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary theme-picker-btn {{ $sidebar === 'light' ? 'active' : '' }}" data-value="light">
                Light
            </button>
        </div>
    </div>

    @unless($autosave)
        <input type="hidden" name="theme_mode" value="{{ $mode }}">
        <input type="hidden" name="theme_accent" value="{{ $accent }}">
        <input type="hidden" name="theme_sidebar" value="{{ $sidebar }}">
    @endunless
</div>

@once
    <script>
    (function () {
        function wireThemePicker(picker) {
            var autosave = picker.dataset.autosave === '1';
            var saveUrl = picker.dataset.saveUrl;

            picker.querySelectorAll('[data-field]').forEach(function (group) {
                var field = group.dataset.field;
                var buttons = group.querySelectorAll('[data-value]');

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var value = btn.dataset.value;

                        buttons.forEach(function (b) { b.classList.remove('active'); });
                        btn.classList.add('active');

                        if (field === 'theme_mode') {
                            if (value) {
                                document.documentElement.setAttribute('data-mode', value);
                            } else {
                                document.documentElement.removeAttribute('data-mode');
                            }
                        } else if (field === 'theme_accent') {
                            document.documentElement.setAttribute('data-accent', value);
                        } else if (field === 'theme_sidebar') {
                            document.documentElement.setAttribute('data-sidebar', value);
                        }

                        if (autosave && saveUrl) {
                            var payload = {};
                            payload[field] = value;

                            fetch(saveUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(payload),
                            }).catch(function () {});
                        } else {
                            var hidden = picker.querySelector('input[type="hidden"][name="' + field + '"]');
                            if (hidden) hidden.value = value;
                        }
                    });
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.theme-picker').forEach(wireThemePicker);
        });
    })();
    </script>
@endonce
