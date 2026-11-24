<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
        } else {
            $submitted = true;
            try {
                $findUser = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $findUser->execute(['email' => $email]);
                $user = $findUser->fetch();

                if ($user !== false) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $pdo = db();
                    $pdo->beginTransaction();
                    try {
                        $removeOld = $pdo->prepare(
                            'DELETE FROM password_reset_tokens WHERE user_id = :user_id'
                        );
                        $removeOld->execute(['user_id' => $user['id']]);
                        $saveToken = $pdo->prepare(
                            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                             VALUES (:user_id, :token_hash, :expires_at)'
                        );
                        $saveToken->execute([
                            'user_id' => $user['id'],
                            'token_hash' => $tokenHash,
                            'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
                        ]);
                        $pdo->commit();
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }

                    $resetUrl = app_absolute_url('reset_password.php?token=' . rawurlencode($token));
                    $subject = 'Đặt lại mật khẩu Tech Gadgets';
                    $message = "Bạn vừa yêu cầu đặt lại mật khẩu.\n\n"
                        . "Mở liên kết sau trong vòng 60 phút để tạo mật khẩu mới:\n"
                        . $resetUrl
                        . "\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.";
                    $headers = "From: Tech Gadgets <no-reply@localhost>\r\n"
                        . "Content-Type: text/plain; charset=UTF-8\r\n";

                    if (!mail($email, $subject, $message, $headers)) {
                        error_log('Không gửi được email đặt lại mật khẩu.');
                    }
                }
            } catch (Throwable $exception) {
                error_log($exception->getMessage());
                $errors[] = 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.';
                $submitted = false;
            }
        }
    }
}

render_header('Quên mật khẩu');
?>
<section class="card narrow">
    <h1>Quên mật khẩu</h1>
    <?php if ($submitted): ?>
        <div class="success" role="status">
            Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi đến hộp thư đó.
        </div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="alert" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!$submitted): ?>
        <p class="muted">Nhập email đã đăng ký. Liên kết đặt lại mật khẩu có hiệu lực trong 60 phút.</p>
        <form method="post" action="<?= e(app_url('forgot_password.php')) ?>">
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required>
            <button class="button full-width" type="submit">Gửi liên kết đặt lại</button>
        </form>
    <?php endif; ?>
    <p class="form-footer"><a href="<?= e(app_url('login.php')) ?>">Quay lại đăng nhập</a></p>
</section>
<?php render_footer(); ?>
