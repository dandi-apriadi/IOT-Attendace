

<?php $__env->startSection('title', 'IoT Status & Health'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <span>Operational</span>
    <span class="breadcrumb-sep">/</span>
    <span>IoT Status</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="stats-grid">
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">ONLINE DEVICES</div>
        <div style="font-size: 2.5rem; font-weight: 800; color: #1DB173;"><?php echo e($onlineDevices); ?> <span style="font-size: 1rem; opacity: 0.5;">/ <?php echo e($totalDevices); ?></span></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Aktif <?php echo e($activeDevices); ?> · Stale <?php echo e($staleDevices); ?></div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">AVG. LATENCY</div>
        <div style="font-size: 2.5rem; font-weight: 800;"><?php echo e($avgLatency); ?>ms</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Dari payload terakhir</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">TODAY'S PAYLOADS</div>
        <div style="font-size: 2.5rem; font-weight: 800;"><?php echo e(number_format($todaysPayloads)); ?></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Data absensi hari ini</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">LATENCY TREND (24H)</div>
        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.35rem;">AVG <?php echo e(number_format($latencyStats24h['avg_ms'] ?? 0, 2)); ?> ms</div>
        <div style="font-size: 0.9rem; color: #6b7280; margin-top: 0.25rem;">P95 <?php echo e(number_format($latencyStats24h['p95_ms'] ?? 0, 2)); ?> ms</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.4rem;">Latest <?php echo e(number_format($latencyStats24h['latest_ms'] ?? 0, 2)); ?> ms · Samples <?php echo e(number_format($latencyStats24h['sample_count'] ?? 0)); ?></div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">AUDIT EVENTS (24H)</div>
        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.35rem; color: #BA1A1A;">ERROR <?php echo e(number_format($eventsSummary24h['errors'] ?? 0)); ?></div>
        <div style="font-size: 0.9rem; color: #1DB173; margin-top: 0.25rem;">SUCCESS <?php echo e(number_format($eventsSummary24h['success'] ?? 0)); ?></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.4rem;">Total <?php echo e(number_format($eventsSummary24h['total'] ?? 0)); ?> event</div>
    </div>
</div>

<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Hardware Inventory & Status</h3>
    <table>
        <thead>
            <tr>
                <th>Device ID</th>
                <th>Nama Device</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th>Update Time</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 700;"><?php echo e($device->device_id); ?></td>
                    <td><?php echo e($device->name); ?></td>
                    <?php
                        $bg = '#EEF0F3';
                        $text = '#6b7280';

                        if ($device->computed_status === 'online') {
                            $bg = '#E6F6EC';
                            $text = '#1DB173';
                        } elseif ($device->computed_status === 'stale') {
                            $bg = '#FEF3C7';
                            $text = '#B45309';
                        } elseif ($device->computed_status === 'disabled') {
                            $bg = '#FADBD8';
                            $text = '#BA1A1A';
                        }
                    ?>
                    <td><span class="status-pill" style="background: <?php echo e($bg); ?>; color: <?php echo e($text); ?>;">
                        <?php echo e($device->computed_status_label); ?>

                    </span></td>
                    <td style="font-size: 0.85rem; color: #6b7280;"><?php echo e($device->last_seen_at?->diffForHumans() ?? '-'); ?></td>
                    <td style="font-size: 0.85rem; color: #6b7280;"><?php echo e($device->updated_at?->format('d M Y H:i:s') ?? '-'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #6b7280; padding: 2rem;">Belum ada device yang terdaftar</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="glass-card" style="margin-top: 2rem; background: #191C1E; color: #fff;">
    <h3 class="display-font" style="margin-bottom: 1.5rem; color: var(--kinetic-yellow);">Technical API Debug Console</h3>
    <div style="background: #000; padding: 1.5rem; border-radius: 12px; font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; overflow-x: auto;">
        <?php $__empty_1 = true; $__currentLoopData = $recentEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $level = str_contains($event->action, 'failed') ? 'ERROR' : ($event->action === 'login' ? 'SUCCESS' : 'INFO');
                $levelColor = $level === 'ERROR' ? '#e06c75' : ($level === 'SUCCESS' ? '#98c379' : '#61afef');
                $time = $event->created_at?->format('H:i:s') ?? '--:--:--';
            ?>
            <div style="color: #abb2bf;">
                [<?php echo e($time); ?>]
                <span style="color: <?php echo e($levelColor); ?>;"><?php echo e($level); ?></span>:
                <?php echo e($event->description ?? $event->action); ?>

                <?php if($event->user): ?>
                    <span style="color: #7f848e;">(user: <?php echo e($event->user->name); ?>)</span>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="color: #abb2bf;">Belum ada event audit di database.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/monitoring/health.blade.php ENDPATH**/ ?>