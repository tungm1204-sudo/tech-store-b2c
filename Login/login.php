<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } else {
        try {
            $statement = db()->prepare(
                'SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user !== false && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $update = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                    $update->execute([
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $user['id'],
                    ]);
                }

                login_user($user);
                header('Location: ' . app_url('dashboard.php'));
                exit;
            }

            $error = 'Email hoặc mật khẩu không chính xác.';
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'Không thể đăng nhập lúc này. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
        }
    }
}

render_header('Đăng nhập');
?>
<section class="card narrow">
    <h1>Đăng nhập</h1>
    <?php if (isset($_GET['registered'])): ?>
        <div class="success" role="status">Đăng ký thành công. Bạn có thể đăng nhập.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('login.php')) ?>">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?= e($email) ?>">

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="button full-width" type="submit">Đăng nhập</button>
    </form>
    <p class="form-footer">
        <a href="<?= e(app_url('forgot_password.php')) ?>">Quên mật khẩu?</a>
        <span aria-hidden="true"> · </span>
        Chưa có tài khoản? <a href="<?= e(app_url('register.php')) ?>">Đăng ký</a>
    </p>
</section>
<?php render_footer(); ?>
