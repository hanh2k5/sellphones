import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'
import { useI18nStore } from './i18n'

export const useOrderStore = defineStore('order', () => {
  const orders     = ref([])
  const current    = ref(null)
  const pagination = ref(null)
  const loading    = ref(false)

  async function fetchOrders(params = {}) {
    loading.value = true
    try {
      const res = await api.get('/orders', { params })
      orders.value = res.data.data
      pagination.value = {
        current_page: res.data.current_page,
        last_page:    res.data.last_page,
        total:        res.data.total,
        per_page:     res.data.per_page,
        from:         res.data.from,
        to:           res.data.to,
      }
    } catch (e) {
      console.error('fetchOrders error:', e)
    } finally {
      loading.value = false
    }
  }

  async function fetchOrder(id) {
    try {
      const res = await api.get(`/orders/${id}`)
      current.value = res.data
      return res.data
    } catch (e) {
      console.error('fetchOrder error:', e)
      return null
    }
  }

  async function checkout(data) {
    try {
      const res = await api.post('/orders', data)
      return { success: true, order: res.data.order }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message || useI18nStore().t('common.error'),
      }
    }
  }

  async function cancelOrder(id, updatedAt = null) {
    try {
      const payload = updatedAt ? { updated_at: updatedAt } : {}
      const res = await api.post(`/orders/${id}/cancel`, payload)
      return { success: true, message: res.data.message }
    } catch (e) {
      return {
        success:  false,
        message:  e.response?.data?.message || useI18nStore().t('common.error'),
        conflict: e.response?.data?.conflict || false,
        status:   e.response?.data?.status,
      }
    }
  }

  async function approveOrder(id, updatedAt = null) {
    try {
      const payload = updatedAt ? { updated_at: updatedAt } : {}
      const res = await api.post(`/orders/${id}/confirm`, payload)
      return { success: true, data: res.data }
    } catch (e) {
      return {
        success:  false,
        message:  e.response?.data?.message || useI18nStore().t('common.error'),
        conflict: e.response?.data?.conflict || false,
        status:   e.response?.data?.status,
      }
    }
  }

  async function deleteOrder(id) {
    try {
      const res = await api.delete(`/admin/orders/${id}`)
      return { success: true, message: res.data.message }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message || useI18nStore().t('common.error'),
      }
    }
  }

  async function checkOrderStatus(id) {
    try {
      const res = await api.get(`/orders/${id}/status`)
      return res.data
    } catch {
      return { changed: false }
    }
  }

  return {
    orders, current, pagination, loading,
    fetchOrders, fetchOrder, checkout,
    cancelOrder, approveOrder, deleteOrder, checkOrderStatus,
  }
})
