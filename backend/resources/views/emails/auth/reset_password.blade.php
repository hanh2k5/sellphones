<x-mail::message>
# Đặt lại mật khẩu

Chào {{ $user->name }},

Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.

<x-mail::button :url="'http://localhost:5174/reset-password?token=' . $token . '&email=' . urlencode($user->email)">
Đặt lại mật khẩu
</x-mail::button>

Link đặt lại mật khẩu này sẽ hết hạn sau 60 phút.

Nếu bạn không yêu cầu đặt lại mật khẩu, bạn không cần thực hiện thêm hành động nào.

Trân trọng,<br>
Đội ngũ {{ config('app.name') }}
</x-mail::message>
