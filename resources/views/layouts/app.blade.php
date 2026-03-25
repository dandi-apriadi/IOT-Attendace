<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'IoT Attendance System' }} | Poltek Manado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/premium-design.css') }}">
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-microchip"></i>
                <span>TECH-PRESENCE</span>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>

                @if (auth()->user()?->role === 'admin')
                    <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Master Data</div>

                    <div class="nav-item"><a href="{{ route('mahasiswa') }}" class="nav-link {{ Route::is('mahasiswa') ? 'active' : '' }}"><i class="fas fa-user-graduate"></i> Mahasiswa</a></div>
                    <div class="nav-item"><a href="{{ route('matakuliah') }}" class="nav-link {{ Route::is('matakuliah') ? 'active' : '' }}"><i class="fas fa-book"></i> Mata Kuliah</a></div>
                    <div class="nav-item"><a href="{{ route('kelas') }}" class="nav-link {{ Route::is('kelas') ? 'active' : '' }}"><i class="fas fa-school"></i> Kelas</a></div>
                    <div class="nav-item"><a href="{{ route('jadwal') }}" class="nav-link {{ Route::is('jadwal') ? 'active' : '' }}"><i class="fas fa-calendar-alt"></i> Jadwal</a></div>
                @endif

                <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Operational</div>
                
                <div class="nav-item"><a href="{{ route('dosen-session') }}" class="nav-link {{ Route::is('dosen-session') ? 'active' : '' }}"><i class="fas fa-play-circle"></i> Buka Sesi</a></div>
                <div class="nav-item"><a href="{{ route('monitoring') }}" class="nav-link {{ Route::is('monitoring') ? 'active' : '' }}"><i class="fas fa-tv"></i> Live Monitoring</a></div>
                <div class="nav-item"><a href="{{ route('iot-health') }}" class="nav-link {{ Route::is('iot-health') ? 'active' : '' }}"><i class="fas fa-server"></i> IoT Status</a></div>

                <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Admin & Reports</div>
                
                <div class="nav-item"><a href="{{ route('reports') }}" class="nav-link {{ Route::is('reports') ? 'active' : '' }}"><i class="fas fa-file-invoice"></i> Laporan</a></div>
                @if (auth()->user()?->role === 'admin')
                    <div class="nav-item"><a href="{{ route('audit-log') }}" class="nav-link {{ Route::is('audit-log') ? 'active' : '' }}"><i class="fas fa-history"></i> Audit Log</a></div>
                @endif
                <div class="nav-item"><a href="{{ route('settings') }}" class="nav-link {{ Route::is('settings') ? 'active' : '' }}"><i class="fas fa-cog"></i> Settings</a></div>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="nav-link" style="width: 100%; border: 0; background: transparent; text-align: left; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header>
                <div class="header-left">
                    <h1 class="display-font">{{ $title ?? 'Dashboard' }}</h1>
                    <p style="color: var(--text-muted);">{{ $subtitle ?? 'Selamat datang kembali, Admin.' }}</p>
                </div>
                <div class="header-right" style="display: flex; align-items: center; gap: 1rem;">
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; background: #E6F6EC; color: #1DB173; padding: 0.5rem 1rem; border-radius: 999px;">
                        <span style="width: 8px; height: 8px; background: #1DB173; border-radius: 50%; display: inline-block;"></span> Server Online
                    </div>
                    <div class="user-profile" style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="text-align: right;">
                            <div style="font-weight: 700; font-size: 0.9rem;">Dandi Apriadi</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Administrator</div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Dandi+Apriadi&background=003366&color=fff" alt="User" style="width: 40px; height: 40px; border-radius: 12px;">
                    </div>
                </div>
            </header>

            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>
    @yield('scripts')
</body>
</html>
