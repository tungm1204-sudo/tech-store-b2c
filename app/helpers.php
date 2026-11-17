<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('ts_route')) {
    /**
     * Sinh URL "an toàn" cho route có thể CHƯA tồn tại.
     *
     * Vì 5 thành viên code song song trên 5 nhánh khác nhau, layout dùng chung
     * có thể tham chiếu tới route của người khác mà nhánh hiện tại chưa có.
     * route() sẽ báo lỗi, còn ts_route() trả về '#' -> trang không bị vỡ.
     *
     * Có thể truyền nhiều tên route, hàm sẽ dùng route đầu tiên tồn tại:
     *   ts_route(['logout', 'dev.logout'])
     */
    function ts_route(string|array $names, mixed $parameters = [], bool $absolute = true): string
    {
        foreach ((array) $names as $name) {
            if (Route::has($name)) {
                return route($name, $parameters, $absolute);
            }
        }

        return '#';
    }
}
