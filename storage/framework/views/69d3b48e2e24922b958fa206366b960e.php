

<?php $__env->startSection('title', 'Pengaturan Akun'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <span>Pengaturan</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div style="max-width: 800px;">
    <?php if(session('success')): ?>
        <div style="background: #E6F6EC; color: #1DB173; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #1DB173;">
            <strong>✓ Berhasil!</strong> <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <div class="glass-card" style="margin-bottom: 2rem;">
        <h3 class="display-font" style="margin-bottom: 2rem;">Pengaturan Akun</h3>
        <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 3rem;">
            <img src="https://ui-avatars.com/api/?name=<?php echo e(urlencode($user->name)); ?>&background=003366&color=fff" style="width: 100px; height: 100px; border-radius: var(--radius-xl);">
            <div>
                <div style="font-weight: 700;"><?php echo e($user->name); ?></div>
                <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;"><?php echo e($user->email); ?></div>
                <div style="font-size: 0.75rem; background: #F1F3F5; color: #6b7280; padding: 0.25rem 0.75rem; border-radius: 999px; display: inline-block; margin-top: 0.75rem;"><?php echo e(ucfirst($user->role)); ?></div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">Avatar dihasilkan otomatis dari nama (read-only).</p>
            </div>
        </div>

        <form action="<?php echo e(route('profile.update')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control" name="name" value="<?php echo e(old('name', $user->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span style="font-size: 0.75rem; color: #BA1A1A;"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" name="email" value="<?php echo e(old('email', $user->email)); ?>" required>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span style="font-size: 0.75rem; color: #BA1A1A;"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <button type="submit" class="btn-kinetic" style="margin-top: 2rem; border: none; cursor: pointer;">UPDATE PROFIL</button>
        </form>
    </div>

    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 1.5rem; color: #BA1A1A;">Keamanan</h3>
        <form action="<?php echo e(route('profile.password')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Password Saat Ini</label>
                <input type="password" class="form-control" name="current_password" placeholder="Masukkan password lama" required>
                <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span style="font-size: 0.75rem; color: #BA1A1A;"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" class="form-control" name="password" placeholder="Minimum 8 karakter" required>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span style="font-size: 0.75rem; color: #BA1A1A;"><?php echo e($message); ?></span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" class="form-control" name="password_confirmation" placeholder="Ulangi password baru" required>
                </div>
            </div>
            <button type="submit" class="btn-kinetic" style="margin-top: 2rem; border: none; cursor: pointer;">UBAH PASSWORD</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\IOT-Attendace\resources\views/profile/settings.blade.php ENDPATH**/ ?>