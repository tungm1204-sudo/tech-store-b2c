<?php

/*
|--------------------------------------------------------------------------
| Cấu hình nghiệp vụ TechStore B2C (Phụ trách: Trung Anh - Đơn hàng / RMA / Kho)
|--------------------------------------------------------------------------
*/

return [

    /*
     * Quy ước tồn kho giữa module Giỏ hàng/Checkout (Phúc) và module Đơn hàng (Trung Anh):
     *   - Phúc TRỪ kho khi tạo đơn (CheckoutController) và gắn cờ stock_deducted_at.
     *   - OrderObserver + OrderInventoryService (Phúc) TỰ ĐỘNG HOÀN kho, trừ lại sold_count
     *     và hoàn lượt mã giảm giá ĐÚNG 1 LẦN khi đơn chuyển sang status = 'cancelled'.
     *   - Vì vậy tắt restock_on_cancel ở OrderStateService (false) để tránh hoàn kho gấp đôi!
     */
    'restock_on_cancel' => env('TECHSTORE_RESTOCK_ON_CANCEL', false),

    // Số ngày kể từ lúc giao hàng thành công mà khách còn được gửi yêu cầu ĐỔI TRẢ (request_type = return)
    'return_window_days' => (int) env('TECHSTORE_RETURN_WINDOW_DAYS', 30),

    // Dung lượng tối đa (KB) ảnh/video minh chứng lỗi khi gửi yêu cầu bảo hành
    'rma_evidence_max_kb' => (int) env('TECHSTORE_RMA_EVIDENCE_MAX_KB', 20480),
];
