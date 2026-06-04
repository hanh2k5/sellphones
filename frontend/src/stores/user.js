import { ref } from 'vue'
import { defineStore } from 'pinia'
import { adminApi } from '../api'
import { useI18nStore } from './i18n'

export const useUserStore = defineStore('user', () => {
  // ─── State ─────────────────────────────────────────────────────────────────
  const list = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const error = ref(null)

  // ─── Helpers ───────────────────────────────────────────────────────────────
  const t = () => useI18nStore().t

  // ─── Actions ───────────────────────────────────────────────────────────────

  /** Lấy danh sách user */
  async function fetchUsers(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await adminApi.userList(params)
      list.value = res.data.data
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

  /** Tạo user mới */
  async function createUser(data) {
    loading.value = true
    error.value = null
    try {
      const res = await adminApi.userCreate(data)
      const newUser = res.data?.data || res.data?.user || res.data
      list.value.unshift(newUser)
      return newUser
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Cập nhật user */
  async function updateUser(id, data) {
    loading.value = true
    error.value = null
    try {
      const res = await adminApi.userUpdate(id, data)
      const updated = res.data?.data || res.data?.user || res.data
      const idx = list.value.findIndex(u => u.id === id)
      if (idx !== -1) {
        list.value[idx] = { ...list.value[idx], ...updated }
      }
      return updated
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Xóa user */
  async function deleteUser(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.userDelete(id)
      list.value = list.value.filter(u => u.id !== id)
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Khóa tài khoản user */
  async function lockUser(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.userLock(id)
      const idx = list.value.findIndex(u => u.id === id)
      if (idx !== -1) {
        list.value[idx].is_active = false
      }
    } catch (e) {
      error.value = e.response?.data?.message || t()('common.error')
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Mở khóa tài khoản user */
  async function unlockUser(id) {
    loading.value = true
    error.value = null
    try {
      await adminApi.userUnlock(id)
      const idx = list.value.findIndex(u => u.id === id)
      if (idx !== -1) {
        list.value[idx].is_active = true
      }
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
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    lockUser,
    unlockUser,
  }
})
