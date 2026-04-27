

<?php $__env->startSection('title', 'Data Master Mata Kuliah'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mata Kuliah</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Mata Kuliah</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total <?php echo e(number_format($mataKuliahList->total())); ?> mata kuliah</span>
    </div>
    
    <?php if(session('success')): ?>
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <!-- Filter Semester -->
    <form method="GET" style="display: flex; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 12px;">
        <div>
            <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280; text-transform:uppercase;">Filter Semester</label>
            <select name="semester_id" class="form-input" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                <?php $__currentLoopData = $semesterList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sem->id); ?>" <?php echo e($selectedSemesterId == $sem->id ? 'selected' : ''); ?>>
                        <?php echo e($sem->display_name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <button type="submit" class="btn-kinetic" style="padding: 0.6rem 1.2rem;">
            <i class="fas fa-filter"></i> Terapkan
        </button>
    </form>

    <form action="<?php echo e(route('matakuliah.store')); ?>" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 0.75rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px;">
        <?php echo csrf_field(); ?>
        <div class="form-group" style="margin-bottom:0;">
            <input name="kode_mk" type="text" value="<?php echo e(old('kode_mk')); ?>" placeholder="Kode MK (ex: IF101)" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input name="nama_mk" type="text" value="<?php echo e(old('nama_mk')); ?>" placeholder="Nama Mata Kuliah" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input name="sks" type="number" value="<?php echo e(old('sks')); ?>" placeholder="SKS" class="form-control" min="1" max="6" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <select name="semester_akademik_id" class="form-control">
                <option value="">-- Pilih Semester --</option>
                <?php $__currentLoopData = $semesterList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sem->id); ?>"><?php echo e($sem->display_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Tambah MK</button>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Semester</th>
                <th>Penggunaan</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $mataKuliahList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 700;"><?php echo e($mk->kode_mk); ?></td>
                    <td><?php echo e($mk->nama_mk); ?></td>
                    <td><?php echo e($mk->sks); ?> SKS</td>
                    <td>
                        <?php if($mk->semesterAkademik): ?>
                            <span style="background: #E6F6EC; color: #1DB173; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                <?php echo e($mk->semesterAkademik->display_name); ?>

                            </span>
                        <?php else: ?>
                            <span style="color: #6b7280; font-size: 0.8rem;">Belum ditentukan</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e(number_format($mk->jadwal_count)); ?> Sesi Kuliah</td>
                    <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <a href="<?php echo e(route('matakuliah.report', $mk->id)); ?>" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #E6F6EC; color: #1DB173; box-shadow: none; text-decoration: none;" title="Lihat Report Absensi">
                            <i class="fas fa-chart-bar"></i>
                        </a>
                        <a href="<?php echo e(route('matakuliah.edit', $mk->id)); ?>" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); box-shadow: none; text-decoration: none;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="<?php echo e(route('matakuliah.destroy', $mk->id)); ?>" method="POST" onsubmit="return confirm('Hapus mata kuliah ini?');" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#6b7280;">Belum ada data mata kuliah.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-container">
        <?php echo e($mataKuliahList->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/master/matakuliah.blade.php ENDPATH**/ ?>