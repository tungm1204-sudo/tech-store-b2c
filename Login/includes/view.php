<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function valid_csrf_token(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function render_header(string $title): void
{
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Tech Gadgets</title>
        <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="<?= e(app_url('index.php')) ?>">Tech Gadgets</a>
        <nav>
            <?php if ($user !== null): ?>
                <a href="<?= e(app_url('dashboard.php')) ?>">Tài khoản</a>
                <a href="<?= e(app_url('profile.php')) ?>">Hồ sơ</a>
                <a href="<?= e(app_url('change_password.php')) ?>">Đổi mật khẩu</a>
                <form class="inline-form" action="<?= e(app_url('logout.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="nav-button" type="submit">Đăng xuất</button>
                </form>
            <?php else: ?>
                <a href="<?= e(app_url('login.php')) ?>">Đăng nhập</a>
                <a href="<?= e(app_url('register.php')) ?>">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
