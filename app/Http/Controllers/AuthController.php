<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Xác thực tài khoản: Đăng ký, Đăng nhập, Đăng xuất (Phụ trách: Điền)
 * Chuyển đổi sang Laravel MVC từ đóng góp của Điền trên nhánh feature/dien-auth-rbac.
 */
class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Địa chỉ email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $email = strtolower(trim($credentials['email']));

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không chính xác.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ quản trị viên.');
        }

        // Chống tấn công Session Fixation (giữ guest_cart_id để CartService gộp giỏ hàng)
        $request->session()->regenerate();

        $defaultTarget = $user->isStaffOrAdmin()
            ? ts_route(['admin.dashboard', 'home'])
            : route('home');

        return redirect()->intended($defaultTarget)
            ->with('success', "Xin chào, {$user->name}! Đăng nhập thành công.");
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'name'  => trim((string) $request->input('name', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:25', 'regex:/^[0-9+().\-\s]{8,25}$/'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function ($attr, $value, $fail) {
                if (strlen((string) $value) > 72) {
                    $fail('Mật khẩu không được vượt quá 72 byte (giới hạn của Bcrypt).');
                }
            }],
        ], [
            'name.required'      => 'Vui lòng nhập họ và tên.',
            'email.unique'       => 'Email này đã được sử dụng.',
            'phone.regex'        => 'Số điện thoại không hợp lệ.',
            'password.min'       => 'Mật khẩu phải có tối thiểu 8 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        // Tài khoản đăng ký tự do luôn có vai trò customer (chống leo thang đặc quyền)
        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'role'      => 'customer',
            'is_active' => true,
        ]);

        return redirect()->route('login')
            ->with('success', 'Đăng ký tài khoản thành công. Bạn có thể đăng nhập ngay bây giờ.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất khỏi hệ thống.');
    }
}
