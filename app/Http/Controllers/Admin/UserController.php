<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Quản trị Tài khoản & Phân quyền RBAC (Phụ trách: Điền - chỉ Admin).
 */
class UserController extends Controller
{
    public const ROLE_LABELS = [
        'customer' => 'Khách hàng',
        'staff'    => 'Nhân viên',
        'admin'    => 'Quản trị viên',
    ];

    public function index(Request $request)
    {
        $query = User::query()->withCount(['orders', 'reviews']);

        if ($request->filled('role') && array_key_exists($request->role, self::ROLE_LABELS)) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'locked') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('q')) {
            $kw = trim((string) $request->q);
            $query->where(function ($sub) use ($kw) {
                $sub->where('name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%")
                    ->orWhere('phone', 'like', "%{$kw}%");
            });
        }

        $users = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $summary = [
            'total'    => User::count(),
            'admin'    => User::where('role', 'admin')->count(),
            'staff'    => User::where('role', 'staff')->count(),
            'customer' => User::where('role', 'customer')->count(),
            'locked'   => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', [
            'users'      => $users,
            'summary'    => $summary,
            'roleLabels' => self::ROLE_LABELS,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(self::ROLE_LABELS))],
        ]);

        if ((int) $user->id === (int) Auth::id() && $data['role'] !== 'admin') {
            return back()->with('error', 'Không thể tự hạ quyền Quản trị viên của chính bạn.');
        }

        $user->update(['role' => $data['role']]);

        return back()->with('success', "Đã cập nhật vai trò của {$user->name} thành \"" . self::ROLE_LABELS[$data['role']] . '".');
    }

    public function toggleStatus(User $user)
    {
        if ((int) $user->id === (int) Auth::id()) {
            return back()->with('error', 'Không thể tự khóa tài khoản đang đăng nhập của chính bạn.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $state = $user->is_active ? 'Đã mở khóa' : 'Đã tạm khóa';

        return back()->with('success', "{$state} tài khoản {$user->email}.");
    }
}
