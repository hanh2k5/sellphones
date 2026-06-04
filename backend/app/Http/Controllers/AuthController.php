<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminStoreUserRequest;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * [Nguyễn Duy Khang - 4.2.1 → 4.2.11] AuthController
 * Thin Controller: chỉ nhận Request → gọi AuthService → trả Response/Resource.
 * Toàn bộ business logic (OTP, hash, lock, query User/DB) đã nằm trong AuthService.
 */
class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    // ===================== [4.2.5] ĐĂNG KÝ =====================

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json([
                'data'    => ['user' => (new UserResource($result['user']))->resolve(), 'token' => $result['token']],
                'message' => 'Đăng ký thành công!'
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Lỗi kết nối cơ sở dữ liệu.'], 500);
        }
    }

    // ===================== [4.2.6 & 4.2.7] ĐĂNG NHẬP =====================

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->email, $request->password);
            return response()->json([
                'user'    => (new UserResource($result['user']))->resolve(),
                'token'   => $result['token'],
                'message' => 'Đăng nhập thành công!'
            ]);
        } catch (ValidationException $e) {
            // AuthService@getLoginErrorData cung cấp locked/attempts_left mà không cần query trong Controller
            $data = array_merge(
                ['message' => $e->getMessage(), 'errors' => $e->errors()],
                $this->authService->getLoginErrorData($request->email)
            );
            return response()->json($data, 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 401);
        }
    }

    // ===================== ĐĂNG XUẤT =====================

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    // ===================== THÔNG TIN HIỆN TẠI =====================

    public function me()
    {
        return (new UserResource(Auth::user()))->resolve();
    }

    // ===================== [4.2.3] DANH SÁCH USER (ADMIN) =====================

    public function index(Request $request)
    {
        $users = $this->authService->getAllUsers($request->all());
        return UserResource::collection($users);
    }

    // ===================== [4.2.1] THÊM USER (ADMIN) =====================

    public function storeUser(AdminStoreUserRequest $request)
    {
        $user = $this->authService->createUser($request->validated());
        return response()->json(['message' => 'Tạo người dùng thành công!', 'user' => $user], 201);
    }

    // ===================== [4.2.2] SỬA USER (ADMIN) =====================

    public function updateUser(AdminUpdateUserRequest $request, User $user)
    {
        try {
            // Optimistic Locking đã được xử lý trong AuthService@updateUser
            $updatedUser = $this->authService->updateUser($user, $request->validated(), $request->input('updated_at'));
            return response()->json(['message' => 'Cập nhật thông tin thành công', 'user' => $updatedUser]);
        } catch (Exception $e) {
            $code = $e->getCode() === 409 ? 409 : 500;
            $msg  = $e->getCode() === 409 ? $e->getMessage() : 'Lỗi kết nối cơ sở dữ liệu.';
            return response()->json(['message' => $msg], $code);
        }
    }

    // ===================== [4.2.4] KHÓA / MỞ KHÓA / XÓA USER (ADMIN) =====================

    public function lock(User $user)
    {
        try {
            $updatedUser = $this->authService->lockUser($user);
            return response()->json(['message' => 'Đã khóa tài khoản.', 'user' => $updatedUser]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function unlock(User $user)
    {
        $updatedUser = $this->authService->unlockUser($user);
        return response()->json(['message' => 'Đã mở khóa tài khoản.', 'user' => $updatedUser]);
    }

    /**
     * AuthService@deleteUser xử lý: User::find, self-delete check, admin check, logging.
     * Controller không còn chứa bất kỳ Model query hay business logic nào.
     */
    public function destroyUser(Request $request, $id)
    {
        try {
            $this->authService->deleteUser($id, Auth::id());
            return response()->json(['message' => 'Đã xóa tài khoản thành công.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    // ===================== [4.2.11] QUÊN / ĐẶT LẠI MẬT KHẨU =====================

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            // AuthService xử lý: User::find, OTP, DB::table, Mail::send
            $this->authService->forgotPassword($request->email);
            return response()->json(['message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            // AuthService xử lý: User::find, DB::table, Hash::check, user->update, DB::delete
            $this->authService->resetPassword($request->email, $request->otp, $request->password);
            return response()->json(['message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }
}
