# BÁO CÁO KIỂM THỬ VÀ XÁC THỰC LOGIC HỆ THỐNG SELLPHONES

## 1. XÁC THỰC LOGIC FILE BÁO CÁO (LƯỢC ĐỒ ERD) VỚI THỰC TẾ CODEBASE

Dựa vào hình ảnh Lược đồ ERD bạn cung cấp và đối chiếu trực tiếp với mã nguồn Laravel (thư mục `database/migrations` và thư mục `app/Models`), tôi xác nhận **Logic code hiện tại hoàn toàn khớp 100% với file báo cáo của bạn**.

**Chi tiết đối chiếu logic:**
- **categories & products**: Quan hệ 1-N. Bảng `products` chứa `category_id` là khóa ngoại. Bảng `categories` có `parent_id` (đệ quy) để làm danh mục con - *Khớp*.
- **users & orders**: Quan hệ 1-N. Bảng `orders` chứa `user_id` - *Khớp*.
- **orders & order_items**: Quan hệ 1-N. Mỗi hóa đơn có nhiều chi tiết. *Khớp*.
- **products & product_images**: Quan hệ 1-N. Bảng hình ảnh chứa `product_id` - *Khớp*.
- **users & cart_items**: Giỏ hàng liên kết `user_id` và `product_id` - *Khớp*.
- **vouchers & orders**: Quan hệ 1-N. Đơn hàng có thể áp dụng 1 `voucher_id` - *Khớp*.
- **users & reviews**: Người dùng đánh giá sản phẩm - *Khớp*.
- **ai_chats**: Lưu lịch sử chat AI liên kết với `user_id` - *Khớp*.

Tất cả các trường dữ liệu (data types) như `varchar`, `bigint`, `decimal(12,2)`, `timestamp` đều được định nghĩa chuẩn xác trong file Migrations của Laravel theo đúng như bản vẽ ERD.

---

## 2. DANH SÁCH 40 TEST CASE CHO MỖI TÍNH NĂNG TRỌNG TÂM

Dưới đây là thiết kế Test Case (chia làm 2 nhóm: Người dùng bình thường và Hacker phá hoại) cho các tính năng trọng tâm tính đến thời điểm hiện tại.

### TÍNH NĂNG 1: XÁC THỰC & HỒ SƠ NGƯỜI DÙNG (AUTH & PROFILE)

**A. 20 Test Case Người dùng sử dụng (Normal Flow & Edge Cases)**
1. Đăng ký tài khoản thành công với dữ liệu hợp lệ.
2. Đăng ký thất bại khi email đã tồn tại trong hệ thống.
3. Đăng ký thất bại khi mật khẩu xác nhận không khớp.
4. Đăng ký thất bại khi mật khẩu ngắn hơn 8 ký tự.
5. Đăng ký thất bại khi bỏ trống các trường bắt buộc.
6. Đăng nhập thành công với email và mật khẩu đúng.
7. Đăng nhập thất bại do sai mật khẩu.
8. Đăng nhập thất bại do email không tồn tại.
9. Đăng nhập thành công sau khi nhập sai 4 lần (dưới ngưỡng khóa).
10. Tài khoản bị khóa (Lockout) tạm thời sau 5 lần nhập sai mật khẩu liên tiếp.
11. Đăng xuất thành công, token bị xóa khỏi trình duyệt.
12. Xem thông tin hồ sơ cá nhân hiển thị chính xác dữ liệu.
13. Cập nhật tên hiển thị thành công.
14. Cập nhật số điện thoại thành công (chỉ cho phép nhập số).
15. Cập nhật địa chỉ nhận hàng thành công.
16. Đổi mật khẩu thành công với mật khẩu cũ chính xác.
17. Đổi mật khẩu thất bại do nhập sai mật khẩu cũ.
18. Nhận cảnh báo "Xung đột dữ liệu (409 Conflict)" nếu cập nhật hồ sơ khi dữ liệu đã bị đổi ở tab khác.
19. Nút "Tải lại dữ liệu" hoạt động đúng khi xảy ra xung đột.
20. Các trường nhập liệu (Input) phản hồi lỗi validation trực tiếp dưới mỗi ô nhập.

