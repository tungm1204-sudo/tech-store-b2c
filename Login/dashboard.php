<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$roleLabels = [
    'customer' => 'Khách hàng',
    'staff' => 'Nhân viên',
    'admin' => 'Quản trị viên',
];

render_header('Tài khoản');
?>
<section class="card">
    <p class="eyebrow">KHU VỰC TÀI KHOẢN</p>
    <h1>Xin chào, <?= e($user['full_name']) ?></h1>
    <dl class="details">
        <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
        <div><dt>Vai trò</dt><dd><span class="badge"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span></dd></div>
    </dl>
    <p>Đăng nhập thành công. Các chức năng quản lý sẽ được mở theo vai trò của tài khoản.</p>
    <div class="actions">
        <a class="button button-secondary" href="<?= e(app_url('profile.php')) ?>">Hồ sơ &amp; sổ địa chỉ</a>
        <a class="button button-secondary" href="<?= e(app_url('change_password.php')) ?>">Đổi mật khẩu</a>
    </div>
    <?php if ($user['role'] === 'staff'): ?>
        <a class="button" href="<?= e(app_url('staff.php')) ?>">Khu vực nhân viên</a>
    <?php elseif ($user['role'] === 'admin'): ?>
        <a class="button" href="<?= e(app_url('admin.php')) ?>">Khu vực quản trị</a>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
