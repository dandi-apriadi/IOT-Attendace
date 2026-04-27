<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'IoT Attendance System'); ?> | Poltek Manado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/premium-design.css')); ?>">
    <?php echo $__env->yieldContent('styles'); ?>
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
                    <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php echo e(Route::is('dashboard*') ? 'active' : ''); ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>

                <?php if(auth()->user()?->role === 'admin'): ?>
                    <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Master Data</div>

                    <div class="nav-item"><a href="<?php echo e(route('mahasiswa')); ?>" class="nav-link <?php echo e(Route::is('mahasiswa*') || Route::is('student*') ? 'active' : ''); ?>"><i class="fas fa-user-graduate"></i> Mahasiswa</a></div>
                    <div class="nav-item"><a href="<?php echo e(route('matakuliah')); ?>" class="nav-link <?php echo e(Route::is('matakuliah*') ? 'active' : ''); ?>"><i class="fas fa-book"></i> Mata Kuliah</a></div>
                    <div class="nav-item"><a href="<?php echo e(route('kelas')); ?>" class="nav-link <?php echo e(Route::is('kelas*') ? 'active' : ''); ?>"><i class="fas fa-school"></i> Kelas</a></div>
                    <div class="nav-item"><a href="<?php echo e(route('semester')); ?>" class="nav-link <?php echo e(Route::is('semester*') ? 'active' : ''); ?>"><i class="fas fa-calendar-check"></i> Semester Akademik</a></div>
                    <div class="nav-item"><a href="<?php echo e(route('jadwal')); ?>" class="nav-link <?php echo e(Route::is('jadwal*') ? 'active' : ''); ?>"><i class="fas fa-calendar-alt"></i> Jadwal</a></div>
                <?php endif; ?>

                <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Operational</div>
                
                <div class="nav-item"><a href="<?php echo e(route('dosen-courses')); ?>" class="nav-link <?php echo e(Route::is('dosen-courses*') ? 'active' : ''); ?>"><i class="fas fa-layer-group"></i> Mata Kuliah Saya</a></div>
                <div class="nav-item"><a href="<?php echo e(route('monitoring')); ?>" class="nav-link <?php echo e(Route::is('monitoring*') ? 'active' : ''); ?>"><i class="fas fa-tv"></i> Live Monitoring</a></div>
                <div class="nav-item"><a href="<?php echo e(route('iot-health')); ?>" class="nav-link <?php echo e(Route::is('iot-health*') ? 'active' : ''); ?>"><i class="fas fa-server"></i> IoT Status</a></div>

                <div style="margin: 1.5rem 0 0.5rem 1rem; font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.1em;">Admin & Reports</div>
                
                <div class="nav-item"><a href="<?php echo e(route('reports.index')); ?>" class="nav-link <?php echo e(Route::is('reports*') ? 'active' : ''); ?>"><i class="fas fa-file-invoice"></i> Laporan</a></div>
                <?php if(auth()->user()?->role === 'admin'): ?>
                    <div class="nav-item"><a href="<?php echo e(route('users')); ?>" class="nav-link <?php echo e(Route::is('users*') ? 'active' : ''); ?>"><i class="fas fa-users-cog"></i> Manajemen User</a></div>
                    <div class="nav-item"><a href="<?php echo e(route('audit-log')); ?>" class="nav-link <?php echo e(Route::is('audit-log*') ? 'active' : ''); ?>"><i class="fas fa-history"></i> Audit Log</a></div>
                <?php endif; ?>
                <div class="nav-item"><a href="<?php echo e(route('settings')); ?>" class="nav-link <?php echo e(Route::is('settings*') ? 'active' : ''); ?>"><i class="fas fa-cog"></i> Settings</a></div>
            </nav>

            <div class="sidebar-footer">
                <form action="<?php echo e(route('logout')); ?>" method="POST" style="margin: 0;">
                    <?php echo csrf_field(); ?>
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
                    <div class="header-breadcrumb">
                        <span>IoT System</span>
                        <span class="breadcrumb-sep">/</span>
                        <?php echo $__env->yieldContent('breadcrumb'); ?>
                    </div>
                    <h1 class="display-font"><?php echo $__env->yieldContent('title', 'Dashboard Overview'); ?></h1>
                </div>
                <div class="header-right" style="display: flex; align-items: center; gap: 1.5rem;">
                    <div class="status-indicator" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; background: #E6F6EC; color: #1DB173; padding: 0.4rem 0.8rem; border-radius: 999px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                        <span style="width: 6px; height: 6px; background: #1DB173; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #1DB173;"></span> IoT Online
                    </div>
                    <div class="user-badge">
                        <div style="text-align: right; line-height: 1.2;">
                            <div style="font-weight: 800; font-size: 0.85rem; color: #FFFFFF;"><?php echo e(auth()->user()->name); ?></div>
                            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;"><?php echo e(strtoupper(auth()->user()->role)); ?></div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo e(urlencode(auth()->user()->name)); ?>&background=003366&color=fff&bold=true" alt="User" style="width: 38px; height: 38px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 30, 64, 0.15);">
                    </div>
                </div>
            </header>

            <section class="content">
                <?php echo $__env->yieldContent('content'); ?>
            </section>
        </main>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/layouts/app.blade.php ENDPATH**/ ?>