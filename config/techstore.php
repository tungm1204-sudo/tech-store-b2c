<?php

/*
|--------------------------------------------------------------------------
| Cấu hình nghiệp vụ TechStore B2C (Phụ trách: Trung Anh - Đơn hàng / RMA / Kho)
|--------------------------------------------------------------------------
*/

return [

    /*
     * Quy ước tồn kho giữa module Giỏ hàng/Checkout (Phúc) và module Đơn hàng (Trung Anh):
     *   - Phúc TRỪ kho khi tạo đơn (lockForUpdate trong CheckoutController).
     *   - Trung Anh HOÀN kho khi đơn bị hủy (OrderStateService::transition -> cancelled).
     * Nếu nhóm quyết định trừ kho ở bước khác (vd: lúc duyệt đơn), đổi giá trị này thành false
     * và chuyển logic trừ/hoàn kho tương ứng.
     */
    'restock_on_cancel' => env('TECHSTORE_RESTOCK_ON_CANCEL', true),

    // Số ngày kể từ lúc giao hàng thành công mà khách còn được gửi yêu cầu ĐỔI TRẢ (request_type = return)
    'return_window_days' => (int) env('TECHSTORE_RETURN_WINDOW_DAYS', 30),

    // Dung lượng tối đa (KB) ảnh/video minh chứng lỗi khi gửi yêu cầu bảo hành
    'rma_evidence_max_kb' => (int) env('TECHSTORE_RMA_EVIDENCE_MAX_KB', 20480),
];
