<template>
  <div class="admin-page" style="max-width: 800px; margin: 0 auto;">
    <div class="admin-toolbar" style="margin-bottom: 10px;">
      <router-link to="/admin/products" class="text-muted fw-bold" style="text-decoration: none;">
        ← {{ i18n.t('admin.back_to_list') }}
      </router-link>
    </div>

    <!-- Conflict alert (2-tab error) -->
    <div v-if="conflictMsg" class="admin-card" style="border: 1px solid #fca5a5; background: #fef2f2; padding: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 32px; height: 32px; background: #ef4444; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold;">!</div>
        <div>
          <p class="fw-bold text-danger" style="margin: 0; font-size: 14px;">{{ conflictMsg }}</p>
          <p class="text-muted" style="margin: 0; font-size: 12px;">{{ i18n.t('admin.data_changed_desc') }}</p>
        </div>
      </div>
      <button @click="refreshData" class="btn-danger" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 6px 12px; cursor: pointer;">{{ i18n.t('common.refresh') }}</button>
    </div>

    <div class="admin-card" style="padding: 24px;">
      <h2 class="font-outfit" style="font-size: 20px; font-weight: 800; margin-bottom: 24px;">
        {{ isEdit ? i18n.t('admin.edit_product') : i18n.t('admin.create_product') }}
      </h2>

      <form @submit.prevent="handleSave" novalidate style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Basic Info -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">{{ i18n.t('admin.product_name') }} *</label>
            <input v-model="form.name" type="text" maxlength="150" class="form-input" :placeholder="i18n.t('admin.product_name') + '...'" :class="{'input-error': errors?.name}" @input="errors && (errors.name = null)" />
            <p v-if="errors?.name" class="form-error-label">{{ errors.name[0] }}</p>
          </div>
          
          <div class="form-group">
            <label class="form-label">{{ i18n.t('product.price') }} ({{ i18n.locale === 'vi' ? 'VNĐ' : 'USD' }}) *</label>
            <input v-model.number="form.price" type="number" min="0" class="form-input" :class="{'input-error': errors?.price}" @input="errors && (errors.price = null)" />
            <p v-if="errors?.price" class="form-error-label">{{ errors.price[0] }}</p>
          </div>

          <div class="form-group">
            <label class="form-label">{{ i18n.t('product.stock') }} *</label>
            <input v-model.number="form.stock" type="number" min="0" class="form-input" :class="{'input-error': errors?.stock}" @input="errors && (errors.stock = null)" />
            <p v-if="errors?.stock" class="form-error-label">{{ errors.stock[0] }}</p>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">{{ i18n.t('admin.categories') }} *</label>
            <select v-model="form.category_id" class="form-input" :class="{'input-error': errors?.category_id}" @change="errors && (errors.category_id = null)" style="font-weight: 600;">
              <option :value="null">-- {{ i18n.t('admin.select_category') }} --</option>
              
              <template v-for="parent in categories" :key="parent.id">
                <option :value="parent.id" style="font-weight: 800; background: #f8fafc; color: #000;">
                  {{ parent.name.toUpperCase() }} (DANH MỤC GỐC)
                </option>
                <option v-for="child in parent.children || []" :key="child.id" :value="child.id">
                  &nbsp;&nbsp;&nbsp;-- {{ child.name }}
                </option>
              </template>
            </select>
            <p v-if="errors?.category_id" class="form-error-label">{{ errors.category_id[0] }}</p>
          </div>
        </div>

        <!-- Main Image -->
        <div class="form-group">
          <label class="form-label">{{ i18n.t('admin.product_thumbnail') }}</label>
          <div style="display: flex; gap: 10px;">
            <input v-model="form.hinh_anh" type="text" placeholder="https://..." class="form-input" style="flex: 1;" />
            <input type="file" ref="fileInputMain" accept="image/*" @change="e => handleFileUpload(e, 'main')" style="display: none;" />
            <button type="button" @click="$refs.fileInputMain.click()" :disabled="uploadingMain" class="btn-secondary" style="white-space: nowrap;">
              {{ uploadingMain ? '⏳ ' + i18n.t('admin.uploading') : '📁 ' + i18n.t('admin.upload_image') }}
            </button>
          </div>
          <div v-if="form.hinh_anh" style="margin-top: 10px; width: 100px; height: 100px; background: #f5f5f7; border-radius: 12px; padding: 4px; position: relative;">
            <img :src="getImageUrl(form.hinh_anh)" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;" />
            <button type="button" @click="form.hinh_anh = ''" style="position: absolute; top: -5px; right: -5px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer;">×</button>
          </div>
        </div>

        <!-- Secondary Images (Multi-upload) -->
        <div class="form-group">
          <label class="form-label">{{ i18n.t('admin.secondary_images') }}</label>
          <div @click="$refs.fileInputMulti.click()" style="background: #fafafa; border: 2px dashed #d2d2d7; border-radius: 16px; padding: 24px; text-align: center; cursor: pointer; transition: 0.2s;">
            <input type="file" ref="fileInputMulti" accept="image/*" multiple @change="e => handleFileUpload(e, 'multi')" style="display: none;" />
            <div style="font-size: 24px; margin-bottom: 8px;">📸</div>
            <p class="fw-bold text-muted" style="margin: 0; font-size: 12px; text-transform: uppercase;">{{ i18n.t('admin.upload_multi_desc') }}</p>
            <p v-if="uploadingMulti" class="text-success fw-bold small" style="margin-top: 8px;">{{ i18n.t('admin.uploading') }}</p>
          </div>

          <div v-if="form.images?.length" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 16px;">
            <div v-for="(img, idx) in form.images" :key="idx" style="aspect-ratio: 1; border: 1px solid #f0f0f0; border-radius: 12px; position: relative; overflow: hidden;">
              <img :src="getImageUrl(img.image_path)" style="width: 100%; height: 100%; object-fit: cover;" />
              <button type="button" @click="removeImage(idx)" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; opacity: 0; transition: 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                {{ i18n.t('common.delete') }}
              </button>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">{{ i18n.t('product.description') }}</label>
          <textarea v-model="form.description" rows="5" class="form-input" :placeholder="i18n.t('product.description') + '...'" style="resize: vertical;"></textarea>
        </div>

        <div style="display: flex; gap: 24px; background: #fafafa; padding: 16px; border-radius: 12px; border: 1px solid #f0f0f0;">
          <label class="toggle-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" v-model="form.is_active" class="apple-checkbox" />
            <span style="font-size: 13px; font-weight: 700; color: #1d1d1f;">{{ i18n.t('admin.show_product') || 'Hiển thị sản phẩm' }}</span>
          </label>
          <label class="toggle-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" v-model="form.is_featured" class="apple-checkbox" />
            <span style="font-size: 13px; font-weight: 700; color: #1d1d1f;">{{ i18n.t('admin.featured_product') || 'Sản phẩm nổi bật' }}</span>
          </label>
        </div>

        <div v-if="errorMsg" class="text-danger fw-bold small" style="background: #fef2f2; padding: 10px; border-radius: 8px;">
          ⚠️ {{ errorMsg }}
        </div>

        <div style="display: flex; gap: 12px; margin-top: 10px;">
          <router-link to="/admin/products" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">{{ i18n.t('common.cancel') }}</router-link>
          <button type="submit" :disabled="saving" class="btn-primary" style="flex: 2; justify-content: center;">
            {{ saving ? i18n.t('admin.saving') : (isEdit ? i18n.t('admin.update') : i18n.t('admin.add')) }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useUtils } from '../../composables/useUtils'
