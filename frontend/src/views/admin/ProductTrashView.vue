<template>
  <div class="admin-page">
    <div class="admin-toolbar" style="justify-content: space-between;">
      <h1 class="font-outfit" style="font-size: 24px; font-weight: 800;">🗑️ {{ i18n.t('admin.trash_title') }}</h1>
      <router-link to="/admin/products" class="btn-secondary" style="text-decoration: none;">
        ← {{ i18n.t('admin.back_to_list') }}
      </router-link>
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
              <th>{{ i18n.t('admin.product_image') }}</th>
              <th>{{ i18n.t('admin.product_name') }}</th>
              <th class="hide-mobile">{{ i18n.t('admin.deleted_at') }}</th>
              <th class="text-right">{{ i18n.t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in trashedProducts" :key="p.id">
              <td :data-label="i18n.t('admin.product_image') || 'IMAGE'">
                <div class="product-thumb" style="margin-left: auto;">
                  <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" />
                </div>
              </td>
              <td :data-label="i18n.t('admin.product_name') || 'NAME'">
                <div class="fw-bold">{{ p.name }}</div>
                <div class="text-danger fw-bold" style="font-size: 13px;">{{ fmtPrice(p.price) }}</div>
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
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useI18nStore } from '../../stores/i18n'
import { useUtils } from '../../composables/useUtils'

const toast = useToast()
const i18n = useI18nStore()
const { getImageUrl, fmtPrice } = useUtils()
const trashedProducts = ref([])
const forceDeleteProduct = ref(null)
const pagination = ref(null)

onMounted(() => doFetch(1))

async function doFetch(page = 1) {
  try {
    const res = await api.get('/admin/products/trash', { params: { page } })
    trashedProducts.value = res.data.data || []
    pagination.value = res.data.meta || res.data
  } catch (e) {
    trashedProducts.value = []
    toast.error(i18n.t('common.error'))
  }
}

function goPage(page) {
  doFetch(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function restoreProduct(product) {
  try {
    await api.post(`/admin/products/${product.id}/restore`)
    trashedProducts.value = trashedProducts.value.filter(p => p.id !== product.id)
    toast.success(i18n.t('admin.restore_success'))
  } catch (e) {
    toast.error(e.response?.data?.message || i18n.t('common.error'))
  }
}

function confirmForceDelete(product) { forceDeleteProduct.value = product }

async function doForceDelete() {
  try {
    await api.delete(`/admin/products/${forceDeleteProduct.value.id}/force-delete`)
    trashedProducts.value = trashedProducts.value.filter(p => p.id !== forceDeleteProduct.value.id)
    toast.success(i18n.t('admin.force_delete_success'))
  } catch (e) {
    toast.error(e.response?.data?.message || i18n.t('common.error'))
  } finally {
    forceDeleteProduct.value = null
  }
}

function formatDate(date) { 
  if (!date) return ''
  return new Date(date).toLocaleString(i18n.locale === 'vi' ? 'vi-VN' : 'en-US') 
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
</style>