**B. 20 Test Case Hacker phá hoại (Security & Penetration)**
1. SQL Injection vào trường Email lúc đăng nhập (`admin@test.com' OR '1'='1`).
2. SQL Injection vào trường Password.
3. Cross-Site Scripting (XSS) Stored: Nhập script `<script>alert(1)</script>` vào trường Tên hiển thị lúc đăng ký.
4. XSS Stored: Nhập mã độc vào trường Địa chỉ giao hàng.
5. Brute-force mật khẩu bằng cách gửi hàng nghìn request qua API `/api/login` (kỳ vọng: Bị Rate Limiter chặn).
6. Sử dụng công cụ tự động đăng ký hàng nghìn tài khoản rác để làm đầy Database (kỳ vọng: Hệ thống có cơ chế chặn spam hoặc Rate Limit).
7. Bypass JWT Token: Thay đổi payload của token trong LocalStorage để mạo danh `role: admin`.
8. Sử dụng token đã hết hạn (Expired Token) để gọi API `/api/profile`.
9. Cố tình gửi request `PUT /api/profile` mà không có header Authorization.
10. Sửa đổi tham số `user_id` trong API cập nhật profile để cố gắng đổi thông tin của người dùng khác (IDOR - Insecure Direct Object Reference).
11. Gửi chuỗi dữ liệu cực lớn (hàng triệu ký tự) vào trường `address` để gây lỗi tràn bộ nhớ (Buffer Overflow/DDoS).
12. Chèn thẻ HTML/CSS độc hại vào trường `name` để phá vỡ giao diện navbar (Self-XSS).
13. Tấn công CSRF: Ép người dùng khác đổi mật khẩu thông qua một trang web giả mạo (Kỳ vọng: Thất bại do cơ chế bảo vệ Sanctum CORS/CSRF).
14. Thử thay đổi trường `role` thành `admin` thông qua API `PUT /api/profile` (Mass Assignment Vulnerability).
15. Gỡ bỏ thuộc tính `maxlength` trên Frontend bằng DevTools và gửi chuỗi siêu dài lên Server.
16. Thử vượt qua lỗi 409 Conflict bằng cách chỉnh sửa tham số `updated_at` trong payload API gửi lên thành một timestamp tương lai.
17. Fuzzing API `/api/check-update` bằng các ký tự đặc biệt trong tham số `last_time`.
18. Xóa LocalStorage nhưng giữ lại Cookie để xem hệ thống xử lý phiên đăng nhập thế nào.
19. Gửi nhiều request đổi mật khẩu cùng một phần nghìn giây (Race Condition).
20. Đánh cắp Token và thử sử dụng ở một địa chỉ IP khác.

---

### TÍNH NĂNG 2: GIỎ HÀNG VÀ THANH TOÁN (CART & CHECKOUT)

**A. 20 Test Case Người dùng sử dụng (Normal Flow)**
1. Thêm 1 sản phẩm vào giỏ hàng thành công từ trang chi tiết.
2. Tăng số lượng sản phẩm trong giỏ hàng và tổng tiền cập nhật đúng.
3. Giảm số lượng sản phẩm (lớn hơn 1).
4. Giảm số lượng sản phẩm về 0 (Sản phẩm bị xóa khỏi giỏ).
5. Xóa trực tiếp sản phẩm khỏi giỏ hàng bằng nút Thùng rác.
6. Thêm sản phẩm vượt quá số lượng Stock (Tồn kho) cho phép (Kỳ vọng: Báo lỗi hết hàng).
7. Thêm sản phẩm vào giỏ khi chưa đăng nhập (Giỏ hàng lưu ở LocalStorage).
8. Đăng nhập sau khi có giỏ hàng Local (Giỏ hàng được đồng bộ lên Server).
9. Mở trang Thanh toán (Checkout) tải đúng thông tin địa chỉ từ Profile.
10. Sửa địa chỉ giao hàng trực tiếp trên trang Checkout.
11. Nhập mã Voucher hợp lệ (Tổng tiền giảm đúng phần trăm/số tiền).
12. Nhập mã Voucher đã hết hạn (Báo lỗi).
13. Nhập mã Voucher đã hết lượt sử dụng (Báo lỗi).
14. Hủy bỏ Voucher đang áp dụng.
15. Chọn phương thức thanh toán COD và đặt hàng thành công.
16. Đặt hàng thành công chuyển hướng đến trang Checkout Success với pháo hoa.
17. Chọn phương thức thanh toán MoMo và tiến hành quét mã.
18. Kiểm tra Email xác nhận đơn hàng sau khi đặt thành công.
19. Đơn hàng mới hiển thị trạng thái "Chờ duyệt" trong Profile -> My Orders.
20. Không cho phép Checkout khi giỏ hàng trống.

