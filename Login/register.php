<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($fullName === '' || strlen($fullName) > 100) {
        $errors[] = 'Họ và tên không được để trống và tối đa 100 ký tự.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Mật khẩu phải có từ 8 đến 72 byte.';
    } else {
        try {
            $statement = db()->prepare(
                'INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)'
            );
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            header('Location: ' . app_url('login.php?registered=1'));
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Email này đã được sử dụng.';
            } else {
                error_log($exception->getMessage());
                $errors[] = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

render_header('Đăng ký');
?>
<section class="card narrow">
    <h1>Tạo tài khoản</h1>
    <p class="muted">Tài khoản đăng ký mới luôn có quyền Khách hàng.</p>
    <?php if ($errors !== []): ?>
        <div class="alert" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('register.php')) ?>">
        <?= csrf_field() ?>
        <label for="full_name">Họ và tên</label>
        <input id="full_name" name="full_name" type="text" maxlength="100" autocomplete="name" required value="<?= e($fullName) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?= e($email) ?>">

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <p class="muted small">Mật khẩu có độ dài từ 8 đến 72 byte.</p>

        <button class="button full-width" type="submit">Đăng ký</button>
    </form>
    <p class="form-footer">Đã có tài khoản? <a href="<?= e(app_url('login.php')) ?>">Đăng nhập</a></p>
</section>
<?php render_footer(); ?>
