<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

render_header('Khu vực quản trị');
?>
<section class="card">
    <p class="eyebrow">RBAC · ADMIN</p>
    <h1>Khu vực quản trị</h1>
    <p>Chỉ Quản trị viên được phép truy cập trang này.</p>
    <a class="text-link" href="<?= e(app_url('dashboard.php')) ?>">Quay lại tài khoản</a>
</section>
<?php render_footer(); ?>
