/**
 * Admin API Module
 * Tất cả các lời gọi HTTP dành riêng cho Admin (dashboard, stats...).
 */
import api from '../services/api'

export const adminApi = {
  /** Dashboard stats (doanh thu, đơn hàng, user, sắp hết hàng) */
  dashboard: () => api.get('/admin/dashboard'),

  // ─── User Management ──────────────────────────────────────────────────────
  /** Lấy danh sách users */
  userList: (params) => api.get('/admin/users', { params }),
  /** Tạo user mới */
  userCreate: (data) => api.post('/admin/users', data),
  /** Cập nhật thông tin user */
  userUpdate: (id, data) => api.put(`/admin/users/${id}`, data),
  /** Xóa user */
  userDelete: (id) => api.delete(`/admin/users/${id}`),
  /** Khóa tài khoản user */
  userLock: (id) => api.post(`/admin/users/${id}/lock`),
  /** Mở khóa tài khoản user */
  userUnlock: (id) => api.post(`/admin/users/${id}/unlock`),
}

