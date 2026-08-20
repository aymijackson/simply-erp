<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    {{-- Sidebar Toggle (Topbar) --}}
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
        <i class="fa fa-bars"></i>
    </button>

    {{-- Topbar Search (Desktop) --}}
    <form class="d-none d-sm-inline-block form-inline me-auto ms-md-3 my-2 my-md-0 mw-100 navbar-search position-relative" onsubmit="return false;">
        <div class="input-group">
            <input type="text"
                   id="globalSearchInput"
                   class="form-control bg-light border-0 small"
                   placeholder="Search..."
                   aria-label="Search"
                   autocomplete="off"
                   aria-describedby="topbar-search">
            <button class="btn btn-primary" id="topbar-search" type="button">
                <i class="fas fa-search fa-sm"></i>
            </button>
        </div>
        <div id="globalSearchResults" class="global-search-results d-none"></div>
    </form>

    {{-- Topbar Navbar --}}
    <ul class="navbar-nav ms-auto">

        {{-- Nav Item - Quick dark/light toggle --}}
        <li class="nav-item">
            <button type="button" class="nav-link border-0 bg-transparent" id="themeQuickToggle" aria-label="Toggle dark mode">
                <i class="fas fa-moon fa-fw"></i>
            </button>
        </li>

        {{-- Nav Item - Search Dropdown (Visible Only XS) --}}
        <li class="nav-item dropdown d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-end p-3 shadow animated--grow-in"
                 aria-labelledby="searchDropdown" style="min-width: 18rem;">
                <form class="form-inline w-100 position-relative" onsubmit="return false;">
                    <div class="input-group">
                        <input type="text" id="globalSearchInputMobile" class="form-control bg-light border-0 small"
                               placeholder="Search..." aria-label="Search" autocomplete="off">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                    <div id="globalSearchResultsMobile" class="global-search-results d-none"></div>
                </form>
            </div>
        </li>

        {{-- Nav Item - Alerts --}}

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell fa-fw"></i>
                <span class="badge bg-danger badge-counter" id="notifCount" style="display:none;"></span>
            </a>
        
            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item text-center small text-gray-500" href="{{ route('admin.notifications.index') }}">
                    View All Notifications
                </a>
            </div>
        </li>
        {{-- Nav Item - Messages --}}
        @php
            $messageCount = $messageCount ?? 1;

            $messages = $messages ?? [
                [
                    'name' => 'System',
                    'time' => '58m',
                    'text' => 'Your scheduled backup completed successfully.',
                    'avatar' => null
                ],
            ];
        @endphp

        <li class="nav-item dropdown mx-1">
            <a class="nav-link dropdown-toggle position-relative" href="#" id="messagesDropdown"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-envelope fa-fw"></i>

                @if($messageCount > 0)
                    <span class="badge bg-danger badge-counter position-absolute"
                          style="top: 0; right: 0; transform: translate(35%, -25%);">
                        {{ $messageCount }}
                    </span>
                @endif
            </a>

            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in"
                 aria-labelledby="messagesDropdown" style="min-width: 20rem;">
                <h6 class="dropdown-header">Messages</h6>

                @forelse($messages as $m)
                    <a class="dropdown-item d-flex align-items-center" href="#">
                        <div class="dropdown-list-image me-3">
                            @if(!empty($m['avatar']))
                                <img class="rounded-circle" src="{{ $m['avatar'] }}" alt="...">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                     style="width: 40px; height: 40px; font-weight: 700;">
                                    {{ strtoupper(substr($m['name'], 0, 1)) }}
                                </div>
                            @endif
                            <div class="status-indicator bg-success"></div>
                        </div>

                        <div class="fw-bold">
                            <div class="text-truncate">{{ $m['text'] }}</div>
                            <div class="small text-gray-500">{{ $m['name'] }} · {{ $m['time'] }}</div>
                        </div>
                    </a>
                @empty
                    <div class="dropdown-item text-center small text-gray-500 py-3">
                        No new messages
                    </div>
                @endforelse

                <a class="dropdown-item text-center small text-gray-500" href="#">
                    Read more messages
                </a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        {{-- Nav Item - User Information --}}
        @php
            $user = auth()->user();
            $userName = $user->name ?? 'Admin User';

            // Adjust this if your user table uses another field for avatar
            $avatarUrl = $user->avatar_url ?? null;

            $initials = collect(explode(' ', trim($userName)))
                ->filter()
                ->map(fn($p) => mb_substr($p, 0, 1))
                ->take(2)
                ->implode('');
            $initials = strtoupper($initials ?: 'AU');
        @endphp

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
               id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="me-2 d-none d-lg-inline text-gray-600 small">{{ $userName }}</span>

                @if($avatarUrl)
                    <img class="img-profile rounded-circle" src="{{ $avatarUrl }}" alt="Avatar">
                @else
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center img-profile"
                         style="width: 2.2rem; height: 2.2rem; font-weight: 800;">
                        {{ $initials }}
                    </div>
                @endif
            </a>

            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in"
                 aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('admin.profile.index') ?? '#' }}">
                    <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                    Profile
                </a>
                <a class="dropdown-item" href="{{ route('admin.settings.index') ?? '#' }}">
                    <i class="fas fa-cogs fa-sm fa-fw me-2 text-gray-400"></i>
                    Settings
                </a>
                <a class="dropdown-item" href="{{ route('admin.audit.index') ?? '#' }}">
                    <i class="fas fa-list fa-sm fa-fw me-2 text-gray-400"></i>
                    Activity Log
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('admin.support.index') ?? '#' }}">
                    <i class="fas fa-life-ring fa-sm fa-fw me-2 text-gray-400"></i>
                    Help & Support
                </a>

                {{-- Logout --}}
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>
</nav>
<!-- End of Topbar -->

