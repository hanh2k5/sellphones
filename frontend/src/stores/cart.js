import { defineStore } from 'pinia'
import api from '../services/api'
import { useAuthStore } from './auth'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    loading: false,
    error: null
  }),

  getters: {
    totalItems: (state) => state.items.reduce((total, item) => total + item.quantity, 0)
  },

  actions: {
    async fetchCart() {
      const authStore = useAuthStore()
      if (!authStore.isLoggedIn) return

      this.loading = true
      try {
        const res = await api.get('/cart')
        this.items = res.data.data
      } catch (err) {
        console.error('Lỗi tải giỏ hàng:', err)
      } finally {
        this.loading = false
      }
    },

    async addToCart(productId, quantity = 1) {
      const authStore = useAuthStore()
      if (!authStore.isLoggedIn) {
        throw new Error('Vui lòng đăng nhập để thêm vào giỏ hàng')
      }

      this.loading = true
      try {
        const res = await api.post('/cart', { product_id: productId, quantity })
        await this.fetchCart() // Refresh lại danh sách
        return res.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Không thể thêm vào giỏ hàng'
        throw err
      } finally {
        this.loading = false
      }
    }
  }
})
