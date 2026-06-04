/**
 * Reviews API Module
 * Tất cả các lời gọi HTTP liên quan đến đánh giá sản phẩm.
 */
import api from '../services/api'

export const reviewsApi = {
  /** Danh sách đánh giá đã duyệt của sản phẩm */
  list: (productId, params) => api.get(`/products/${productId}/reviews`, { params }),

  /** Gửi đánh giá mới */
  create: (productId, data) => api.post(`/products/${productId}/reviews`, data),

  /** Cập nhật đánh giá */
  update: (id, data) => api.put(`/reviews/${id}`, data),

  /** Xóa đánh giá (User) */
  delete: (id) => api.delete(`/reviews/${id}`),

  /** Xóa đánh giá (Admin) */
  adminDelete: (id) => api.delete(`/admin/reviews/${id}`),

  /** Lấy danh sách đánh giá cho Admin */
  adminList: (params) => api.get('/admin/reviews', { params }),

  /** Duyệt/Ẩn đánh giá */
  moderate: (id, status) => api.put(`/admin/reviews/${id}/moderate`, { status }),
}

