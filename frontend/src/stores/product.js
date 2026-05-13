/**
 * Product Store — Pinia
 *
 * Trách nhiệm: Quản lý state danh sách & chi tiết sản phẩm.
 * Quy tắc: Store KHÔNG tự gọi HTTP — ủy thác hoàn toàn cho productsApi.
 */
import { ref } from 'vue'
import { defineStore } from 'pinia'
import { productsApi } from '../api'
import { useI18nStore } from './i18n'

export const useProductStore = defineStore('product', () => {
  // ─── State ─────────────────────────────────────────────────────────────────
  const list       = ref([])
  const current    = ref(null)
  const trashList  = ref([])
  const pagination = ref(null)
  const loading    = ref(false)
  const error      = ref(null)

  // ─── Helpers ───────────────────────────────────────────────────────────────
  const t = () => useI18nStore().t

  // ─── User Actions ──────────────────────────────────────────────────────────

  /** Danh sách sản phẩm (có filter, search, phân trang) */
  async function fetchProducts(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res    = await productsApi.list(params)
      list.value       = res.data.data
      pagination.value = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
    } finally {
      loading.value = false
    }
  }

  /** Chi tiết sản phẩm */
  async function fetchProduct(id) {
    loading.value = true
    try {
      const res     = await productsApi.show(id)
      current.value = res.data
      return res.data
    } catch {
      return null
    } finally {
      loading.value = false
    }
  }

  // ─── Admin Actions ─────────────────────────────────────────────────────────

  /** Tạo sản phẩm */
  async function createProduct(data) {
    const res = await productsApi.create(data)
    return res.data
  }

  /** Cập nhật sản phẩm */
  async function updateProduct(id, data) {
    const res = await productsApi.update(id, data)
    return res.data
  }

  /** Xóa mềm */
  async function deleteProduct(id) {
    const res = await productsApi.destroy(id)
    return res.data
  }

  /** Danh sách thùng rác */
  async function fetchTrash() {
    const res      = await productsApi.trash()
    trashList.value = res.data.data
    return res.data
  }

  /** Khôi phục sản phẩm */
  async function restoreProduct(id) {
    const res = await productsApi.restore(id)
    return res.data
  }

  /** Xóa vĩnh viễn */
  async function forceDeleteProduct(id) {
    const res = await productsApi.forceDelete(id)
    return res.data
  }

  return {
    // State
    list, current, trashList, pagination, loading, error,
    // Actions
    fetchProducts, fetchProduct,
    createProduct, updateProduct, deleteProduct,
    fetchTrash, restoreProduct, forceDeleteProduct,
  }
})