**B. 20 Test Case Hacker phá hoại (Security & Penetration)**
1. Đổi giá sản phẩm trên Frontend (DevTools) và gửi API Checkout (Kỳ vọng: Backend tính lại giá từ DB, không tin tưởng Frontend).
2. Đổi số lượng sản phẩm thành số âm (`-5`) qua API để làm giảm tổng tiền hóa đơn.
3. Đổi số lượng sản phẩm thành kiểu chữ (String `abc`) hoặc mảng (Array).
4. Sửa ID của sản phẩm trong giỏ hàng thành ID của một sản phẩm không tồn tại (`999999`).
5. Sửa tham số `discount_amount` gửi lên API Checkout để tự giảm giá 100% hóa đơn.
6. Gửi API áp dụng mã Voucher liên tục cùng 1 lúc (Race Condition) để được giảm giá nhiều lần trên 1 đơn hàng.
7. XSS Stored: Chèn mã độc `<img src=x onerror=alert(1)>` vào Ghi chú đơn hàng (nếu có) hoặc Địa chỉ giao hàng.
8. Thêm hàng triệu sản phẩm vào giỏ hàng để kiểm tra tràn kiểu dữ liệu `DECIMAL` của cột Tổng tiền.
9. Bypass kiểm tra Tồn kho (Stock): Gửi request mua 100 cái trong khi kho chỉ còn 2 cái thông qua API Postman.
10. IDOR: Sửa `cart_item_id` của người khác để xóa đồ trong giỏ hàng của họ.
11. IDOR: Xem chi tiết đơn hàng (Order) của User khác bằng cách thay đổi `order_id` trên thanh URL.
12. Bỏ qua bước Thanh toán MoMo bằng cách gọi thẳng API xác nhận thanh toán thành công của MoMo webhook.
13. Sửa `payment_status` thành `paid` thông qua lỗ hổng Mass Assignment khi gọi API Checkout.
14. Gửi payload Voucher dạng mảng `voucher_code[]` thay vì string để gây lỗi Backend.
15. Thử ép kiểu ID Voucher thành chuỗi dài để gây lỗi SQL Exception.
16. Đặt hàng liên tục 100 đơn/giây bằng script tự động để gây nghẽn hệ thống (DoS).
17. Đánh cắp CSRF token và thử đặt hàng hộ người dùng khác (Lừa người dùng click link lạ).
18. Thay đổi LocalStorage của trình duyệt, chèn mã thực thi trực tiếp vào key `cart_items` để tấn công XSS nội bộ.
19. Sử dụng công cụ chặn bắt gói tin (Burp Suite) để thả (drop) request cập nhật số lượng, làm lệch giỏ hàng.
20. Thay đổi tham số `order_id` trong hàm hủy đơn hàng để hủy đơn của người khác.

---

### TÍNH NĂNG 3: QUẢN TRỊ ADMIN (ADMIN PANEL)

