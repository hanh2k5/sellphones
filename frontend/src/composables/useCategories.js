import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCategoryStore } from '../stores/category'
import { categoriesApi } from '../api'
import { useToast } from './useToast'
import { useI18nStore } from '../stores/i18n'
import Swal from 'sweetalert2'

export function useCategories() {
  const toast = useToast()
  const i18n = useI18nStore()
  const categoryStore = useCategoryStore()

  const { 
    categories, 
    allCats, 
    loading, 
    searchQuery, 
    currentPage, 
    pagination 
  } = storeToRefs(categoryStore)
  const saving = ref(false)

  async function fetchCategories(isAdmin = false) {
    loading.value = true
    try {
      await categoryStore.fetchCategories(isAdmin)
    } catch {
      toast.error(i18n.t('common.error'))
    } finally {
      loading.value = false
    }
  }

  async function saveCategory(id, formData) {
    saving.value = true
    try {
      let res
      if (id) {
        res = await categoriesApi.update(id, formData)
      } else {
        res = await categoriesApi.create(formData)
      }
      toast.success(i18n.t('admin.category_saved_success'))
      await fetchCategories(true)
      return { success: true }
    } catch (e) {
      let errorMsg = e.response?.data?.message || i18n.t('common.error')
      if (!e.response || e.response.status === 500) {
        errorMsg = 'Lỗi kết nối máy chủ, chưa lưu được.'
      }
      toast.error(errorMsg)
      return { success: false, error: e.response?.data?.errors || errorMsg }
    } finally {
      saving.value = false
    }
  }

  async function deleteCategory(cat) {
    const result = await Swal.fire({
      title: 'Xác nhận xóa',
      text: 'Xóa danh mục này sẽ không thể khôi phục, bạn chắc chứ?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e11d48',
      cancelButtonColor: '#94a3b8',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      reverseButtons: true
    })

    if (!result.isConfirmed) return { success: false }

    try {
      await categoriesApi.destroy(cat.id)
      categoryStore.removeCategory(cat.id)
      toast.success('Đã xóa danh mục thành công!')
      return { success: true }
    } catch (e) {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
      return { success: false }
    }
  }

  return { 
    categories, 
    allCats, 
    loading, 
    saving, 
    searchQuery, 
    currentPage, 
    pagination, 
    fetchCategories, 
    saveCategory, 
    deleteCategory 
  }
}

