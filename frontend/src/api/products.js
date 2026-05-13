/**
 * Products API Module
 * Tất cả các lời gọi HTTP liên quan đến sản phẩm & danh mục.
 */
import api from '../services/api'

export const productsApi = {
  // ─── Public ──────────────────────────────────────────────────────────────

  /** Danh sách sản phẩm (có filter, search, phân trang) */
  list:           (params)  => api.get('/products', { params }),

  /** Chi tiết sản phẩm */
  show:           (id)      => api.get(`/products/${id}`),

  /** Danh sách danh mục (cây) */
  categories:     ()        => api.get('/categories'),

  /** Danh sách danh mục phẳng */
  categoriesFlat: ()        => api.get('/categories/flat'),

  // ─── Admin ───────────────────────────────────────────────────────────────

  /** Tạo sản phẩm mới */
  create:         (data)    => api.post('/admin/products', data),

  /** Cập nhật sản phẩm */
  update:         (id, data)=> api.put(`/admin/products/${id}`, data),

  /** Xóa mềm */
  destroy:        (id)      => api.delete(`/admin/products/${id}`),

  /** Danh sách thùng rác */
  trash:          ()        => api.get('/admin/products/trash'),

  /** Khôi phục sản phẩm */
  restore:        (id)      => api.post(`/admin/products/${id}/restore`),

  /** Xóa vĩnh viễn */
  forceDelete:    (id)      => api.delete(`/admin/products/${id}/force-delete`),
}
