/**
 * Profile API Module
 * Các lời gọi HTTP liên quan đến hồ sơ cá nhân của user đang đăng nhập.
 */
import api from '../services/api'

export const profileApi = {
  /** Cập nhật hồ sơ cá nhân (có Optimistic Locking qua updated_at) */
  update: (data) => api.put('/profile', data),

  /** Cập nhật mật khẩu */
  updatePassword: (data) => api.put('/profile/password', data),
}
