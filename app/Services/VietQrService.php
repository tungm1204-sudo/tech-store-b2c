<?php

namespace App\Services;

use App\Models\Order;

/**
 * Sinh Quick Link VietQR động theo số tiền và mã đơn hàng.
 * Việc xác nhận tiền về tài khoản cần webhook/API ngân hàng riêng.
 */
class VietQrService
{
    public function imageUrl(Order $order): ?string
    {
        $bankId = trim((string) config('services.vietqr.bank_id'));
        $accountNo = trim((string) config('services.vietqr.account_no'));
        $accountName = trim((string) config('services.vietqr.account_name'));

        if ($bankId === '' || $accountNo === '') {
            return null;
        }

        // Chỉ cho phép mã ngân hàng/tài khoản ở dạng an toàn để ghép URL.
        if (
            !preg_match('/^[A-Za-z0-9]{2,12}$/', $bankId) ||
            !preg_match('/^[0-9]{6,19}$/', $accountNo)
        ) {
            return null;
        }

        $memo = 'TECHSTORE ' . $order->order_code;
        $query = [
            'amount' => (int) round((float) $order->total_amount),
            'addInfo' => $memo,
        ];

        if ($accountName !== '') {
            $query['accountName'] = $accountName;
        }

        return 'https://img.vietqr.io/image/' . rawurlencode($bankId) . '-' .
            rawurlencode($accountNo) . '-compact2.svg?' . http_build_query($query);
    }

    public function isConfigured(): bool
    {
        return $this->imageUrl(new Order([
            'order_code' => 'CONFIG-CHECK',
            'total_amount' => 1,
        ])) !== null;
    }
}
