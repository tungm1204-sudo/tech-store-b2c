<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Hồ sơ cá nhân, Đổi mật khẩu & Sổ địa chỉ giao hàng (Phụ trách: Điền)
 * Giữ nguyên toàn bộ ràng buộc an toàn từ đóng góp của Điền (lockForUpdate, chống IDOR,
 * tự đặt mặc định địa chỉ đầu tiên, tự đôn địa chỉ mặc định khi xóa) và ánh xạ đúng CSDL 16 bảng.
 */
class ProfileController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $addresses = $user->addresses()->get();

        return view('profile.index', compact('user', 'addresses'));
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->merge([
            'name'  => trim((string) $request->input('name', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'   => ['nullable', 'string', 'max:25', 'regex:/^[0-9+().\-\s]{8,25}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Họ và tên không được để trống.',
            'email.unique'  => 'Email này đã được tài khoản khác sử dụng.',
            'phone.regex'   => 'Số điện thoại không hợp lệ.',
        ]);

        $user->update($data);

        return redirect()->route('profile.index')->with('success', 'Thông tin cá nhân đã được cập nhật.');
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'max:72', 'confirmed', function ($attr, $value, $fail) {
                if (strlen((string) $value) > 72) {
                    $fail('Mật khẩu mới không được vượt quá 72 byte.');
                }
            }],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.min'              => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'password.confirmed'        => 'Mật khẩu mới và phần xác nhận không khớp.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->update(['password' => Hash::make($data['password'])]);
            DB::table('password_resets')->where('email', $user->email)->delete();
        });

        $request->session()->regenerate();

        return redirect()->route('profile.index')->with('success', 'Mật khẩu đã được cập nhật thành công.');
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);
        $userId = (int) Auth::id();
        $isDefault = $request->boolean('is_default');

        DB::transaction(function () use ($userId, $data, $isDefault) {
            User::whereKey($userId)->lockForUpdate()->first();

            $hasExisting = UserAddress::where('user_id', $userId)->exists();
            $makeDefault = $isDefault || ! $hasExisting;

            if ($makeDefault) {
                UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            UserAddress::create($data + [
                'user_id'    => $userId,
                'is_default' => $makeDefault,
            ]);
        });

        return redirect()->route('profile.index')->with('success', 'Đã thêm địa chỉ giao hàng mới.');
    }

    public function updateAddress(Request $request, UserAddress $address)
    {
        abort_unless((int) $address->user_id === (int) Auth::id(), 404);

        $data = $this->validateAddress($request);
        $userId = (int) Auth::id();
        $isDefault = $request->boolean('is_default');

        DB::transaction(function () use ($userId, $address, $data, $isDefault) {
            User::whereKey($userId)->lockForUpdate()->first();

            if ($isDefault) {
                UserAddress::where('user_id', $userId)->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($data + ['is_default' => $isDefault]);

            // Nếu user bỏ chọn mặc định ở địa chỉ duy nhất đang mặc định, tự động giữ lại 1 địa chỉ mặc định
            if (! UserAddress::where('user_id', $userId)->where('is_default', true)->exists()) {
                UserAddress::where('user_id', $userId)->orderBy('id')->limit(1)->update(['is_default' => true]);
            }
        });

        return redirect()->route('profile.index')->with('success', 'Đã cập nhật địa chỉ giao hàng.');
    }

    public function setDefaultAddress(UserAddress $address)
    {
        abort_unless((int) $address->user_id === (int) Auth::id(), 404);

        $userId = (int) Auth::id();

        DB::transaction(function () use ($userId, $address) {
            User::whereKey($userId)->lockForUpdate()->first();
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('profile.index')->with('success', 'Đã đặt địa chỉ mặc định.');
    }

    public function destroyAddress(UserAddress $address)
    {
        abort_unless((int) $address->user_id === (int) Auth::id(), 404);

        $userId = (int) Auth::id();

        DB::transaction(function () use ($userId, $address) {
            User::whereKey($userId)->lockForUpdate()->first();
            $wasDefault = (bool) $address->is_default;
            $address->delete();

            if ($wasDefault) {
                UserAddress::where('user_id', $userId)->orderBy('id')->limit(1)->update(['is_default' => true]);
            }
        });

        return redirect()->route('profile.index')->with('success', 'Đã xóa địa chỉ giao hàng.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'recipient_name'  => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:25', 'regex:/^[0-9+().\-\s]{8,25}$/'],
            'province'        => ['required', 'string', 'max:100'],
            'district'        => ['required', 'string', 'max:100'],
            'ward'            => ['nullable', 'string', 'max:100'],
            'street_address'  => ['required', 'string', 'max:255'],
        ], [
            'recipient_name.required'  => 'Vui lòng nhập tên người nhận.',
            'recipient_phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'recipient_phone.regex'    => 'Số điện thoại nhận hàng không hợp lệ.',
            'province.required'        => 'Vui lòng nhập Tỉnh / Thành phố.',
            'district.required'        => 'Vui lòng nhập Quận / Huyện.',
            'street_address.required'  => 'Vui lòng nhập địa chỉ cụ thể.',
        ]);
    }
}
