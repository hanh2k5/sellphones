<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ProfileService
{
    /**
     * [Nguyễn Duy Khang - 4.2.9] Cập nhật hồ sơ cá nhân (Optimistic Locking)
     */
    public function updateInfo($user, array $data, $clientUpdatedAt)
    {
        // Optimistic Locking Check
        // Chuyển về timestamp (giây) để bỏ qua sai số mili-giây
        $clientTime = Carbon::parse($clientUpdatedAt)->timestamp;
        $serverTime = $user->updated_at->timestamp;

        if ($clientTime < $serverTime) {
            abort(409, 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại dữ liệu.');
        }

        $user->update($data);
        return $user;
    }

    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword($user, $oldPassword, $newPassword)
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không chính xác.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        return $user;
    }

    /**
     * Kiểm tra cập nhật (Polling) cho Profile
     */
    public function checkUpdate($user, $lastTime)
    {
        return [
            'changed'    => $user->updated_at->gt($lastTime),
            'user'       => $user,
            'updated_at' => $user->updated_at->toIso8601String(),
            'message'    => 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại dữ liệu.'
        ];
    }
}
