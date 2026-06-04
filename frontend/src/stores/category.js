import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoriesApi } from '../api'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref([])
  const allCats = ref([])
  const loading = ref(false)
  
  // Admin search & pagination states
  const searchQuery = ref('')
  const currentPage = ref(1)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
  })

  async function fetchCategories(isAdmin = false) {
    loading.value = true
    try {
      if (isAdmin) {
        // Fetch paginated & searched flat categories for admin
        const res = await categoriesApi.tree({
          all: true,
          paginate: true,
          page: currentPage.value,
          search: searchQuery.value,
          per_page: 10 // 10-15 dòng/trang
        })
        categories.value = res.data.data || []
        pagination.value = {
          current_page: res.data.current_page || 1,
          last_page: res.data.last_page || 1,
          total: res.data.total || 0,
          per_page: res.data.per_page || 10
        }

        // Fetch all flat categories for parent selection dropdown
        const flatRes = await categoriesApi.flat({ all: true })
        allCats.value = flatRes.data || []
      } else {
        // Public client-side tree
        const res = await categoriesApi.tree()
        categories.value = res.data || []
        
        const flatRes = await categoriesApi.flat()
        allCats.value = flatRes.data || []
      }
    } finally {
      loading.value = false
    }
  }

  function removeCategory(id) {
    // 5 Cập nhật State: thực hiện filter() loại bỏ phần tử khỏi State trong Pinia
    categories.value = categories.value.filter(c => c.id !== id)
    allCats.value = allCats.value.filter(c => c.id !== id)
  }

  return { 
    categories, 
    allCats, 
    loading, 
    searchQuery, 
    currentPage, 
    pagination, 
    fetchCategories, 
    removeCategory 
  }
})
