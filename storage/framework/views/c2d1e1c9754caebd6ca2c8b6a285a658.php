

<?php $__env->startSection('content'); ?>
<div style="max-width: 1100px; margin: 0 auto;">
    <div class="glass-card" style="margin-bottom: 1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h3 class="display-font" style="margin:0;">Detail Sesi Jadwal</h3>
                <div style="margin-top:0.4rem; color:#6b7280; font-size:0.88rem;">
                    <?php echo e($mataKuliah->nama_mk); ?> (<?php echo e($mataKuliah->kode_mk); ?>) · Kelas <?php echo e($kelas->nama_kelas); ?> · <?php echo e(\Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y')); ?>

                </div>
            </div>
            <div style="display:flex; gap:0.45rem; flex-wrap:wrap;">
                <a href="<?php echo e(route('dosen-schedule.detail.export.excel', ['date' => $selectedDate, 'mata_kuliah_id' => $mataKuliah->id, 'kelas_id' => $kelas->id])); ?>" class="btn-kinetic" style="text-decoration:none; padding:0.6rem 0.9rem; font-size:0.82rem;">
                    Export Excel
                </a>
                <a href="<?php echo e(route('dosen-schedule.detail.export.pdf', ['date' => $selectedDate, 'mata_kuliah_id' => $mataKuliah->id, 'kelas_id' => $kelas->id])); ?>" target="_blank" class="btn-kinetic" style="text-decoration:none; padding:0.6rem 0.9rem; font-size:0.82rem; background:#0066CC;">
                    Export PDF
                </a>
                <a href="<?php echo e(route('dosen-courses')); ?>" class="btn-kinetic" style="text-decoration:none; padding:0.6rem 0.9rem; font-size:0.82rem; background:#374151;">
                    Kembali
                </a>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('dosen-schedule.detail')); ?>" style="display:grid; grid-template-columns: 1fr auto; gap:0.6rem; margin-top: 1rem; max-width: 420px;">
            <input type="hidden" name="mata_kuliah_id" value="<?php echo e($mataKuliah->id); ?>" />
            <input type="hidden" name="kelas_id" value="<?php echo e($kelas->id); ?>" />
            <input type="date" name="date" value="<?php echo e($selectedDate); ?>" style="padding:0.62rem 0.72rem; border:1px solid #e5e7eb; border-radius:10px;" />
            <button type="submit" class="btn-kinetic" style="border:none; padding:0.62rem 0.82rem;">Terapkan</button>
        </form>
    </div>

    <div style="display:grid; grid-template-columns: repeat(6, minmax(120px, 1fr)); gap:0.75rem; margin-bottom: 1rem;">
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#6b7280; text-transform:uppercase;">Total</div>
            <div style="font-size:1.5rem; font-weight:800;"><?php echo e($summary['total_students']); ?></div>
        </div>
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#1DB173; text-transform:uppercase;">Hadir</div>
            <div style="font-size:1.5rem; font-weight:800; color:#1DB173;"><?php echo e($summary['hadir']); ?></div>
        </div>
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#F59E0B; text-transform:uppercase;">Telat</div>
            <div style="font-size:1.5rem; font-weight:800; color:#F59E0B;"><?php echo e($summary['telat']); ?></div>
        </div>
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#0066CC; text-transform:uppercase;">Sakit/Izin</div>
            <div style="font-size:1.5rem; font-weight:800; color:#0066CC;"><?php echo e($summary['sakit'] + $summary['izin']); ?></div>
        </div>
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#BA1A1A; text-transform:uppercase;">Alpa</div>
            <div style="font-size:1.5rem; font-weight:800; color:#BA1A1A;"><?php echo e($summary['alpa']); ?></div>
        </div>
        <div class="glass-card" style="padding:0.85rem;">
            <div style="font-size:0.72rem; color:#6b7280; text-transform:uppercase;">Pending</div>
            <div style="font-size:1.5rem; font-weight:800; color:#6b7280;"><?php echo e($summary['pending']); ?></div>
        </div>
    </div>

    <div class="glass-card">
        <h4 class="display-font" style="margin-bottom: 1rem; font-size: 1.1rem;">Daftar Siswa dan Status Absensi</h4>
        <table style="width:100%; font-size:0.85rem;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:0.75rem; border-bottom:2px solid #e5e7eb;">NIM</th>
                    <th style="text-align:left; padding:0.75rem; border-bottom:2px solid #e5e7eb;">Nama</th>
                    <th style="text-align:center; padding:0.75rem; border-bottom:2px solid #e5e7eb;">Status</th>
                    <th style="text-align:center; padding:0.75rem; border-bottom:2px solid #e5e7eb;">Metode</th>
                    <th style="text-align:center; padding:0.75rem; border-bottom:2px solid #e5e7eb;">Waktu Tap</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $studentRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $statusBadge = \App\Support\StatusBadge::forAbsensi((string) ($row['status'] ?? ''));
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:0.75rem; font-family:monospace; color:#0066CC;"><?php echo e($row['nim']); ?></td>
                        <td style="padding:0.75rem;"><?php echo e($row['nama']); ?></td>
                        <td style="padding:0.75rem; text-align:center;">
                            <span style="display:inline-block; padding:0.25rem 0.72rem; border-radius:999px; font-size:0.74rem; font-weight:700; background: <?php echo e($statusBadge['bg']); ?>; color: <?php echo e($statusBadge['text']); ?>;">
                                <?php echo e($row['status'] === 'Pending' ? 'Belum Absensi' : $row['status']); ?>

                            </span>
                        </td>
                        <td style="padding:0.75rem; text-align:center; color:#6b7280;"><?php echo e($row['metode']); ?></td>
                        <td style="padding:0.75rem; text-align:center; color:#6b7280;"><?php echo e($row['waktu_tap']); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" style="padding:1.5rem; text-align:center; color:#6b7280;">Belum ada data siswa untuk kelas ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/dosen/session-detail.blade.php ENDPATH**/ ?>