/**
 * Vouchers API Module
 */
import api from '../services/api'

export const vouchersApi = {
  /** Danh sách voucher có thể dùng */
  list: () => api.get('/vouchers'),

  /** Áp dụng mã giảm giá */
  apply: (code) => api.post('/vouchers/apply', { code }),
}
