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

  /** Đánh giá đã duyệt của sản phẩm */
  reviews:        (id, params) => api.get(`/products/${id}/reviews`, { params }),

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
  destroy:        (id, params) => api.delete(`/admin/products/${id}`, { params }),

  /** Danh sách thùng rác */
  trash:          (params)  => api.get('/admin/products/trash', { params }),

  /** Khôi phục sản phẩm */
  restore:        (id, data)      => api.post(`/admin/products/${id}/restore`, data),

  /** Xóa vĩnh viễn */
  forceDelete:    (id)      => api.delete(`/admin/products/${id}/force-delete`),

  /** Kiểm tra xung đột (Optimistic Locking) */
  checkUpdated:   (id, lastTime) => api.get(`/admin/products/${id}/check-updated`, { params: { last_time: lastTime } }),

  /** Upload ảnh chính */
  upload:         (formData) => api.post('/admin/upload', formData),

  /** Upload ảnh phụ */
  uploadImages:   (id, formData) => api.post(`/admin/products/${id}/images`, formData),

  /** Xóa ảnh phụ */
  deleteImage:    (id, imageId) => api.delete(`/admin/products/${id}/images/${imageId}`),
}
