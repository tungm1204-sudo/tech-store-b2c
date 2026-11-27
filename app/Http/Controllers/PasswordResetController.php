<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Quên mật khẩu & Đặt lại mật khẩu bằng token băm SHA-256 hết hạn sau 60 phút, dùng 1 lần.
 * (Phụ trách: Điền - chuyển đổi sang bảng password_resets chuẩn Laravel 9)
 */
class PasswordResetController extends Controller
{
    private const TOKEN_TTL_MINUTES = 60;

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email'    => 'Địa chỉ email không hợp lệ.',
        ]);

        $email = $data['email'];
        $user = User::where('email', $email)->where('is_active', true)->first();
        $demoResetUrl = null;

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            DB::transaction(function () use ($email, $tokenHash) {
                DB::table('password_resets')->where('email', $email)->delete();
                DB::table('password_resets')->insert([
                    'email'      => $email,
                    'token'      => $tokenHash,
                    'created_at' => now(),
                ]);
            });

            $resetUrl = route('password.reset', ['token' => $rawToken, 'email' => $email]);
            Log::info("[TechStore Password Reset] {$email} -> {$resetUrl}");

            try {
                Mail::raw(
                    "Bạn vừa yêu cầu đặt lại mật khẩu tại TechStore B2C.\n\n"
                    . "Mở liên kết sau trong vòng 60 phút để tạo mật khẩu mới:\n{$resetUrl}\n\n"
                    . "Nếu bạn không yêu cầu, hãy bỏ qua email này.",
                    function ($message) use ($email) {
                        $message->to($email)->subject('Đặt lại mật khẩu - TechStore B2C');
                    }
                );
            } catch (\Throwable $e) {
                Log::warning('Không gửi được email đặt lại mật khẩu (kiểm tra cấu hình SMTP): ' . $e->getMessage());
            }

            if (app()->environment('local')) {
                $demoResetUrl = $resetUrl;
            }
        }

        // Phản hồi chung chung để không tiết lộ email có tồn tại hay không (chống user enumeration)
        $response = back()->with(
            'success',
            'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu đã được gửi đến hộp thư đó (hiệu lực 60 phút).'
        );

        if ($demoResetUrl) {
            $response->with('demo_reset_url', $demoResetUrl);
        }

        return $response;
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = strtolower(trim((string) $request->query('email', '')));
        $record = $this->findValidTokenRecord($token, $email ?: null);

        return view('auth.reset-password', [
            'token'      => $token,
            'email'      => $record->email ?? $email,
            'tokenValid' => $record !== null,
        ]);
    }

    public function reset(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        $data = $request->validate([
            'token'    => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'email'    => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function ($attr, $value, $fail) {
                if (strlen((string) $value) > 72) {
                    $fail('Mật khẩu không được vượt quá 72 byte.');
                }
            }],
        ], [
            'token.regex'        => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            'password.min'       => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $tokenHash = hash('sha256', $data['token']);

        $updated = DB::transaction(function () use ($data, $tokenHash) {
            $record = DB::table('password_resets')
                ->where('email', $data['email'])
                ->lockForUpdate()
                ->first();

            if (! $record || ! hash_equals((string) $record->token, $tokenHash)) {
                return false;
            }

            if (! $record->created_at || Carbon::parse($record->created_at)->lt(now()->subMinutes(self::TOKEN_TTL_MINUTES))) {
                DB::table('password_resets')->where('email', $data['email'])->delete();
                return false;
            }

            $user = User::where('email', $data['email'])->lockForUpdate()->first();
            if (! $user) {
                return false;
            }

            $user->update(['password' => Hash::make($data['password'])]);
            DB::table('password_resets')->where('email', $data['email'])->delete();

            return true;
        });

        if (! $updated) {
            return back()->with('error', 'Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn hoặc đã được sử dụng.');
        }

        return redirect()->route('login')
            ->with('success', 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập bằng mật khẩu mới.');
    }

    private function findValidTokenRecord(string $rawToken, ?string $email = null): ?object
    {
        if (preg_match('/^[a-f0-9]{64}$/', $rawToken) !== 1) {
            return null;
        }

        $query = DB::table('password_resets')
            ->where('token', hash('sha256', $rawToken))
            ->where('created_at', '>=', now()->subMinutes(self::TOKEN_TTL_MINUTES));

        if ($email) {
            $query->where('email', $email);
        }

        return $query->first();
    }
}
