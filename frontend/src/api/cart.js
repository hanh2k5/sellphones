/**
 * Cart API Module
 * Tất cả các lời gọi HTTP liên quan đến giỏ hàng.
 */
import api from '../services/api'

export const cartApi = {
  /** Lấy giỏ hàng hiện tại */
  get: (params) => api.get('/cart', { params }),

  /** Thêm sản phẩm vào giỏ */
  add: (productId, quantity = 1) => api.post('/cart', { product_id: productId, quantity }),

  /** Cập nhật số lượng */
  update: (cartItemId, quantity) => api.put(`/cart/${cartItemId}`, { quantity }),

  /** Xóa một sản phẩm khỏi giỏ */
  remove: (cartItemId) => api.delete(`/cart/${cartItemId}`),

  /** Xóa toàn bộ giỏ hàng */
  clear: () => api.delete('/cart'),
}
