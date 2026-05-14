<template>
  <div class="admin-page">
    <div class="admin-toolbar">
      <h1 class="font-outfit" style="font-size: 24px; font-weight: 800;">🗂️ {{ i18n.t('admin.manage_categories_title') }}</h1>
    </div>

    <div class="admin-grid-layout">
      <!-- Category List (Tree) -->
      <CategoryTree 
        :categories="categories" 
        @edit="handleEdit" 
        @delete="deleteCategory" 
      />

      <!-- Category Form -->
      <CategoryForm 
        ref="formRef"
        :initial-data="activeCat"
        :all-categories="allCats"
        :saving="saving"
        @save="handleSave"
        @cancel="closeForm"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18nStore } from '../../stores/i18n'
import { useCategories } from '../../composables/useCategories'
import CategoryTree from '../../components/admin/CategoryTree.vue'
import CategoryForm from '../../components/admin/CategoryForm.vue'

const i18n = useI18nStore()
const { 
  categories, 
  allCats, 
  saving, 
  fetchCategories, 
  saveCategory, 
  deleteCategory 
} = useCategories()

const activeCat = ref(null)
const formRef = ref(null)

onMounted(fetchCategories)

function openNewForm() {
  activeCat.value = null
}

function handleEdit(cat) {
  activeCat.value = cat
}

function closeForm() {
  activeCat.value = null
}

async function handleSave({ id, data }) {
  const res = await saveCategory(id, data)
  if (res.success) {
    activeCat.value = null
  } else if (formRef.value) {
    formRef.value.setError(res.error)
  }
}
</script>

<style scoped>
.admin-grid-layout {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 24px;
  align-items: start;
}

@media (max-width: 768px) {
  .admin-grid-layout {
    grid-template-columns: 1fr;
  }
}
</style>
