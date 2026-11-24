<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$tokenIsValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$errors = [];
$success = false;

if ($tokenIsValid) {
    $findToken = db()->prepare(
        'SELECT id, user_id FROM password_reset_tokens
         WHERE token_hash = :token_hash AND expires_at > UTC_TIMESTAMP() LIMIT 1'
    );
    $findToken->execute(['token_hash' => hash('sha256', $token)]);
    $resetRecord = $findToken->fetch();
} else {
    $resetRecord = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($resetRecord === false) {
        http_response_code(400);
        $errors[] = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    } else {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu mới và phần xác nhận không khớp.';
        } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
            $errors[] = 'Mật khẩu mới phải có từ 8 đến 72 byte.';
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $lockToken = $pdo->prepare(
                    'SELECT id, user_id FROM password_reset_tokens
                     WHERE id = :id AND expires_at > UTC_TIMESTAMP() FOR UPDATE'
                );
                $lockToken->execute(['id' => $resetRecord['id']]);
                $lockedRecord = $lockToken->fetch();
                if ($lockedRecord === false) {
                    throw new DomainException('Liên kết đặt lại mật khẩu đã hết hạn hoặc đã được sử dụng.');
                }

                $updatePassword = $pdo->prepare(
                    'UPDATE users SET password_hash = :password_hash WHERE id = :user_id'
                );
                $updatePassword->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'user_id' => $lockedRecord['user_id'],
                ]);
                $deleteTokens = $pdo->prepare(
                    'DELETE FROM password_reset_tokens WHERE user_id = :user_id'
                );
                $deleteTokens->execute(['user_id' => $lockedRecord['user_id']]);
                $pdo->commit();
                $success = true;
            } catch (DomainException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($exception->getMessage());
                $errors[] = 'Không thể đặt lại mật khẩu lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

render_header('Đặt lại mật khẩu');
?>
<section class="card narrow">
    <h1>Đặt lại mật khẩu</h1>
    <?php if ($success): ?>
        <div class="success" role="status">Mật khẩu đã được cập nhật. <a href="<?= e(app_url('login.php')) ?>">Đăng nhập</a></div>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <div class="alert" role="alert">
                <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($resetRecord !== false): ?>
            <form method="post" action="<?= e(app_url('reset_password.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label for="new_password">Mật khẩu mới</label>
                <input id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                <label for="confirm_password">Nhập lại mật khẩu mới</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                <button class="button full-width" type="submit">Lưu mật khẩu mới</button>
            </form>
        <?php elseif ($errors === []): ?>
            <div class="alert" role="alert">Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.</div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
