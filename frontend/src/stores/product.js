import { defineStore } from 'pinia'
import api from '../services/api'

export const useProductStore = defineStore('product', {
  state: () => ({
    list: [],
    loading: false,
    pagination: null,
    error: null
  }),

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true
      this.error = null
      try {
        const res = await api.get('/products', { params })
        this.list = res.data.data
        this.pagination = {
          current_page: res.data.current_page,
          last_page: res.data.last_page,
          total: res.data.total,
          per_page: res.data.per_page
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Lỗi tải sản phẩm'
      } finally {
        this.loading = false
      }
    }
  }
})