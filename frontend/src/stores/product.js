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
  const reviewList = ref([])
  const reviewPagination = ref({ meta: null, links: null })
  const reviewsLoading = ref(false)
  const trashList  = ref([])
  const pagination = ref(null)
  const loading    = ref(false)
  const error      = ref(null)
  const listFilters = ref({
    category_id: null,
    search: '',
    gia_tu: '',
    gia_den: '',
    page: 1,
    sort_by: 'created_at',
    sort_dir: 'desc'
  })
  const listMessage = ref('')

  function setFilters(newFilters) {
    listFilters.value = { ...listFilters.value, ...newFilters }
  }

  function resetFilters() {
    listFilters.value = {
      category_id: null,
      search: '',
      gia_tu: '',
      gia_den: '',
      page: 1,
      sort_by: 'created_at',
      sort_dir: 'desc'
    }
    listMessage.value = ''
  }

  // ─── Helpers ───────────────────────────────────────────────────────────────
  const t = () => useI18nStore().t

  // ─── User Actions ──────────────────────────────────────────────────────────

  /** Danh sách sản phẩm (có filter, search, phân trang) */
  async function fetchProducts(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const mergedParams = { ...listFilters.value, ...params }
      // Clean up empty params
      Object.keys(mergedParams).forEach(key => {
        if (mergedParams[key] === '' || mergedParams[key] === null || mergedParams[key] === undefined) {
          delete mergedParams[key]
        }
      })
      const res    = await productsApi.list(mergedParams)
      list.value       = res.data.data
      pagination.value = res.data.meta
      listMessage.value = res.data.message || ''
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

  function setCurrentProduct(product) {
    current.value = product
  }

  async function fetchProductReviews(productId, { page = 1, append = false, perPage = 5 } = {}) {
    reviewsLoading.value = true
    error.value = null
    try {
      const res = await productsApi.reviews(productId, { page, per_page: perPage })
      const reviews = res.data.data || []
      reviewList.value = append ? [...reviewList.value, ...reviews] : reviews
      reviewPagination.value = {
        meta: res.data.meta || null,
        links: res.data.links || null,
      }

      if (current.value?.id === productId) {
        current.value = { ...current.value, reviews: reviewList.value }
      }

      return res.data
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      reviewsLoading.value = false
    }
  }

  function syncProductReviewSummary(productId, avgRating, reviews = null) {
    const normalizedAvg = avgRating ?? 0

    if (current.value?.id === productId) {
      current.value = {
        ...current.value,
        avg_rating: normalizedAvg,
        ...(reviews ? { reviews } : {}),
      }
    }

    const idx = list.value.findIndex(p => p.id === productId)
    if (idx !== -1) {
      list.value[idx] = {
        ...list.value[idx],
        avg_rating: normalizedAvg,
      }
    }
  }

  function addProductReview(productId, review, avgRating) {
    reviewList.value = [review, ...reviewList.value.filter(item => item.id !== review.id)]
    syncProductReviewSummary(productId, avgRating, reviewList.value)
  }

  function replaceProductReview(productId, review, avgRating) {
    const idx = reviewList.value.findIndex(item => item.id === review.id)
    if (idx !== -1) {
      reviewList.value[idx] = review
    }
    syncProductReviewSummary(productId, avgRating, reviewList.value)
  }

  function removeProductReview(productId, reviewId, avgRating) {
    reviewList.value = reviewList.value.filter(item => item.id !== reviewId)
    syncProductReviewSummary(productId, avgRating, reviewList.value)
  }

  // ─── Admin Actions ─────────────────────────────────────────────────────────

  /** Tạo sản phẩm */
  async function createProduct(data) {
    const res = await productsApi.create(data)
    const newProduct = res.data?.data || res.data
    list.value.unshift(newProduct)
    return newProduct
  }

  /** Cập nhật sản phẩm */
  async function updateProduct(id, data) {
    const res = await productsApi.update(id, data)
    const updated = res.data?.data || res.data
    const idx = list.value.findIndex(p => p.id === id)
    if (idx !== -1) {
      list.value[idx] = { ...list.value[idx], ...updated }
    }
    return updated
  }

  /** Xóa mềm */
  async function deleteProduct(id, version = null) {
    const res = await productsApi.destroy(id, version ? { updated_at: version } : undefined)
    list.value = list.value.filter(p => p.id !== id)
    return res.data
  }

  /** Danh sách thùng rác */
  async function fetchTrash() {
    const res      = await productsApi.trash()
    trashList.value = res.data.data
    return res.data
  }

  /** Khôi phục sản phẩm */
  async function restoreProduct(id, version = null) {
    const res = await productsApi.restore(id, version ? { updated_at: version } : undefined)
    return res.data
  }

  /** Xóa vĩnh viễn */
  async function forceDeleteProduct(id) {
    const res = await productsApi.forceDelete(id)
    return res.data
  }

  return {
    // State
    list, current, reviewList, reviewPagination, reviewsLoading, trashList, pagination, loading, error, listFilters, listMessage,
    // Actions
    fetchProducts, fetchProduct, setCurrentProduct, fetchProductReviews,
    syncProductReviewSummary, addProductReview, replaceProductReview, removeProductReview,
    setFilters, resetFilters,
    createProduct, updateProduct, deleteProduct,
    fetchTrash, restoreProduct, forceDeleteProduct,
  }
})
