import { ref } from 'vue'
import { categoriesApi } from '../api'
import { useToast } from './useToast'
import { useI18nStore } from '../stores/i18n'
import Swal from 'sweetalert2'

export function useCategories() {
  const toast = useToast()
  const i18n = useI18nStore()
  const categories = ref([])
  const allCats = ref([])
  const loading = ref(false)
  const saving = ref(false)

  async function fetchCategories() {
    loading.value = true
    try {
      const [tree, flat] = await Promise.all([
        categoriesApi.tree(),
        categoriesApi.flat()
      ])
      categories.value = tree.data
      allCats.value = flat.data
    } catch {
      toast.error(i18n.t('common.error'))
    } finally {
      loading.value = false
    }
  }

  async function saveCategory(id, formData) {
    saving.value = true
    try {
      if (id) {
        await categoriesApi.update(id, formData)
      } else {
        await categoriesApi.create(formData)
      }
      toast.success(i18n.t('admin.category_saved_success'))
      await fetchCategories()
      return { success: true }
    } catch (e) {
      const errorMsg = e.response?.data?.message || i18n.t('common.error')
      toast.error(errorMsg)
      return { success: false, error: errorMsg }
    } finally {
      saving.value = false
    }
  }

  async function deleteCategory(cat) {
    const result = await Swal.fire({
      title: i18n.t('common.confirm'),
      text: `${i18n.t('product.delete')} ${cat.name}?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e11d48',
      cancelButtonColor: '#94a3b8',
      confirmButtonText: i18n.t('common.delete'),
      cancelButtonText: i18n.t('common.cancel'),
      reverseButtons: true
    })

    if (!result.isConfirmed) return { success: false }

    try {
      await categoriesApi.destroy(cat.id)
      toast.success(i18n.t('admin.category_deleted_success'))
      await fetchCategories()
      return { success: true }
    } catch (e) {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
      return { success: false }
    }
  }

  return { categories, allCats, loading, saving, fetchCategories, saveCategory, deleteCategory }
}