import { useI18nStore } from '../../stores/i18n'

const { getImageUrl } = useUtils()
const toast = useToast()
const i18n = useI18nStore()
const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const categories = ref([])
const saving = ref(false)
const errorMsg = ref('')
const errors = ref({})
const conflictMsg = ref('')
const versionKey = ref(null)
const uploadingMain = ref(false)
const uploadingMulti = ref(false)

const form = ref({ 
  name: '', price: 0, stock: 0, category_id: null, 
  hinh_anh: '', description: '', images: [],
  is_active: true, is_featured: false
})

let pollingTimer = null

onMounted(async () => {
  try {
    const cats = await api.get('/categories')
    categories.value = cats.data.data || cats.data
    if (isEdit.value) {
      await refreshData()
      startPolling()
    }
  } catch(e) {}
})

onUnmounted(() => stopPolling())

async function refreshData() {
  conflictMsg.value = ''
  try {
    const res = await api.get(`/products/${route.params.id}`)
    const p = res.data
    form.value = { 
      name: p.name, price: Number(p.price), stock: Number(p.stock), 
      category_id: p.category_id, hinh_anh: p.hinh_anh, 
      description: p.description, images: p.images || [],
      is_active: !!p.is_active,
      is_featured: !!p.is_featured
    }
    versionKey.value = p.updated_at
  } catch (e) { toast.error(i18n.t('common.error')) }
}

function startPolling() {
  pollingTimer = setInterval(async () => {
    if (!isEdit.value || saving.value || !versionKey.value) return
    try {
      const res = await api.get(`/admin/products/${route.params.id}/check-updated`)
      if (res.data.updated || res.data.deleted) {
        if (res.data.deleted) {
           conflictMsg.value = i18n.t('admin.product_deleted')
           stopPolling()
           return
        }

        const cachedTime = Math.floor(new Date(res.data.data.updated_at).getTime() / 1000)
        const localTime = Math.floor(new Date(versionKey.value).getTime() / 1000)

        if (cachedTime > localTime) {
          conflictMsg.value = i18n.t('admin.data_conflict')
          stopPolling()
        }
      }
    } catch {}
  }, 5000)
}

