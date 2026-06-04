<template>
  <div class="admin-page">
    <div class="admin-toolbar" style="justify-content: space-between;">
      <h1 class="font-outfit" style="font-size: 24px; font-weight: 800; color: #dc2626;">🗑️ {{ i18n.t('admin.trash_title') }} ({{ trashedProducts.length }})</h1>
      <router-link to="/admin/products" class="btn-secondary" style="text-decoration: none;">
        ← {{ i18n.t('admin.back_to_list') }}
      </router-link>
    </div>

    <!-- Red warning banner for conflict -->
    <div v-if="conflictMsg" class="trash-banner-conflict" style="background: #fef2f2; border: 1px solid #fca5a5; margin-bottom: 24px; border-radius: 16px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 32px; height: 32px; background: #ef4444; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold;">!</div>
        <div>
          <p class="fw-bold text-danger" style="margin: 0; font-size: 14px;">{{ conflictMsg }}</p>
        </div>
      </div>
      <button @click="doFetch(pagination?.current_page || 1)" class="btn-danger" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-weight: 700;">{{ i18n.t('common.refresh') }}</button>
    </div>

    <!-- Red warning banner for Trash area -->
    <div class="trash-banner">
      <div class="trash-banner-icon">⚠️</div>
      <div class="trash-banner-content">
        <h4 class="trash-banner-title">{{ i18n.t('admin.trash_warning_title') }}</h4>
        <p class="trash-banner-desc">{{ i18n.t('admin.trash_warning_desc') }}</p>
      </div>
    </div>

    <div class="admin-card premium-card">
      <div v-if="!trashedProducts || trashedProducts.length === 0" class="table-empty">
        <div class="empty-icon">✅</div>
        <p>{{ i18n.t('admin.trash_empty') }}</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 1%; white-space: nowrap; text-align: center;">{{ i18n.t('admin.product_image') }}</th>
              <th>{{ i18n.t('admin.product_name') }}</th>
              <th class="hide-mobile">{{ i18n.t('admin.deleted_at') }}</th>
              <th class="text-right" style="width: 1%; white-space: nowrap;">{{ i18n.t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in trashedProducts" :key="p.id">
              <td :data-label="i18n.t('admin.product_image') || 'IMAGE'">
                <div class="image-wrapper" style="display: flex; justify-content: center;">
                  <div class="product-thumb">
                    <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" @error="onImgError" />
                  </div>
                </div>
              </td>
              <td :data-label="i18n.t('admin.product_name') || 'NAME'">
                <div class="product-info-cell">
                  <div class="fw-bold">{{ p.name }}</div>
                  <div class="text-danger fw-bold" style="font-size: 13px;">{{ fmtPrice(p.price) }}</div>
                </div>
              </td>
              <td class="hide-mobile" :data-label="i18n.t('admin.deleted_at') || 'DELETED'">
                <span class="text-muted">{{ formatDate(p.deleted_at) }}</span>
              </td>
              <td :data-label="i18n.t('admin.actions') || 'ACTIONS'">
                <div class="action-row">
                  <button @click="restoreProduct(p)" class="btn-action info" :title="i18n.t('admin.restore')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                  </button>
                  <button @click="confirmForceDelete(p)" class="btn-action danger" :title="i18n.t('admin.force_delete')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="pagination-wrapper">
      <div class="pagination-apple-wrapper">
        <ul class="pagination-apple">
          <li v-if="pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(1)">«</button>
          </li>
          <li v-if="pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(pagination.current_page - 1)">‹</button>
          </li>
          <li v-for="page in pagination.last_page" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
            <button class="page-link" @click="goPage(page)">{{ page }}</button>
          </li>
          <li v-if="pagination.current_page < pagination.last_page" class="page-item">
            <button class="page-link" @click="goPage(pagination.current_page + 1)">›</button>
          </li>
          <li v-if="pagination.current_page < pagination.last_page" class="page-item">
            <button class="page-link" @click="goPage(pagination.last_page)">»</button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Force Delete Confirm -->
    <div v-if="forceDeleteProduct" class="modal-overlay" @click.self="forceDeleteProduct = null">
      <div class="modal-box" style="max-width: 340px; text-align: center;">
        <div class="modal-body" style="align-items: center; padding: 30px 20px;">
          <div style="font-size: 40px; margin-bottom: 10px;">💀</div>
          <h3 class="fw-bold" style="font-size: 18px; margin-bottom: 10px;">{{ i18n.t('admin.force_delete') }}</h3>
          <p class="text-muted" style="margin-bottom: 10px;">{{ i18n.t('admin.force_delete_desc_item', { name: forceDeleteProduct.name }) || i18n.t('admin.force_delete_desc') }}</p>
          <p class="text-danger fw-bold small" style="margin-bottom: 20px;">⚠️ {{ i18n.t('admin.force_delete_desc') }}</p>
          <div style="display: flex; gap: 10px; width: 100%;">
            <button @click="forceDeleteProduct = null" class="btn-secondary" style="flex: 1;">{{ i18n.t('common.cancel') }}</button>
            <button @click="doForceDelete" class="btn-danger" style="flex: 1; background: #ef4444; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">{{ i18n.t('admin.delete_now') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { productsApi } from '../../api'
import { useToast } from '../../composables/useToast'
import { useI18nStore } from '../../stores/i18n'
import { useUtils } from '../../composables/useUtils'
import { useProductStore } from '../../stores/product'

const toast = useToast()
const i18n = useI18nStore()
const { getImageUrl, fmtPrice } = useUtils()
const productStore = useProductStore()

const trashedProducts = computed(() => productStore.trashList)
const forceDeleteProduct = ref(null)
const pagination = ref(null)
const conflictMsg = ref('')

onMounted(() => doFetch(1))

async function doFetch(page = 1) {
  conflictMsg.value = ''
  try {
    const res = await productsApi.trash({ page })
    productStore.trashList = res.data.data || []

    pagination.value = res.data.meta || res.data
  } catch (e) {
    productStore.trashList = []
    toast.error(i18n.t('common.error'))
  }
}

function goPage(page) {
  doFetch(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function restoreProduct(product) {
  conflictMsg.value = ''
  try {
    await productStore.restoreProduct(product.id, product.updated_at)
    productStore.trashList = productStore.trashList.filter(p => p.id !== product.id)
    await productStore.fetchProducts()
    toast.success(`Sản phẩm ${product.name} đã được đưa trở lại danh sách kinh doanh`)
  } catch (e) {
    if (e.response?.status === 409) {
      conflictMsg.value = 'Dữ liệu đã thay đổi, vui lòng làm mới!'
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
    }
  }
}

function confirmForceDelete(product) { forceDeleteProduct.value = product }

async function doForceDelete() {
  conflictMsg.value = ''
  try {
    await productStore.forceDeleteProduct(forceDeleteProduct.value.id)
    productStore.trashList = productStore.trashList.filter(p => p.id !== forceDeleteProduct.value.id)
    toast.success(i18n.t('admin.force_delete_success'))
  } catch (e) {
    if (e.response?.status === 409) {
      conflictMsg.value = 'Dữ liệu đã thay đổi, vui lòng làm mới!'
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      toast.error(e.response?.data?.message || i18n.t('common.error'))
    }
  } finally {
    forceDeleteProduct.value = null
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  return `${day}/${month}/${year} ${hours}:${minutes}`
}

// [Đặng Văn Hà - 4.3.14] Xử lý hiển thị ảnh mặc định khi ảnh sản phẩm bị lỗi tải (Broken Image)
function onImgError(e) {
  e.target.src = 'https://placehold.co/400'
}
</script>

<style scoped>
.admin-page { padding: 24px; animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.admin-toolbar { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }

.premium-card { background: #fff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { background: #fafafa; padding: 16px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #86868b; border-bottom: 1px solid #f2f2f2; }
.admin-table td { padding: 16px; border-bottom: 1px solid #f2f2f2; vertical-align: middle; }
.admin-table tr:hover td { background: #fbfbfb; }

.product-thumb { width: 44px; height: 44px; background: #f5f5f7; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 4px; }
.product-thumb img { width: 100%; height: 100%; object-fit: contain; }

.action-row { display: flex; gap: 8px; justify-content: flex-end; flex-direction: row !important; align-items: center; }
.btn-action { width: 34px; height: 34px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
.btn-action.info { background: #f0f9ff; color: #0369a1; }
.btn-action.info:hover { background: #e0f2fe; transform: scale(1.05); }
.btn-action.danger { background: #fff1f2; color: #e11d48; }
.btn-action.danger:hover { background: #ffe4e6; transform: scale(1.05); }

.btn-secondary { background: #f5f5f7; color: #1d1d1f; border: none; padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.btn-secondary:hover { background: #e8e8ed; }

.table-empty { padding: 80px 20px; text-align: center; }
.empty-icon { font-size: 48px; margin-bottom: 16px; }

@media (max-width: 768px) {
  .admin-page { padding: 12px; }
  .hide-mobile { display: none; }
}

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; animation: modalFade 0.3s; }
@keyframes modalFade { from { opacity: 0; } to { opacity: 1; } }
.modal-box { background: white; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); width: 90%; animation: modalSlide 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes modalSlide { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

.trash-banner {
  background: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 16px;
  padding: 16px 20px;
  margin-bottom: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}
.trash-banner-icon {
  font-size: 24px;
  line-height: 1;
}
.trash-banner-content {
  text-align: left;
}
.trash-banner-title {
  color: #dc2626;
  font-weight: 800;
  margin: 0 0 4px 0;
  font-size: 15px;
}
.trash-banner-desc {
  color: #7f1d1d;
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
}
</style>
