<template>
  <div class="admin-page">
    <!-- Toolbar -->
    <!-- Premium Filter Bar -->
    <div class="admin-toolbar premium-bar">
      <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input v-model="search" @input="debounceSearch" type="text"
          :placeholder="i18n.t('admin.search_products')" class="premium-search" />
      </div>

      <div class="filter-group">
        <div class="custom-select-wrapper">
          <select v-model="categoryFilter" @change="doFetch(1)" class="premium-select">
            <option value="">✨ {{ i18n.t('admin.all_categories') }}</option>
            
            <template v-for="parent in brands" :key="parent.id">
              <option :value="parent.id" class="opt-parent">
                📂 {{ parent.name.toUpperCase() }} (TẤT CẢ)
              </option>
              <option v-for="child in (parent.children || [])" :key="child.id" :value="child.id" class="opt-child">
                &nbsp;&nbsp;&nbsp;└─ {{ child.name }}
              </option>
            </template>

            <template v-if="categories.find(c => c.id === 5)">
              <option :value="5" class="opt-parent">
                📂 {{ categories.find(c => c.id === 5).name.toUpperCase() }} (TẤT CẢ)
              </option>
              <option v-for="child in (categories.find(c => c.id === 5).children || [])" :key="child.id" :value="child.id" class="opt-child">
                &nbsp;&nbsp;&nbsp;└─ {{ child.name }}
              </option>
            </template>
          </select>
          <div class="select-arrow"></div>
        </div>

        <router-link to="/admin/products/create" class="btn-add-premium">
          <span class="plus-icon">+</span> {{ i18n.t('admin.add') }} {{ i18n.t('admin.stat_products').toLowerCase() }}
        </router-link>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-card">
      <div v-if="loading" class="table-empty"><div class="empty-icon">⏳</div><p>{{ i18n.t('common.loading') }}</p></div>
      <div v-else-if="!products.length" class="table-empty">
        <div class="empty-icon">📱</div><p>{{ i18n.t('product.not_found') }}</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>{{ i18n.t('admin.product_image') }}</th>
              <th>{{ i18n.t('admin.product_name') }}</th>
              <th class="hide-mobile">{{ i18n.t('admin.stat_brands') }}</th>
              <th class="text-right">{{ i18n.t('product.price') }} ({{ i18n.locale === 'vi' ? 'VNĐ' : 'USD' }})</th>
              <th class="text-center" style="width: 100px;">{{ i18n.t('product.stock') }}</th>
              <th class="text-right">{{ i18n.t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.id">
              <td class="text-muted">#{{ p.id }}</td>
              <td>
                <div style="width:44px;height:44px;background:#f5f5f7;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                  <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" style="width:100%;height:100%;object-fit:contain;padding:4px;" />
                </div>
              </td>
              <td>
                <div class="fw-bold" style="font-size:13px;">{{ p.name }}</div>
                <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                  <span class="text-muted" style="font-size:11px;">ID: {{ p.id }}</span>
                  <span v-if="!p.is_active" class="status-badge badge-danger" style="font-size: 9px; padding: 1px 6px;">HIDDEN</span>
                  <span v-if="p.is_featured" class="status-badge badge-warning" style="font-size: 9px; padding: 1px 6px; background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;">HOT 🔥</span>
                </div>
              </td>
              <td class="hide-mobile">
                <span v-if="p.category" style="background:#f5f5f7;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;">{{ p.category.name }}</span>
              </td>
              <td class="text-right fw-bold text-danger">{{ fmtPrice(p.price) }}</td>
              <td class="text-center">
                <input type="number" v-model.number="p.stock" @change="quickUpdateStock(p)" class="form-input text-center" style="width: 70px; padding: 4px; font-size: 13px; border-radius: 6px; display: inline-block; font-weight: 700; color: #1d1d1f;" min="0" />
              </td>
              <td>
                <div class="action-row">
                  <router-link :to="`/admin/products/${p.id}/edit`" class="btn-action info" :title="i18n.t('common.edit')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  </router-link>
                  <button @click="softDelete(p)" class="btn-action danger" :title="i18n.t('common.delete')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
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
            <button class="page-link" @click="doFetch(1)">«</button>
          </li>
          <li v-if="pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="doFetch(pagination.current_page - 1)">‹</button>
          </li>
          <li v-for="page in pagination.last_page" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
            <button class="page-link" @click="doFetch(page)">{{ page }}</button>
          </li>
          <li v-if="pagination.current_page < pagination.last_page" class="page-item">
            <button class="page-link" @click="doFetch(pagination.current_page + 1)">›</button>
          </li>
          <li v-if="pagination.current_page < pagination.last_page" class="page-item">
            <button class="page-link" @click="doFetch(pagination.last_page)">»</button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useUtils } from '../../composables/useUtils'
import { useI18nStore } from '../../stores/i18n'
import Swal from 'sweetalert2'

const { fmtPrice, getImageUrl } = useUtils()
const toast = useToast()
const i18n = useI18nStore()
const products = ref([])
const categories = ref([])
const brands = computed(() => categories.value.filter(c => c.parent_id === null && c.id !== 5))
const accessories = computed(() => categories.value.filter(c => c.parent_id === 5))
const search = ref('')
const categoryFilter = ref('')
const pagination = ref(null)
const loading = ref(false)
let searchTimeout = null

onMounted(async () => {
  doFetch(1)
  try {
    const cats = await api.get('/categories')
    categories.value = cats.data
  } catch (e) {
    console.error('Failed to fetch categories', e)
  }
})

async function doFetch(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/products', { params: { page, search: search.value || undefined, category_id: categoryFilter.value || undefined, show_all: 1 } })
    products.value = res.data.data
    pagination.value = res.data.meta || res.data
  } catch { toast.error(i18n.t('common.error')) } finally { loading.value = false }
}

function debounceSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => doFetch(1), 400)
}

async function quickUpdateStock(p) {
  try {
    const payload = {
      name: p.name,
      price: p.price,
      stock: p.stock,
      category_id: p.category?.id || p.category_id || null,
      description: p.description || '',
      hinh_anh: p.hinh_anh || '',
      updated_at: p.updated_at,
    }
    await api.put(`/admin/products/${p.id}`, payload)
    toast.success(i18n.t('admin.product_saved_success'))
  } catch(e) {
    console.error('[QuickUpdate Error]', e.response?.data)
    const errorData = e.response?.data
    let msg = i18n.t('common.error')
    
    if (errorData?.errors) {
      const firstError = Object.values(errorData.errors).flat()[0]
      if (firstError) msg = firstError
    } else if (errorData?.message) {
      msg = errorData.message
    }
    
    toast.error(msg)
    doFetch(pagination.value?.current_page || 1)
  }
}

async function softDelete(product) {
  const result = await Swal.fire({
    title: i18n.t('admin.trash_confirm', { name: product.name }),
    text: 'Sản phẩm sẽ được chuyển vào thùng rác.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Đưa vào thùng rác',
    cancelButtonText: 'Bỏ qua'
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/admin/products/${product.id}`, {
      data: { updated_at: product.updated_at }
    })
    products.value = products.value.filter(p => p.id !== product.id)
    toast.success(i18n.t('admin.moved_to_trash'))
  } catch(e) { 
    Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: e.response?.data?.message || i18n.t('common.error'),
      confirmButtonColor: '#e11d48'
    })
  }
}
</script>

<style scoped>
.admin-page { padding: 24px; animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ===== PREMIUM BAR ===== */
.premium-bar { 
  display: flex; align-items: center; justify-content: space-between; gap: 20px;
  background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px);
  padding: 12px 16px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 24px;
}

.search-wrapper { position: relative; flex: 1; max-width: 400px; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; opacity: 0.5; }
.premium-search {
  width: 100%; background: #f5f5f7; border: 1.5px solid transparent; border-radius: 12px;
  padding: 10px 16px 10px 40px; font-size: 14px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: #1d1d1f; outline: none;
}
.premium-search:focus { background: #fff; border-color: #0071e3; box-shadow: 0 0 0 4px rgba(0,113,227,0.1); }

.filter-group { display: flex; align-items: center; gap: 12px; }

.custom-select-wrapper { position: relative; min-width: 220px; }
.premium-select {
  width: 100%; appearance: none; background: #fff; border: 1.5px solid #e5e5ea;
  border-radius: 12px; padding: 10px 40px 10px 16px; font-size: 14px; font-weight: 600;
  color: #1d1d1f; cursor: pointer; transition: 0.2s; outline: none;
}
.premium-select:hover { border-color: #0071e3; background: #f5faff; }
.opt-parent { font-weight: 800; background: #f8fafc; color: #000; padding: 8px; }
.opt-child { font-weight: 500; color: #48484a; }

.select-arrow {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  width: 10px; height: 10px; pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2386868b' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-size: contain; background-repeat: no-repeat;
}

.btn-add-premium {
  background: #1d1d1f; color: #fff; border: none; padding: 10px 20px;
  border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer;
  transition: 0.3s; display: flex; align-items: center; gap: 8px; text-decoration: none;
}
.btn-add-premium:hover { background: #000; transform: scale(1.02); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.plus-icon { font-size: 18px; line-height: 1; }

/* Table Styling */
.admin-card { background: #fff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); overflow: hidden; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { background: #fafafa; padding: 16px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #86868b; border-bottom: 1px solid #f2f2f2; }
.admin-table td { padding: 16px; border-bottom: 1px solid #f2f2f2; vertical-align: middle; }
.admin-table tr:hover td { background: #fbfbfb; }

.action-row { display: flex; gap: 8px; justify-content: flex-end; }
.btn-action { width: 34px; height: 34px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
.btn-action.info { background: #f5f5f7; color: #0071e3; }
.btn-action.info:hover { background: #e8f3ff; transform: scale(1.05); }
.btn-action.danger { background: #fff1f2; color: #e11d48; }
.btn-action.danger:hover { background: #ffe4e6; transform: scale(1.05); }

.table-empty { padding: 60px; text-align: center; color: #86868b; }
.empty-icon { font-size: 40px; margin-bottom: 10px; opacity: 0.5; }

/* Responsive */
@media (max-width: 1024px) {
  .premium-bar { flex-direction: column; align-items: stretch; }
  .search-wrapper { max-width: none; }
}

@media (max-width: 768px) {
  .admin-page { padding: 12px; }
  .hide-mobile { display: none; }
  .filter-group { flex-direction: column; align-items: stretch; }
}
</style>
