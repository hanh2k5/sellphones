import { ref } from 'vue'
import { defineStore } from 'pinia'
import { cartApi, vouchersApi } from '../api'
import { useI18nStore } from './i18n'
import { useToast } from '../composables/useToast'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const pagination = ref(null)
  const tongTien = ref(0)
  const soLuong = ref(0)
  const loading = ref(false)
  const safeParse = (key) => {
    try {
      const val = localStorage.getItem(key)
      if (!val || val === 'undefined') return null
      return JSON.parse(val)
    } catch {
      return null
    }
  }

  const appliedVoucher = ref(safeParse('cart_voucher'))
  const tienGiam = ref(Number(localStorage.getItem('cart_discount') || 0))

  async function fetchCart(page = 1) {
    loading.value = true
    try {
      const res = await cartApi.get({ page })
      items.value = res.data.items.data
      pagination.value = {
        current_page: res.data.items.current_page,
        last_page: res.data.items.last_page,
        total: res.data.items.total
      }
      tongTien.value = res.data.total_amount
      soLuong.value = res.data.total_quantity
      
      // Nếu đã có voucher, tính toán lại tiền giảm dựa trên tổng tiền mới
      if (appliedVoucher.value) {
        await applyVoucher(appliedVoucher.value.code)
      }
    } catch (e) {
      useToast().error(e.response?.data?.message || useI18nStore().t('common.error'))
    } finally {
      loading.value = false
    }
  }

  async function addToCart(productId, quantity = 1) {
    try {
      const res = await cartApi.add(productId, quantity)
      await fetchCart()
      return { success: true, message: res.data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message || useI18nStore().t('common.error') }
    }
  }

  async function removeFromCart(cartItemId) {
    try {
      await cartApi.remove(cartItemId)
      await fetchCart(pagination.value?.current_page || 1)
      return { success: true }
    } catch (e) {
      return { success: false }
    }
  }

  async function updateQty(cartItemId, qty) {
    try {
      await cartApi.update(cartItemId, qty)
      await fetchCart()
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  async function clearCart() {
    try { await cartApi.clear() } catch {}
    items.value = []
    tongTien.value = 0
    soLuong.value = 0
    appliedVoucher.value = null
    tienGiam.value = 0
    localStorage.removeItem('cart_voucher')
    localStorage.removeItem('cart_discount')
    await fetchCart() // Force sync
  }

  async function applyVoucher(code) {
    try {
      const res = await vouchersApi.apply(code)
      appliedVoucher.value = res.data.voucher
      tienGiam.value = res.data.discount
      localStorage.setItem('cart_voucher', JSON.stringify(res.data.voucher))
      localStorage.setItem('cart_discount', res.data.discount.toString())
      return { success: true, data: res.data }
    } catch (e) {
      appliedVoucher.value = null
      tienGiam.value = 0
      localStorage.removeItem('cart_voucher')
      localStorage.removeItem('cart_discount')
      return { success: false, message: e.response?.data?.message || useI18nStore().t('common.error') }
    }
  }

  function recalcTotal() {
    tongTien.value = items.value.reduce((sum, i) => sum + (i.product?.price || 0) * i.quantity, 0)
    soLuong.value = items.value.reduce((sum, i) => sum + i.quantity, 0)
  }

  const thanhToan = () => Math.max(0, tongTien.value - tienGiam.value)

  return {
    items, pagination, tongTien, soLuong, loading, appliedVoucher, tienGiam, thanhToan,
    fetchCart, addToCart, removeFromCart, updateQty, clearCart, applyVoucher
  }
})
