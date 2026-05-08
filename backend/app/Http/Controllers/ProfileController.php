<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;
use Exception;

use App\Http\Resources\UserResource;

/**
 * SV THỰC HIỆN: NGUYỄN DUY KHANG
 * MỤC: 4.2.9 (CẬP NHẬT HỒ SƠ & OPTIMISTIC LOCKING)
 */
class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show(Request $request)
    {
        return (new UserResource($request->user()))->resolve();
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
        ]);

        $updatedUser = $this->profileService->updateInfo($user, $request->only(['name', 'email', 'phone', 'address']));
        return response()->json([
            'user'    => (new UserResource($updatedUser))->resolve(),
            'message' => 'Cập nhật hồ sơ thành công!'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->profileService->updatePassword(
                $request->user(),
                $request->current_password,
                $request->new_password
            );
            return response()->json(['message' => 'Đổi mật khẩu thành công!']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function checkUpdate(Request $request)
    {
        $lastTime = $request->query('last_time', now()->subMinute());
        return response()->json($this->profileService->checkUpdate($request->user(), $lastTime));
    }
}
