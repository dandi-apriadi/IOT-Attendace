

<?php $__env->startSection('title', 'Data Master Mahasiswa'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mahasiswa</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Mahasiswa</h3>
    </div>

    <?php if(session('success')): ?>
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    <form method="GET" action="<?php echo e(route('mahasiswa')); ?>" style="margin-bottom: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <input name="q" value="<?php echo e($search); ?>" type="text" style="flex: 1 1 260px; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" placeholder="Cari berdasarkan Nama atau NIM...">
        <select name="kelas_id" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px; min-width: 180px;">
            <option value="">Semua Kelas</option>
            <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kelas): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($kelas->id); ?>" <?php echo e((string) $kelasId === (string) $kelas->id ? 'selected' : ''); ?>><?php echo e($kelas->nama_kelas); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button class="btn-kinetic" type="submit">Filter</button>
    </form>

    <form action="<?php echo e(route('mahasiswa.store')); ?>" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 0.75rem; margin-bottom: 1.5rem;">
        <?php echo csrf_field(); ?>
        <input name="nim" type="text" value="<?php echo e(old('nim')); ?>" placeholder="NIM" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
        <input name="nama" type="text" value="<?php echo e(old('nama')); ?>" placeholder="Nama lengkap" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
        <select name="kelas_id" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
            <option value="">Pilih Kelas</option>
            <?php $__currentLoopData = $kelasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kelas): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($kelas->id); ?>" <?php echo e(old('kelas_id') == $kelas->id ? 'selected' : ''); ?>><?php echo e($kelas->nama_kelas); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input name="rfid_uid" type="text" value="<?php echo e(old('rfid_uid')); ?>" placeholder="RFID UID (opsional)" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        <input name="barcode_id" type="text" value="<?php echo e(old('barcode_id')); ?>" placeholder="Barcode ID (opsional)" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Tambah Mahasiswa</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Identitas IoT</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $mahasiswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mahasiswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <div style="font-family: monospace; font-weight: 700; color: var(--primary-blue-container); background: #F1F3F5; padding: 2px 8px; border-radius: 4px; display: inline-block;">
                            <?php echo e($mahasiswa->nim); ?>

                        </div>
                    </td>
                    <td style="font-weight: 700;"><?php echo e($mahasiswa->nama); ?></td>
                    <td><?php echo e($mahasiswa->kelas?->nama_kelas ?? '-'); ?></td>
                    <td>
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            <?php if($mahasiswa->rfid_uid): ?>
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">RFID</span>
                            <?php endif; ?>
                            <?php if($mahasiswa->barcode_id): ?>
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">BARCODE</span>
                            <?php endif; ?>
                            <?php if($mahasiswa->fingerprint_data): ?>
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">FINGERPRINT</span>
                            <?php endif; ?>
                            <?php if($mahasiswa->face_model_data): ?>
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">FACE</span>
                            <?php endif; ?>
                            <?php if(!$mahasiswa->rfid_uid && !$mahasiswa->barcode_id && !$mahasiswa->fingerprint_data && !$mahasiswa->face_model_data): ?>
                                <span style="font-size: 0.65rem; background: #F1F3F5; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-weight: 700;">BELUM TERDAFTAR</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="display:flex; gap: 0.5rem; align-items:center;">
                        <a href="<?php echo e(route('student-detail', ['id' => $mahasiswa->id])); ?>" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; text-decoration: none;"><i class="fas fa-eye"></i></a>
                        <a href="<?php echo e(route('mahasiswa.edit', $mahasiswa)); ?>" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; background: #F1F3F5; text-decoration:none;"><i class="fas fa-edit"></i></a>
                        <form action="<?php echo e(route('mahasiswa.destroy', $mahasiswa)); ?>" method="POST" onsubmit="return confirm('Hapus data mahasiswa ini?');" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; background: #FDECEC; color: #BA1A1A;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">Belum ada data mahasiswa.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination-container">
        <?php echo e($mahasiswaList->links()); ?>

    </div>
</div>

<div class="glass-card" style="background: var(--primary-blue-container); color: #fff;">
    <h3 class="display-font" style="margin-bottom: 1rem;">Registrasi Cepat Hardware</h3>
    <p style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 1.5rem;">Gunakan perintah API untuk mendaftarkan UID RFID mahasiswa langsung dari perangkat ESP32 di lab.</p>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <code style="background: rgba(255, 255, 255, 0.1); padding: 0.75rem 1.5rem; border-radius: 8px; flex-grow: 1;">POST /api/register-tag { "nim": "...", "uid": "..." }</code>
        <button class="btn-kinetic" style="white-space: nowrap;">KLIK UNTUK COPY</button>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/master/mahasiswa.blade.php ENDPATH**/ ?>