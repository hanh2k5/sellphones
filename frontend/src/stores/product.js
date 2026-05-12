import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'
import { useI18nStore } from './i18n'

export const useProductStore = defineStore('product', () => {
  const list = ref([])
  const current = ref(null)
  const trashList = ref([])
  const pagination = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Danh sách sản phẩm với filter
  async function fetchProducts(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await api.get('/products', { params })
      list.value = res.data.data
      
      // SỬA DÒNG NÀY: Trỏ đúng vào object 'meta' của Laravel Resource
      pagination.value = res.data.meta 
      
    } catch (err) {
      error.value = err.response?.data?.message || useI18nStore().t('common.error')
      console.error('fetchProducts error:', err)
    } finally {
      loading.value = false
    }
  }

  // Chi tiết sản phẩm
  async function fetchProduct(id) {
    loading.value = true
    try {
      const res = await api.get(`/products/${id}`)
      current.value = res.data
      return res.data
    } catch (e) {
      console.error('fetchProduct error:', e)
    } finally {
      loading.value = false
    }
  }

  // Thêm sản phẩm (admin)
  async function createProduct(data) {
    const res = await api.post('/admin/products', data)
    return res.data
  }

  // Sửa sản phẩm (admin)
  async function updateProduct(id, data) {
    const res = await api.put(`/admin/products/${id}`, data)
    return res.data
  }

  // Xóa mềm (admin)
  async function deleteProduct(id) {
    const res = await api.delete(`/admin/products/${id}`)
    return res.data
  }

  // Thùng rác
  async function fetchTrash() {
    const res = await api.get('/admin/products/trash')
    trashList.value = res.data.data
    return res.data
  }

  // Phục hồi
  async function restoreProduct(id) {
    const res = await api.post(`/admin/products/${id}/restore`)
    return res.data
  }

  // Xóa vĩnh viễn
  async function forceDeleteProduct(id) {
    const res = await api.delete(`/admin/products/${id}/force-delete`)
    return res.data
  }

  // Check cập nhật từ tab khác (polling)
  async function checkUpdated(id) {
    try {
      const res = await api.get(`/products/${id}/check-updated`)
      return res.data
    } catch { return { updated: false, deleted: false } }
  }

  return {
    list, current, trashList, pagination, loading, error,
    fetchProducts, fetchProduct, createProduct, updateProduct,
    deleteProduct, fetchTrash, restoreProduct, forceDeleteProduct, checkUpdated
  }
})