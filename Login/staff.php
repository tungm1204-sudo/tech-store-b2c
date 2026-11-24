<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('staff', 'admin');

render_header('Khu vực nhân viên');
?>
<section class="card">
    <p class="eyebrow">RBAC · STAFF</p>
    <h1>Khu vực nhân viên</h1>
    <p>Chỉ Nhân viên và Quản trị viên được phép truy cập trang này.</p>
    <a class="text-link" href="<?= e(app_url('dashboard.php')) ?>">Quay lại tài khoản</a>
</section>
<?php render_footer(); ?>
