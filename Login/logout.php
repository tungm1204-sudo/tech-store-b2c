<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('405 - Phương thức không được hỗ trợ.');
}

if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('400 - Phiên gửi biểu mẫu không hợp lệ.');
}

logout_user();
header('Location: ' . app_url('index.php'));
exit;
