/**
 * Orders API Module
 * Tất cả các lời gọi HTTP liên quan đến đơn hàng (user + admin).
 */
import api from '../services/api'

export const ordersApi = {
  // ─── User ──────────────────────────────────────────────────────────────────

  /** Danh sách đơn hàng của user đang đăng nhập */
  list: (params) => api.get('/orders', { params }),

  /** Chi tiết một đơn hàng */
  show: (id) => api.get(`/orders/${id}`),

  /** Đặt hàng (checkout) */
  store: (data) => api.post('/orders', data),

  /** Xác nhận thanh toán MoMo */
  confirmPayment: (id) => api.post(`/orders/${id}/confirm-payment`),

  /** Hủy đơn hàng (user) — gửi updated_at để Optimistic Locking */
  cancel: (id, updatedAt) => api.post(`/orders/${id}/cancel`, { updated_at: updatedAt }),

  // ─── Admin ─────────────────────────────────────────────────────────────────

  /** Danh sách tất cả đơn hàng (admin, có filter & search) */
  adminList: (params) => api.get('/admin/orders', { params }),

  /**
   * Duyệt đơn hàng: pending → confirmed
   * Gửi status và updated_at để backend kiểm tra Optimistic Locking (4.1.8)
   */
  adminConfirm: (id, status, updatedAt) =>
    api.post(`/admin/orders/${id}/confirm`, { status, updated_at: updatedAt }),

  /** Cập nhật trạng thái chung (ví dụ confirmed → shipping) */
  adminUpdateStatus: (id, status, lastUpdatedAt) =>
    api.put(`/admin/orders/${id}/status`, { status, last_updated_at: lastUpdatedAt }),

  /** Hoàn thành đơn: shipping → completed */
  adminComplete: (id, updatedAt) =>
    api.post(`/admin/orders/${id}/complete`, { updated_at: updatedAt }),

  /** Xóa đơn đã hủy */
  adminDelete: (id) => api.delete(`/admin/orders/${id}`),
}
