<template>
  <div class="admin-card" style="padding: 24px; position: sticky; top: 80px;">
    <h2 class="font-outfit" style="font-size: 18px; font-weight: 800; margin-bottom: 20px;">
      {{ editId ? i18n.t('admin.edit_category') : i18n.t('admin.add_category') }}
    </h2>
    <form @submit.prevent="handleSubmit" novalidate style="display: flex; flex-direction: column; gap: 16px;">
      <div class="form-group">
        <label class="form-label">{{ i18n.t('admin.category_name') }} *</label>
        <input v-model="form.name" type="text" maxlength="100" class="form-input" :placeholder="i18n.t('admin.category_name') + '...'" :class="{'input-error': errors?.name}" @input="errors && (errors.name = null)" />
        <p v-if="errors?.name" class="form-error-label">{{ errors.name[0] }}</p>
      </div>
      <div class="form-group">
        <label class="form-label">{{ i18n.t('admin.parent_category') }}</label>
        <select v-model="form.parent_id" class="form-input" :class="{'input-error': errors?.parent_id}" @change="errors && (errors.parent_id = null)">
          <option :value="null">-- {{ i18n.t('admin.root_category') }} --</option>
          <option v-for="cat in availableParents" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <p v-if="errors?.parent_id" class="form-error-label">{{ errors.parent_id[0] }}</p>
      </div>
      <div class="form-group">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="form.is_active" />
          <span class="text-sm font-bold text-slate-700">{{ i18n.locale === 'vi' ? 'Kích hoạt' : 'Is Active' }}</span>
        </label>
      </div>
      <div v-if="error" class="text-danger fw-bold small" style="background: #fef2f2; padding: 10px; border-radius: 8px;">
        ⚠️ {{ error }}
      </div>
      <div style="display: flex; gap: 10px; margin-top: 10px;">
        <button v-if="editId" type="button" @click="$emit('cancel')" class="btn-secondary" style="flex: 1;">{{ i18n.t('common.cancel') }}</button>
        <button type="submit" :disabled="saving" class="btn-primary" style="flex: 2; justify-content: center;">
          {{ saving ? i18n.t('admin.saving') : (editId ? i18n.t('admin.update') : i18n.t('admin.add')) }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useI18nStore } from '../../stores/i18n'

const props = defineProps({
  initialData: { type: Object, default: null },
  allCategories: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['save', 'cancel'])
const i18n = useI18nStore()
const error = ref('')
const errors = ref({})

const form = ref({
  name: '',
  parent_id: null,
  is_active: true
})

const editId = computed(() => props.initialData?.id)

const availableParents = computed(() => {
  return props.allCategories.filter(c => c.id !== editId.value)
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { 
      name: newVal.name, 
      parent_id: newVal.parent_id, 
      is_active: !!newVal.is_active 
    }
  } else {
    form.value = { name: '', parent_id: null, is_active: true }
  }
  error.value = ''
  errors.value = {}
}, { immediate: true })

async function handleSubmit() {
  errors.value = {}
  error.value = ''
  emit('save', { id: editId.value, data: form.value })
}

function setError(val) {
  if (typeof val === 'object') {
    errors.value = val
  } else {
    error.value = val
  }
}

defineExpose({ setError })
</script>
