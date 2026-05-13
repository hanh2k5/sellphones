/**
 * Order Store — Pinia
 *
 * Trách nhiệm: Quản lý state liên quan đến đơn hàng.
 * Quy tắc: Store KHÔNG tự gọi HTTP — ủy thác hoàn toàn cho ordersApi.
 */
import { ref } from 'vue'
import { defineStore } from 'pinia'
import { ordersApi } from '../api'
import { useI18nStore } from './i18n'

export const useOrderStore = defineStore('order', () => {
  // ─── State ─────────────────────────────────────────────────────────────────
  const orders     = ref([])
  const current    = ref(null)
  const pagination = ref(null)
  const loading    = ref(false)

  // ─── Helpers ───────────────────────────────────────────────────────────────
  const t = () => useI18nStore().t

  function normalizePagination(data) {
    return {
      current_page: data.meta?.current_page ?? data.current_page ?? 1,
      last_page:    data.meta?.last_page    ?? data.last_page    ?? 1,
      total:        data.meta?.total        ?? data.total        ?? 0,
      per_page:     data.meta?.per_page     ?? data.per_page     ?? 10,
    }
  }

  function errorResult(e) {
    return {
      success:  false,
      status:   e.response?.status,
      message:  e.response?.data?.message || t()('common.error'),
    }
  }

  // ─── User Actions ──────────────────────────────────────────────────────────

  /** Lấy danh sách đơn hàng của user đang đăng nhập */
  async function fetchOrders(params = {}) {
    loading.value = true
    try {
      const res = await ordersApi.list(params)
      orders.value    = res.data.data
      pagination.value = normalizePagination(res.data)
    } finally {
      loading.value = false
    }
  }

  /** Lấy chi tiết một đơn hàng */
  async function fetchOrder(id) {
    try {
      const res = await ordersApi.show(id)
      current.value = res.data
      return res.data
    } catch {
      return null
    }
  }

  /** Đặt hàng */
  async function checkout(data) {
    try {
      const res = await ordersApi.store(data)
      return { success: true, order: res.data.order }
    } catch (e) {
      return errorResult(e)
    }
  }

  /** Hủy đơn hàng — gửi updated_at để Optimistic Locking */
  async function cancelOrder(id, updatedAt) {
    try {
      const res = await ordersApi.cancel(id, updatedAt)
      return { success: true, message: res.data.message }
    } catch (e) {
      return errorResult(e)
    }
  }

  return {
    // State
    orders, current, pagination, loading,
    // Actions
    fetchOrders, fetchOrder, checkout, cancelOrder,
  }
})
