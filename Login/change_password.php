<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu mới và phần xác nhận không khớp.';
        } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
            $errors[] = 'Mật khẩu mới phải có từ 8 đến 72 byte.';
        } else {
            $statement = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
            $statement->execute(['id' => current_user()['id']]);
            $user = $statement->fetch();

            if ($user === false || !password_verify($currentPassword, $user['password_hash'])) {
                $errors[] = 'Mật khẩu hiện tại không chính xác.';
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                    $update->execute([
                        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'id' => current_user()['id'],
                    ]);
                    $deleteResetTokens = $pdo->prepare(
                        'DELETE FROM password_reset_tokens WHERE user_id = :user_id'
                    );
                    $deleteResetTokens->execute(['user_id' => current_user()['id']]);
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);
                $success = 'Mật khẩu đã được cập nhật.';
            }
        }
    }
}

render_header('Đổi mật khẩu');
?>
<section class="card narrow">
    <h1>Đổi mật khẩu</h1>
    <?php if ($errors !== []): ?>
        <div class="alert" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?><div class="success" role="status"><?= e($success) ?></div><?php endif; ?>
    <form method="post" action="<?= e(app_url('change_password.php')) ?>">
        <?= csrf_field() ?>
        <label for="current_password">Mật khẩu hiện tại</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        <label for="new_password">Mật khẩu mới</label>
        <input id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <label for="confirm_password">Nhập lại mật khẩu mới</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <button class="button full-width" type="submit">Cập nhật mật khẩu</button>
    </form>
</section>
<?php render_footer(); ?>