<script>
(function () {
    const SEARCH_URL = @json(route('admin.search'));

    function wireUpSearch(inputId, resultsId) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        if (!input || !results) return;

        let debounceTimer = null;
        let currentRequest = null;
        let activeIndex = -1;

        function hide() {
            results.classList.add('d-none');
            results.innerHTML = '';
            activeIndex = -1;
        }

        function itemEls() {
            return Array.from(results.querySelectorAll('.gsr-item'));
        }

        function setActive(idx) {
            const items = itemEls();
            items.forEach((el) => el.classList.remove('gsr-active'));
            if (items[idx]) {
                items[idx].classList.add('gsr-active');
                items[idx].scrollIntoView({ block: 'nearest' });
            }
            activeIndex = idx;
        }

        function render(groups) {
            if (!groups || groups.length === 0) {
                results.innerHTML = '<div class="gsr-empty">No matches found.</div>';
                results.classList.remove('d-none');
                return;
            }

            let html = '';
            groups.forEach((group) => {
                html += '<div class="gsr-group-label">' + escapeHtml(group.label) + '</div>';
                group.items.forEach((item) => {
                    html += '<a class="gsr-item" href="' + escapeHtml(item.url) + '">'
                        + '<div class="gsr-title">' + escapeHtml(item.title) + '</div>'
                        + (item.subtitle ? '<div class="gsr-subtitle">' + escapeHtml(item.subtitle) + '</div>' : '')
                        + '</a>';
                });
            });
            results.innerHTML = html;
            results.classList.remove('d-none');
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            })[c]);
        }

        function runSearch(term) {
            if (currentRequest) currentRequest.abort();

            const controller = new AbortController();
            currentRequest = controller;

            results.innerHTML = '<div class="gsr-loading">Searching…</div>';
            results.classList.remove('d-none');

            fetch(SEARCH_URL + '?q=' + encodeURIComponent(term), {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
            })
                .then((r) => r.json())
                .then((data) => render(data.groups))
                .catch((err) => {
                    if (err.name !== 'AbortError') hide();
                });
        }

        input.addEventListener('input', function () {
            const term = input.value.trim();
            clearTimeout(debounceTimer);

            if (term.length < 2) {
                hide();
                return;
            }

            debounceTimer = setTimeout(() => runSearch(term), 250);
        });

        input.addEventListener('keydown', function (e) {
            const items = itemEls();
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(Math.min(activeIndex + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(Math.max(activeIndex - 1, 0));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    window.location.href = items[activeIndex].getAttribute('href');
                }
            } else if (e.key === 'Escape') {
                hide();
            }
        });

        document.addEventListener('click', function (e) {
            if (!results.contains(e.target) && e.target !== input) {
                hide();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        wireUpSearch('globalSearchInput', 'globalSearchResults');
        wireUpSearch('globalSearchInputMobile', 'globalSearchResultsMobile');
    });

    function resolvedIsDark() {
        var attr = document.documentElement.getAttribute('data-mode');
        if (attr) return attr === 'dark';
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function setToggleIcon(btn) {
        var icon = btn.querySelector('i');
        if (!icon) return;
        icon.className = resolvedIsDark() ? 'fas fa-sun fa-fw' : 'fas fa-moon fa-fw';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('themeQuickToggle');
        if (!toggle) return;

        setToggleIcon(toggle);

        toggle.addEventListener('click', function () {
            var next = resolvedIsDark() ? 'light' : 'dark';
            document.documentElement.setAttribute('data-mode', next);
            document.documentElement.setAttribute('data-bs-theme', next);
            setToggleIcon(toggle);

            fetch(@json(route('admin.profile.theme')), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ theme_mode: next }),
            }).catch(function () {});
        });
    });
})();
</script>
