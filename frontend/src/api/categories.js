/**
 * Categories API Module
 * Tách riêng để useCategories composable không gọi api trực tiếp.
 */
import api from '../services/api'

export const categoriesApi = {
  // ─── Public ──────────────────────────────────────────────────────────────
  tree:   (params) => api.get('/categories', { params }),
  flat:   (params) => api.get('/categories/flat', { params }),

  // ─── Admin ───────────────────────────────────────────────────────────────
  create: (data)    => api.post('/admin/categories', data),
  update: (id, data)=> api.put(`/admin/categories/${id}`, data),
  destroy:(id)      => api.delete(`/admin/categories/${id}`),
}
