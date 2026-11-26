<?php

/*
|--------------------------------------------------------------------------
| Cấu hình nghiệp vụ TechStore B2C (Phụ trách: Trung Anh - Đơn hàng / RMA / Kho)
|--------------------------------------------------------------------------
*/

return [

    /*
     * Quy ước tồn kho giữa Checkout (Phúc) và Đơn hàng (Trung Anh):
     *   - Phúc TRỪ kho khi tạo đơn và đánh dấu orders.stock_deducted_at.
     *   - Khi đơn chuyển sang "cancelled", OrderObserver của Phúc HOÀN kho đúng một lần.
     *   - Module Đơn hàng không tự hoàn kho để tránh cộng kho hai lần.
     */

    // Số ngày kể từ lúc giao hàng thành công mà khách còn được gửi yêu cầu ĐỔI TRẢ (request_type = return)
    'return_window_days' => (int) env('TECHSTORE_RETURN_WINDOW_DAYS', 30),

    // Dung lượng tối đa (KB) ảnh/video minh chứng lỗi khi gửi yêu cầu bảo hành
    'rma_evidence_max_kb' => (int) env('TECHSTORE_RMA_EVIDENCE_MAX_KB', 20480),
];
