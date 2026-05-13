/**
 * Auth API Module
 * Tất cả các lời gọi HTTP liên quan đến xác thực người dùng.
 * Stores chỉ gọi qua module này, KHÔNG gọi api.js trực tiếp.
 */
import api from '../services/api'

export const authApi = {
  /** Đăng nhập */
  login: (credentials) => api.post('/login', credentials),

  /** Đăng ký */
  register: (data) => api.post('/register', data),

  /** Đăng xuất */
  logout: () => api.post('/logout'),

  /** Lấy thông tin user hiện tại */
  me: () => api.get('/me'),
}
