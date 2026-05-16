<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1d1d1f; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #d2d2d7; border-radius: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #0071e3; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .footer { margin-top: 30px; font-size: 12px; color: #86868b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Khôi phục mật khẩu</h2>
        </div>
        <p>Chào <strong>{{ $user->name }}</strong>,</p>
        <p>Chúng tôi đã nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn tại <strong>SELLPHONES</strong>.</p>
        <p>Vui lòng nhấn vào nút bên dưới để tiến hành đặt lại mật khẩu. Liên kết này sẽ hết hạn sau 60 phút.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url') }}/reset-password?token={{ $token }}&email={{ urlencode($user->email) }}" class="btn">Đặt lại mật khẩu</a>
        </div>
        <p>Nếu bạn không yêu cầu hành động này, vui lòng bỏ qua email này.</p>
        <div class="footer">
            <p>&copy; {{ date('Y') }} SELLPHONES. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
