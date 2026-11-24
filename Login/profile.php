<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$errors = [];
$success = '';
$profileName = $user['full_name'];
$profileEmail = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'update_profile') {
                $profileName = trim((string) ($_POST['full_name'] ?? ''));
                $profileEmail = strtolower(trim((string) ($_POST['email'] ?? '')));

                if ($profileName === '' || strlen($profileName) > 100) {
                    $errors[] = 'Họ và tên không được để trống và tối đa 100 ký tự.';
                } elseif (!filter_var($profileEmail, FILTER_VALIDATE_EMAIL) || strlen($profileEmail) > 190) {
                    $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
                } else {
                    $statement = db()->prepare(
                        'UPDATE users SET full_name = :full_name, email = :email WHERE id = :id'
                    );
                    $statement->execute([
                        'full_name' => $profileName,
                        'email' => $profileEmail,
                        'id' => $user['id'],
                    ]);
                    $_SESSION['user']['full_name'] = $profileName;
                    $_SESSION['user']['email'] = $profileEmail;
                    $user = current_user();
                    $success = 'Thông tin cá nhân đã được cập nhật.';
                }
            } elseif ($action === 'add_address' || $action === 'update_address') {
                $addressId = filter_var($_POST['address_id'] ?? null, FILTER_VALIDATE_INT);
                $address = [
                    'receiver_name' => trim((string) ($_POST['receiver_name'] ?? '')),
                    'phone' => trim((string) ($_POST['phone'] ?? '')),
                    'province' => trim((string) ($_POST['province'] ?? '')),
                    'district' => trim((string) ($_POST['district'] ?? '')),
                    'ward' => trim((string) ($_POST['ward'] ?? '')),
                    'street_address' => trim((string) ($_POST['street_address'] ?? '')),
                ];

                $limits = [
                    'receiver_name' => 100,
                    'phone' => 20,
                    'province' => 100,
                    'district' => 100,
                    'ward' => 100,
                    'street_address' => 255,
                ];

                foreach ($address as $field => $value) {
                    if ($value === '' || strlen($value) > $limits[$field]) {
                        $errors[] = 'Vui lòng điền đầy đủ địa chỉ và không vượt quá giới hạn ký tự.';
                        break;
                    }
                }

                if ($errors === [] && !preg_match('/^[0-9+().\-\s]{8,20}$/', $address['phone'])) {
                    $errors[] = 'Số điện thoại không hợp lệ.';
                }
                if ($action === 'update_address' && ($addressId === false || $addressId < 1)) {
                    $errors[] = 'Địa chỉ cần cập nhật không hợp lệ.';
                }

                if ($errors === []) {
                    $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1';
                    $pdo = db();
                    $pdo->beginTransaction();

                    try {
                        $lock = $pdo->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE');
                        $lock->execute(['id' => $user['id']]);

                        if ($action === 'update_address') {
                            $ownedAddress = $pdo->prepare(
                                'SELECT id FROM user_addresses WHERE id = :address_id AND user_id = :user_id'
                            );
                            $ownedAddress->execute([
                                'address_id' => $addressId,
                                'user_id' => $user['id'],
                            ]);
                            if ($ownedAddress->fetch() === false) {
                                throw new DomainException('Địa chỉ không tồn tại hoặc không thuộc tài khoản này.');
                            }
                        }

                        if ($isDefault) {
                            $clearDefault = $pdo->prepare(
                                'UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id'
                            );
                            $clearDefault->execute(['user_id' => $user['id']]);
                        }

                        if ($action === 'add_address') {
                            $hasAddress = $pdo->prepare(
                                'SELECT id FROM user_addresses WHERE user_id = :user_id LIMIT 1'
                            );
                            $hasAddress->execute(['user_id' => $user['id']]);
                            $isDefault = $isDefault || $hasAddress->fetch() === false;

                            $statement = $pdo->prepare(
                                'INSERT INTO user_addresses
                                    (user_id, receiver_name, phone, province, district, ward, street_address, is_default)
                                 VALUES
                                    (:user_id, :receiver_name, :phone, :province, :district, :ward, :street_address, :is_default)'
                            );
                            $statement->execute($address + [
                                'user_id' => $user['id'],
                                'is_default' => (int) $isDefault,
                            ]);
                        } else {
                            $statement = $pdo->prepare(
                                'UPDATE user_addresses
                                 SET receiver_name = :receiver_name, phone = :phone, province = :province,
                                     district = :district, ward = :ward, street_address = :street_address,
                                     is_default = :is_default
                                 WHERE id = :address_id AND user_id = :user_id'
                            );
                            $statement->execute($address + [
                                'is_default' => (int) $isDefault,
                                'address_id' => $addressId,
                                'user_id' => $user['id'],
                            ]);
                        }

                        $pdo->commit();
                        $success = $action === 'add_address'
                            ? 'Đã thêm địa chỉ giao hàng.'
                            : 'Đã cập nhật địa chỉ giao hàng.';
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }
                }
            } elseif ($action === 'delete_address' || $action === 'default_address') {
                $addressId = filter_var($_POST['address_id'] ?? null, FILTER_VALIDATE_INT);
                if ($addressId === false || $addressId < 1) {
                    $errors[] = 'Địa chỉ cần thao tác không hợp lệ.';
                } else {
                    $pdo = db();
                    $pdo->beginTransaction();

                    try {
                        $lock = $pdo->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE');
                        $lock->execute(['id' => $user['id']]);
                        $findAddress = $pdo->prepare(
                            'SELECT id, is_default FROM user_addresses WHERE id = :address_id AND user_id = :user_id'
                        );
                        $findAddress->execute([
                            'address_id' => $addressId,
                            'user_id' => $user['id'],
                        ]);
                        $ownedAddress = $findAddress->fetch();
                        if ($ownedAddress === false) {
                            throw new DomainException('Địa chỉ không tồn tại hoặc không thuộc tài khoản này.');
                        }

                        if ($action === 'default_address') {
                            $clearDefault = $pdo->prepare(
                                'UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id'
                            );
                            $clearDefault->execute(['user_id' => $user['id']]);
                            $setDefault = $pdo->prepare(
                                'UPDATE user_addresses SET is_default = 1 WHERE id = :address_id AND user_id = :user_id'
                            );
                            $setDefault->execute([
                                'address_id' => $addressId,
                                'user_id' => $user['id'],
                            ]);
                            $success = 'Đã đặt địa chỉ mặc định.';
                        } else {
                            $delete = $pdo->prepare(
                                'DELETE FROM user_addresses WHERE id = :address_id AND user_id = :user_id'
                            );
                            $delete->execute([
                                'address_id' => $addressId,
                                'user_id' => $user['id'],
                            ]);
                            if ((int) $ownedAddress['is_default'] === 1) {
                                $promote = $pdo->prepare(
                                    'UPDATE user_addresses SET is_default = 1
                                     WHERE user_id = :user_id ORDER BY id ASC LIMIT 1'
                                );
                                $promote->execute(['user_id' => $user['id']]);
                            }
                            $success = 'Đã xóa địa chỉ giao hàng.';
                        }

                        $pdo->commit();
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }
                }
            } else {
                http_response_code(400);
                $errors[] = 'Thao tác không hợp lệ.';
            }
        } catch (DomainException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Email này đã được tài khoản khác sử dụng.';
            } else {
                error_log($exception->getMessage());
                $errors[] = 'Không thể lưu thay đổi lúc này. Vui lòng thử lại sau.';
            }
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $errors[] = 'Không thể lưu thay đổi lúc này. Vui lòng thử lại sau.';
        }
    }
}

