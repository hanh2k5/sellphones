# Sellphones - Hệ thống Bán Điện Thoại Trực Tuyến

Dự án bán điện thoại trực tuyến bao gồm hệ thống Backend API xây dựng trên **Laravel 11** và giao diện Frontend xây dựng trên **Vue.js 3**.

---

## 🛠 Yêu cầu hệ thống (Prerequisites)

Để chạy được dự án này, máy tính của bạn cần cài đặt:
- **Docker & Docker Compose** (Dành cho Backend chạy qua Laravel Sail)
- **Node.js & npm** (Dành cho Frontend)
- **Git**

---

## 🚀 Hướng dẫn Cài đặt & Chạy dự án (Getting Started)

Dự án được chia làm 2 phần độc lập: **Backend** và **Frontend**. Bạn cần khởi chạy cả 2 để hệ thống hoạt động hoàn chỉnh.

### 1. Khởi chạy Backend (Laravel + Docker)

Backend sử dụng [Laravel Sail](https://laravel.com/docs/sail) để đóng gói toàn bộ môi trường (PHP, MySQL, Redis...) vào Docker, giúp bạn không cần cài đặt PHP hay MySQL trực tiếp trên máy.

1. Di chuyển vào thư mục `backend`:
   ```bash
   cd backend
   ```

2. Cài đặt các thư viện PHP thông qua Composer:
   ```bash
   composer install
   ```

3. Tạo file cấu hình môi trường:
   ```bash
   cp .env.example .env
   ```

4. Khởi chạy các container Docker bằng Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```

5. Sinh khóa bảo mật (App Key) cho Laravel:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. Chạy Migration để tạo các bảng trong cơ sở dữ liệu và seed dữ liệu mẫu:
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```

> **Lưu ý:** Nếu bạn dùng hệ điều hành Linux/macOS, bạn có thể thiết lập alias cho Sail để gõ lệnh nhanh hơn: `alias sail='bash vendor/bin/sail'`. Sau đó bạn chỉ cần gõ `sail artisan ...`

### 2. Khởi chạy Frontend (Vue.js)

Giao diện người dùng được phát triển bằng Vue.js.

1. Mở một terminal mới (giữ terminal Backend vẫn đang chạy ngầm) và di chuyển vào thư mục `frontend`:
   ```bash
   cd frontend
   ```

2. Cài đặt các thư viện Node.js:
   ```bash
   npm install
   ```

3. Khởi chạy server phát triển (Development Server):
   ```bash
   npm run dev
   ```

4. Mở trình duyệt và truy cập vào đường dẫn được hiển thị trên terminal (thường là `http://localhost:5173`).

---

## 🔑 Tài khoản thử nghiệm (Demo Accounts)

Sau khi chạy lệnh seed cơ sở dữ liệu (`migrate --seed`), hệ thống sẽ tự động tạo sẵn các tài khoản sau để bạn đăng nhập thử nghiệm:

### 1. Tài khoản Quản trị (Admin)
- **Email:** `admin@gmail.com`
- **Mật khẩu:** `11111111`
- **Quyền hạn:** Truy cập trang Dashboard Admin (`/admin`), quản lý sản phẩm, danh mục, đơn hàng, kho hàng...

### 2. Tài khoản Khách hàng (User)
- **Email:** `hanh2005k@gmail.com`
- **Mật khẩu:** `11111111`
- **Quyền hạn:** Mua sắm, thêm vào giỏ hàng, đặt hàng, xem lịch sử mua hàng...

### 3. Tài khoản Đánh giá Demo (Review User)
- **Email:** `ha@gmail.com`
- **Mật khẩu:** `11111111`
- **Quyền hạn:** Tài khoản phụ được tạo sẵn dùng để thử nghiệm tính năng đánh giá & phản hồi sản phẩm.

---

## 🧪 Chạy Kiểm thử (Testing)

Dự án đã được tích hợp bộ test tự động (Automated Tests) sử dụng PHPUnit, kiểm tra khắt khe các tính năng và lỗ hổng bảo mật.

Để chạy bộ kiểm thử, bạn mở terminal ở thư mục `backend` và chạy lệnh sau:
```bash
./vendor/bin/sail artisan test
```

## 📚 Tài liệu tham khảo thêm (Documentation)

Bạn có thể tìm thấy các tài liệu phân tích thiết kế chi tiết và báo cáo ở thư mục `docs/`:
- [`docs/test_cases_and_verification.md`](docs/test_cases_and_verification.md): Tài liệu đối chiếu lược đồ ERD và báo cáo.
- [`docs/walkthrough.md`](docs/walkthrough.md): Báo cáo chi tiết kết quả chạy kiểm thử tự động (Test Automation).