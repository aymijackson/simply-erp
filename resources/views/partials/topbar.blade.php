<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    {{-- Sidebar Toggle (Topbar) --}}
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
        <i class="fa fa-bars"></i>
    </button>

    {{-- Topbar Search (Desktop) --}}
    <form class="d-none d-sm-inline-block form-inline me-auto ms-md-3 my-2 my-md-0 mw-100 navbar-search">
        <div class="input-group">
            <input type="text"
                   class="form-control bg-light border-0 small"
                   placeholder="Search..."
                   aria-label="Search"
                   aria-describedby="topbar-search">
            <button class="btn btn-primary" id="topbar-search" type="button">
                <i class="fas fa-search fa-sm"></i>
            </button>
        </div>
    </form>

    {{-- Topbar Navbar --}}
    <ul class="navbar-nav ms-auto">

        {{-- Nav Item - Search Dropdown (Visible Only XS) --}}
        <li class="nav-item dropdown d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-end p-3 shadow animated--grow-in"
                 aria-labelledby="searchDropdown" style="min-width: 18rem;">
                <form class="form-inline w-100">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-0 small"
                               placeholder="Search..." aria-label="Search">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
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