$statement = db()->prepare(
    'SELECT id, receiver_name, phone, province, district, ward, street_address, is_default
     FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC'
);
$statement->execute(['user_id' => $user['id']]);
$addresses = $statement->fetchAll();

render_header('Hồ sơ & sổ địa chỉ');
?>
<section class="card">
    <p class="eyebrow">TÀI KHOẢN CỦA TÔI</p>
    <h1>Hồ sơ cá nhân</h1>
    <?php if ($errors !== []): ?>
        <div class="alert" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success !== ''): ?><div class="success" role="status"><?= e($success) ?></div><?php endif; ?>

    <form method="post" action="<?= e(app_url('profile.php')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <label for="full_name">Họ và tên</label>
        <input id="full_name" name="full_name" type="text" maxlength="100" required value="<?= e($profileName) ?>">
        <label for="email">Email đăng nhập</label>
        <input id="email" name="email" type="email" maxlength="190" required value="<?= e($profileEmail) ?>">
        <button class="button" type="submit">Lưu thông tin</button>
    </form>
</section>

<section class="card section-card">
    <h2>Sổ địa chỉ giao hàng</h2>
    <?php if ($addresses === []): ?>
        <p class="muted">Bạn chưa lưu địa chỉ nào. Địa chỉ đầu tiên sẽ tự động được đặt làm mặc định.</p>
    <?php endif; ?>

    <?php foreach ($addresses as $address): ?>
        <article class="address-card">
            <div class="address-heading">
                <strong><?= e($address['receiver_name']) ?></strong>
                <?php if ((int) $address['is_default'] === 1): ?><span class="badge">Mặc định</span><?php endif; ?>
            </div>
            <p><?= e($address['phone']) ?> · <?= e($address['street_address']) ?>,
                <?= e($address['ward']) ?>, <?= e($address['district']) ?>, <?= e($address['province']) ?></p>
            <?php if ((int) $address['is_default'] !== 1): ?>
                <form method="post" action="<?= e(app_url('profile.php')) ?>" class="inline-actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="default_address">
                    <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                    <button class="text-button" type="submit">Đặt làm mặc định</button>
                </form>
            <?php endif; ?>
            <details>
                <summary>Chỉnh sửa</summary>
                <form method="post" action="<?= e(app_url('profile.php')) ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_address">
                    <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                    <label for="receiver-<?= (int) $address['id'] ?>">Người nhận</label>
                    <input id="receiver-<?= (int) $address['id'] ?>" name="receiver_name" maxlength="100" required value="<?= e($address['receiver_name']) ?>">
                    <label for="phone-<?= (int) $address['id'] ?>">Số điện thoại</label>
                    <input id="phone-<?= (int) $address['id'] ?>" name="phone" maxlength="20" required value="<?= e($address['phone']) ?>">
                    <label for="street-<?= (int) $address['id'] ?>">Địa chỉ cụ thể</label>
                    <input id="street-<?= (int) $address['id'] ?>" name="street_address" maxlength="255" required value="<?= e($address['street_address']) ?>">
                    <label for="ward-<?= (int) $address['id'] ?>">Phường / xã</label>
                    <input id="ward-<?= (int) $address['id'] ?>" name="ward" maxlength="100" required value="<?= e($address['ward']) ?>">
                    <label for="district-<?= (int) $address['id'] ?>">Quận / huyện</label>
                    <input id="district-<?= (int) $address['id'] ?>" name="district" maxlength="100" required value="<?= e($address['district']) ?>">
                    <label for="province-<?= (int) $address['id'] ?>">Tỉnh / thành phố</label>
                    <input id="province-<?= (int) $address['id'] ?>" name="province" maxlength="100" required value="<?= e($address['province']) ?>">
                    <label class="checkbox-label"><input type="checkbox" name="is_default" value="1" <?= (int) $address['is_default'] === 1 ? 'checked' : '' ?>> Đặt làm địa chỉ mặc định</label>
                    <button class="button" type="submit">Lưu địa chỉ</button>
                </form>
            </details>
            <form method="post" action="<?= e(app_url('profile.php')) ?>" class="inline-actions" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_address">
                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                <button class="text-button danger" type="submit">Xóa địa chỉ</button>
            </form>
        </article>
    <?php endforeach; ?>

    <details class="add-address">
        <summary>Thêm địa chỉ mới</summary>
        <form method="post" action="<?= e(app_url('profile.php')) ?>" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_address">
            <label for="new-receiver">Người nhận</label>
            <input id="new-receiver" name="receiver_name" maxlength="100" required>
            <label for="new-phone">Số điện thoại</label>
            <input id="new-phone" name="phone" maxlength="20" required>
            <label for="new-street">Địa chỉ cụ thể</label>
            <input id="new-street" name="street_address" maxlength="255" required>
            <label for="new-ward">Phường / xã</label>
            <input id="new-ward" name="ward" maxlength="100" required>
            <label for="new-district">Quận / huyện</label>
            <input id="new-district" name="district" maxlength="100" required>
            <label for="new-province">Tỉnh / thành phố</label>
            <input id="new-province" name="province" maxlength="100" required>
            <label class="checkbox-label"><input type="checkbox" name="is_default" value="1"> Đặt làm địa chỉ mặc định</label>
            <button class="button" type="submit">Thêm địa chỉ</button>
        </form>
    </details>
</section>
<?php render_footer(); ?>
