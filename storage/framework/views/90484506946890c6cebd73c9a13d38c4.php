

<?php $__env->startSection('title', 'Manajemen Semester Akademik'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Semester Akademik</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Manajemen Semester Akademik</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total <?php echo e(number_format($semesterList->total())); ?> semester</span>
    </div>

    <?php if(session('success')): ?>
        <div style="margin-bottom: 1.5rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            <strong>✓ Berhasil!</strong> <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div style="margin-bottom: 1.5rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <strong>✗ Gagal!</strong> <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <!-- Add Semester Form -->
    <div class="glass-card" style="background: #f8fafc; padding: 1.25rem; margin-bottom: 2rem;">
        <h4 class="display-font" style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted); text-transform: uppercase;">Tambah Semester Baru</h4>
        <form action="<?php echo e(route('semester.store')); ?>" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 0.75rem; align-items: end;">
            <?php echo csrf_field(); ?>
            <div>
                <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280;">Nama Semester</label>
                <input name="nama_semester" type="text" placeholder="Ganjil/Genap" class="form-input" required>
            </div>
            <div>
                <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280;">Tahun Ajaran</label>
                <input name="tahun_ajaran" type="text" placeholder="2025/2026" class="form-input" required>
            </div>
            <div>
                <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280;">Tanggal Mulai</label>
                <input name="tanggal_mulai" type="date" class="form-input" required>
            </div>
            <div>
                <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280;">Tanggal Selesai</label>
                <input name="tanggal_selesai" type="date" class="form-input" required>
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Simpan</button>
            </div>
        </form>
    </div>

    <!-- Semester List Table -->
    <table style="width: 100%; font-size: 0.9rem;">
        <thead>
            <tr>
                <th style="text-align: left;">Semester</th>
                <th style="text-align: left;">Tahun Ajaran</th>
                <th style="text-align: center;">Periode</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Jadwal</th>
                <th style="text-align: center; width: 220px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $semesterList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $semester): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 1rem 0;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <?php if($semester->is_active): ?>
                                <span style="width: 10px; height: 10px; background: #1DB173; border-radius: 50%; display: inline-block;"></span>
                            <?php endif; ?>
                            <span style="font-weight: 600;"><?php echo e($semester->nama_semester); ?></span>
                        </div>
                    </td>
                    <td style="padding: 1rem 0; color: #6b7280;"><?php echo e($semester->tahun_ajaran); ?></td>
                    <td style="padding: 1rem 0; text-align: center; color: #6b7280; font-size: 0.85rem;">
                        <?php echo e($semester->tanggal_mulai?->format('d M Y') ?? '-'); ?> s/d <?php echo e($semester->tanggal_selesai?->format('d M Y') ?? '-'); ?>

                    </td>
                    <td style="padding: 1rem 0; text-align: center;">
                        <?php if($semester->is_active): ?>
                            <span style="background: #E6F6EC; color: #1DB173; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                                Aktif
                            </span>
                        <?php else: ?>
                            <span style="background: #F1F3F5; color: #6b7280; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                                Tidak Aktif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem 0; text-align: center; color: #6b7280; font-size: 0.85rem;">
                        <?php echo e($semester->jadwal()->count()); ?> jadwal
                    </td>
                    <td style="padding: 1rem 0; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <?php if(!$semester->is_active): ?>
                                <form action="<?php echo e(route('semester.set-active', $semester->id)); ?>" method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-kinetic" style="padding: 0.5rem; background: #E6F6EC; color: #1DB173; border: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;"
                                        title="Aktifkan semester ini">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo e(route('semester.edit', $semester->id)); ?>" class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5; color: #000; text-decoration: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="<?php echo e(route('semester.destroy', $semester->id)); ?>" method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn-kinetic" style="padding: 0.5rem; background: #FEE2E2; color: #BA1A1A; border: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;"
                                    onclick="return confirm('Hapus semester <?php echo e($semester->display_name); ?>?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data semester</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-container">
        <?php echo e($semesterList->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/master/semester.blade.php ENDPATH**/ ?>