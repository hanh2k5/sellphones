<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use App\Http\Resources\UserResource;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Request;

/**
 * [Nguyễn Duy Khang - 4.2.9] ProfileController
 * LUỒNG HỒ SƠ CÁ NHÂN:
 *   Vào trang Profile → show() → GET /profile → trả thông tin user hiện tại
 *   User sửa thông tin → update() → Optimistic Locking (so sánh updated_at) → UPDATE users
 *   User đổi mật khẩu → updatePassword() → kiểm tra mật khẩu cũ → Hash::make → UPDATE
 *   Kiểm tra cập nhật (polling) → checkUpdate() → so sánh updated_at với last_time
 */
class ProfileController extends Controller
{
    protected $profileService; // ProfileService xử lý Optimistic Locking & hash mật khẩu

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /** GET /profile → trả thông tin user đang đăng nhập (name, email, phone, address, role) */
    public function show(Request $request)
    {
        return (new UserResource($request->user()))->resolve();
    }

    /**
     * PUT /profile → cập nhật thông tin cá nhân (có Optimistic Locking).
     * $request->updated_at: timestamp client giữ → so sánh với DB → 409 nếu xung đột 2-tab.
     * Trả về: user đã cập nhật | 409 nếu xung đột | 422 nếu email trùng.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // ProfileService@updateInfo so sánh updated_at client với DB → ném Exception 409 nếu lệch
        $updatedUser = $this->profileService->updateInfo(
            $user,
            $request->only(['name', 'email', 'phone', 'address']),
            $request->updated_at // Timestamp Optimistic Locking
        );
        return response()->json([
            'user'    => (new UserResource($updatedUser))->resolve(),
            'message' => __('messages.profile_updated')
        ]);
    }

    /**
     * PUT /profile/password → đổi mật khẩu.
     * ProfileService: Hash::check(current_password, stored_hash) → nếu đúng → Hash::make(new) → UPDATE.
     * Throws: 422 nếu mật khẩu cũ sai.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            $this->profileService->updatePassword(
                $request->user(),
                $request->current_password, // Mật khẩu cũ để xác minh
                $request->new_password      // Mật khẩu mới sẽ được hash trước khi lưu
            );
            return response()->json(['message' => __('messages.password_changed')]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /profile/check-update?last_time=... → kiểm tra hồ sơ có cập nhật mới không (Real-time Polling).
     * Frontend gọi định kỳ để phát hiện nếu Admin sửa thông tin user từ tab khác.
     * Trả về: {updated: bool, updated_at: timestamp}
     */
    public function checkUpdate(Request $request)
    {
        $lastTime = $request->query('last_time', now()->subMinute()); // Mặc định: 1 phút trước
        return response()->json($this->profileService->checkUpdate($request->user(), $lastTime));
    }
}
