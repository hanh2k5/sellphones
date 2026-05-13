/**
 * Admin API Module
 * Tất cả các lời gọi HTTP dành riêng cho Admin (dashboard, stats...).
 */
import api from '../services/api'

export const adminApi = {
  /** Dashboard stats (doanh thu, đơn hàng, user, sắp hết hàng) */
  dashboard: () => api.get('/admin/dashboard'),
}
