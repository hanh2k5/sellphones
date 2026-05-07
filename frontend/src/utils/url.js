<<<<<<< HEAD
export const getApiBaseUrl = () => import.meta.env.VITE_API_BASE_URL || 'http://localhost';
=======
/**
 * Tiện ích xử lý URL linh hoạt cho mọi môi trường (Dev/Prod)
 */
export const getApiBaseUrl = () => {
    // 1. Ưu tiên biến môi trường (cho Production)
    if (import.meta.env.VITE_API_URL) {
        return import.meta.env.VITE_API_URL.replace(/\/api$/, '');
    }

    // 2. Trong môi trường Dev: 
    // Dựa trên cấu hình thực tế, backend đang chạy ở http://localhost (cổng 80)
    // Chúng ta sẽ loại bỏ cổng của Frontend (5173, 5174...) để trỏ về cổng mặc định
    const origin = window.location.origin;

    // Nếu đang ở localhost và có cổng, hãy thử trỏ về cổng 80 (không có cổng trong URL)
    // Hoặc bạn có thể sửa thành :8000 nếu bạn dùng php artisan serve
    return origin.replace(/:\d+$/, '');
};
>>>>>>> 11a9119 (complete feature list)