**A. 20 Test Case Người dùng (Admin) sử dụng**
1. Truy cập trang Dashboard xem thống kê doanh thu, số đơn, số người dùng.
2. Biểu đồ doanh thu hiển thị đúng số liệu theo tháng/năm.
3. Chuyển sang Dark Mode trong Admin Panel hoạt động bình thường.
4. Xem danh sách Sản phẩm, phân trang hiển thị đúng.
5. Cột Hình ảnh và Tên sản phẩm trong bảng hiển thị cân đối (Sticky Header hoạt động tốt khi cuộn).
6. Tìm kiếm sản phẩm theo tên hoạt động chính xác.
7. Lọc sản phẩm theo danh mục.
8. Thêm sản phẩm mới thành công (Upload ảnh lên Cloudinary).
9. Chỉnh sửa thông tin sản phẩm (giá, tên, tồn kho).
10. Xóa sản phẩm (Chuyển vào Thùng rác - Soft Delete).
11. Vào Thùng rác khôi phục lại sản phẩm đã xóa.
12. Xem danh sách Đơn hàng, sắp xếp theo thời gian mới nhất.
13. Cập nhật trạng thái đơn hàng từ "Chờ duyệt" sang "Đang giao".
14. Hủy đơn hàng và ghi rõ lý do.
15. Xem chi tiết người dùng và lịch sử mua hàng của họ.
16. Tạo mã Voucher mới (Quy định hạn mức, thời gian).
17. Khóa tài khoản của một người dùng vi phạm.
18. Quản lý danh mục: Thêm danh mục cha, danh mục con.
19. Đăng xuất khỏi Admin Panel an toàn.
20. Responsive: Menu Admin Sidebar tự động thu gọn trên màn hình điện thoại.

**B. 20 Test Case Hacker phá hoại (Security & Penetration)**
1. Truy cập URL `/admin/dashboard` bằng tài khoản User thường (Kỳ vọng: Bị chặn, trả về 403 Forbidden).
2. Gọi thẳng API `/api/admin/products` bằng token của User thường.
3. IDOR: Sửa đổi giá trị ID sản phẩm trong API Xóa sản phẩm để xóa hàng loạt sản phẩm ngẫu nhiên.
4. Upload file PHP/Shell độc hại thay vì file hình ảnh (JPEG/PNG) trong form Thêm sản phẩm.
5. Upload file ảnh có dung lượng khổng lồ (200MB) để làm cạn kiệt băng thông và bộ nhớ Server.
6. XSS Stored: Chèn mã độc JavaScript vào trường "Tên sản phẩm" hoặc "Mô tả sản phẩm" bằng API. Mục tiêu là khi Admin mở ra xem sẽ bị lấy cắp phiên đăng nhập.
7. XSS Stored qua tên Danh mục (Categories).
8. SQL Injection qua thanh tìm kiếm trong bảng Admin (e.g., `search='; DROP TABLE users; --`).
9. Thay đổi tham số `limit` trong API phân trang thành `1000000` để làm treo Server (ReDoS/Memory Limit).
10. Bypass kiểm tra quyền Admin: Thay đổi trường `role` trong Vuex/Pinia State hoặc LocalStorage thành `admin` để lừa giao diện Frontend mở khóa các nút Admin. (Kỳ vọng: Giao diện mở nhưng gọi API vẫn bị chặn).
11. Tấn công CSRF vào API Xóa người dùng (Lừa Admin click vào link độc).
12. Tấn công Directory Traversal thông qua tham số `image_path` để đọc các file nhạy cảm trên Server (như `.env`).
13. Thay đổi ID của Admin thành ID của chính Hacker thông qua lỗi Mass Assignment khi Admin cập nhật thông tin nội bộ.
14. Tự cấp quyền Admin cho một tài khoản khác bằng cách tiêm thêm trường `role: admin` vào body JSON của một API update nào đó.
15. Thực hiện liên tục request "Tạo Voucher" để làm đầy Database rác.
16. Thay đổi mã Voucher thành các mã độc hại chứa ký tự điều khiển (Control Characters).
17. Dùng Burp Suite để sửa Response từ Server, ép trả về HTTP 200 OK dù đã bị lỗi 403, để dò tìm lộ lọt dữ liệu tĩnh.
18. Xóa Admin Sidebar thông qua F12 (DevTools) và thử tìm các endpoint ẩn trong source code JS.
19. Phân tích JWT Token của Admin, thử brute-force khóa Secret Key của Server để tự tạo Token Admin giả.
20. Thử gửi payload XML độc hại (XXE Injection) vào API nếu Server có lỡ bóc tách dữ liệu XML.
