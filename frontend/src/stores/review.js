import { ref } from 'vue'
import { defineStore } from 'pinia'
import { reviewsApi } from '../api'
import { useI18nStore } from './i18n'

export const useReviewStore = defineStore('review', () => {
  // ─── State ─────────────────────────────────────────────────────────────────
  const list = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const error = ref(null)

  // ─── Helpers ───────────────────────────────────────────────────────────────
  const t = () => useI18nStore().t

  // ─── Actions ───────────────────────────────────────────────────────────────

  /** Lấy danh sách review cho Admin */
  async function fetchAdminReviews(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await reviewsApi.adminList(params)
      list.value = res.data.data || []
      pagination.value = res.data.meta || {
        current_page: res.data.current_page || 1,
        last_page: res.data.last_page || 1,
        total: res.data.total || res.data.data?.length || 0,
      }
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Duyệt/Ẩn đánh giá */
  async function moderateReview(id, status) {
    loading.value = true
    error.value = null
    try {
      const res = await reviewsApi.moderate(id, status)
      const updated = res.data?.review || res.data
      const idx = list.value.findIndex(r => r.id === id)
      if (idx !== -1) {
        list.value[idx].status = updated.status
      }
      return updated
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Xóa đánh giá (Admin) */
  async function deleteReviewAdmin(id) {
    loading.value = true
    error.value = null
    try {
      await reviewsApi.adminDelete(id)
      list.value = list.value.filter(r => r.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Gửi đánh giá mới */
  async function submitReview(productId, data) {
    loading.value = true
    error.value = null
    try {
      const res = await reviewsApi.create(productId, data)
      return res.data
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Cập nhật đánh giá (User) */
  async function updateReviewUser(id, data) {
    loading.value = true
    error.value = null
    try {
      const res = await reviewsApi.update(id, data)
      return res.data
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Xóa đánh giá (User) */
  async function deleteReviewUser(id) {
    loading.value = true
    error.value = null
    try {
      await reviewsApi.delete(id)
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    list,
    pagination,
    loading,
    error,
    fetchAdminReviews,
    moderateReview,
    deleteReviewAdmin,
    submitReview,
    updateReviewUser,
    deleteReviewUser,
  }
})