function stopPolling() {
  if (pollingTimer) clearInterval(pollingTimer)
}

async function handleFileUpload(e, mode) {
  const files = e.target.files
  if (!files.length) return

  if (mode === 'main') {
    const formData = new FormData()
    formData.append('file', files[0])
    uploadingMain.value = true
    try {
      const res = await api.post('/admin/upload', formData)
      form.value.hinh_anh = res.data.path
      toast.success(i18n.t('admin.image_upload_success'))
    } catch (err) { toast.error(i18n.t('common.error')) }
    finally { uploadingMain.value = false; e.target.value = '' }
  } else {
    if (!isEdit.value) {
      toast.warning(i18n.t('admin.create_first'))
      return
    }
    const formData = new FormData()
    for (let i = 0; i < files.length; i++) {
      formData.append('images[]', files[i])
    }
    uploadingMulti.value = true
    try {
      const res = await api.post(`/admin/products/${route.params.id}/images`, formData)
      form.value.images = [...form.value.images, ...res.data.images]
      toast.success(i18n.t('admin.image_upload_success'))
    } catch (err) { toast.error(i18n.t('common.error')) }
    finally { uploadingMulti.value = false; e.target.value = '' }
  }
}

async function removeImage(idx) {
  const imgId = form.value.images[idx]?.id
  if (imgId) {
    try {
      await api.delete(`/admin/products/${route.params.id}/images/${imgId}`)
      form.value.images.splice(idx, 1)
      toast.success(i18n.t('admin.image_deleted_success'))
    } catch(e) { toast.error(i18n.t('common.error')) }
  } else {
    form.value.images.splice(idx, 1)
  }
}

async function handleSave() {
  saving.value = true; errorMsg.value = ''; conflictMsg.value = ''; errors.value = {}
  try {
    const payload = { ...form.value }
    if (isEdit.value) {
      payload.updated_at = versionKey.value
      await api.put(`/admin/products/${route.params.id}`, payload)
      toast.success(i18n.t('admin.product_saved_success'))
      router.push('/admin/products')
    } else {
      await api.post('/admin/products', payload)
      toast.success(i18n.t('admin.product_saved_success'))
      router.push('/admin/products')
    }
  } catch (e) {
    const errorData = e.response?.data
    let msg = i18n.t('common.error')

    if (e.response?.status === 409) {
      conflictMsg.value = i18n.t('admin.data_conflict')
      msg = conflictMsg.value
    } else if (e.response?.status === 422) {
      errors.value = errorData.errors || errorData
      const firstError = Object.values(errors.value).flat()[0]
      if (firstError) msg = firstError
    } else {
      errorMsg.value = errorData?.message || msg
      msg = errorMsg.value
    }
    toast.error(msg)
  } finally { saving.value = false }
}
</script>

<style scoped>
.admin-page { padding: 20px; }
.admin-toolbar { margin-bottom: 20px; }
.admin-card { background: #fff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.form-group { margin-bottom: 4px; }
.form-label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #86868b; margin-bottom: 8px; }
.form-input { width: 100%; background: #f5f5f7; border: 1.5px solid transparent; border-radius: 12px; padding: 12px 16px; font-size: 14px; font-family: inherit; transition: 0.2s; outline: none; }
.form-input:focus { background: #fff; border-color: #0071e3; box-shadow: 0 0 0 4px rgba(0,113,227,0.1); }
.btn-primary { background: #1d1d1f; color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #000; transform: translateY(-1px); }
.btn-secondary { background: #f5f5f7; color: #1d1d1f; border: none; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.btn-secondary:hover { background: #e8e8ed; }
.text-danger { color: #d70018; }
.fw-bold { font-weight: 700; }
.small { font-size: 12px; }

/* Validation Styles */
.input-error { border-color: #f43f5e !important; background-color: #fff1f2 !important; }
.form-error-label { color: #e11d48; font-size: 12px; font-weight: 600; margin-top: 5px; animation: slideIn 0.2s ease-out; }

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .admin-page { padding: 12px; }
  .admin-card { padding: 16px !important; border-radius: 16px; }
  div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
  .btn-primary, .btn-secondary { padding: 10px 16px; font-size: 13px; }
}

/* Apple-style Checkbox */
.apple-checkbox {
  width: 18px; height: 18px; border-radius: 6px; appearance: none; background: #fff; border: 2.5px solid #d2d2d7; cursor: pointer; transition: 0.2s; position: relative;
}
.apple-checkbox:checked {
  background: #0071e3; border-color: #0071e3;
}
.apple-checkbox:checked::after {
  content: "✓"; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 800;
}
</style>
