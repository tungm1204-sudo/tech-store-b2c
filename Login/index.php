<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

render_header('Cửa hàng đồ công nghệ');
?>
<section class="hero">
    <p class="eyebrow">ĐỒ CÔNG NGHỆ CHÍNH HÃNG</p>
    <h1>Chào mừng đến với Tech Gadgets</h1>
    <p>Mô-đun xác thực và phân quyền cho website thương mại điện tử đồ công nghệ.</p>
    <div class="actions">
        <a class="button" href="<?= e(app_url('register.php')) ?>">Tạo tài khoản</a>
        <a class="button button-secondary" href="<?= e(app_url('login.php')) ?>">Đăng nhập</a>
    </div>
</section>
<?php render_footer(); ?>
