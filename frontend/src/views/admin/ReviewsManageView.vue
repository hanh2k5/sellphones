<template>
  <div class="admin-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-info">
        <span class="count-badge">{{ i18n.t('admin.reviews_count', { count: pagination.total || reviews.length }) }}</span>
      </div>
      <div class="filter-row">
        <button v-for="s in [0,1,2,3,4,5]" :key="s"
          @click="filterRating = s; fetchReviews()"
          class="filter-tab" :class="{ active: filterRating === s }">
          {{ s === 0 ? i18n.t('admin.rating_all') : s + '★' }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div v-if="loading" class="table-empty"><div>⏳</div><p>{{ i18n.t('common.loading') }}</p></div>
      <div v-else-if="!reviews.length" class="table-empty">
        <div>💬</div><p>{{ i18n.t('admin.no_reviews') }}</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ i18n.t('admin.product_name') }}</th>
              <th>{{ i18n.t('admin.customer') }}</th>
              <th>{{ i18n.t('admin.reviews') }}</th>
              <th>{{ i18n.t('admin.comment') }}</th>
              <th>{{ i18n.t('admin.status') }}</th>
              <th>{{ i18n.t('admin.order_date') }}</th>
              <th class="text-right">{{ i18n.t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reviews" :key="r.id">
              <td>
                <div class="fw-bold small">{{ r.product?.name || `#SP-${r.product_id}` }}</div>
              </td>
              <td>
                <div class="user-cell">
                  <div class="avatar">{{ (r.user?.name || 'K').charAt(0).toUpperCase() }}</div>
                  <span>{{ r.user?.name || i18n.t('admin.anonymous') }}</span>
                </div>
              </td>
              <td>
                <div class="stars">
                  <span v-for="i in 5" :key="i" :class="i <= r.rating ? 'star-filled' : 'star-empty'">★</span>
                  <span class="rating-num">{{ r.rating }}/5</span>
                </div>
              </td>
              <td>
                <p class="review-comment">{{ r.comment || i18n.t('admin.no_comment') }}</p>
              </td>
              <td>
                <span class="status-badge" :class="r.status === 'approved' ? 'badge-success' : 'badge-warning'">
                  {{ reviewStatusLabel(r.status) }}
                </span>
              </td>
              <td class="text-muted">{{ formatDate(r.created_at) }}</td>
              <td>
                <div class="action-row">
                  <button v-if="r.status !== 'approved'" @click="moderate(r, 'approved')" class="btn-action success" title="Duyệt">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  <button v-if="r.status !== 'hidden'" @click="moderate(r, 'hidden')" class="btn-action warning" title="Ẩn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  </button>
                  <button @click="deleteReview(r.id)" class="btn-action danger" :title="i18n.t('common.delete')">
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
    <div v-if="pagination.last_page > 1" class="pagination-wrapper">
      <div class="pagination-apple-wrapper">
        <ul class="pagination-apple">
          <li v-if="pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(1)">«</button>
          </li>
          <li v-if="pagination.current_page > 1" class="page-item">
            <button class="page-link" @click="goPage(pagination.current_page - 1)">‹</button>
          </li>
          <li v-for="p in pagination.last_page" :key="p" class="page-item" :class="{ active: p === pagination.current_page }">
            <button class="page-link" @click="goPage(p)">{{ p }}</button>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useUtils } from '../../composables/useUtils'
import { useToast } from '../../composables/useToast'
import { useI18nStore } from '../../stores/i18n'
import Swal from 'sweetalert2'

const { fmtDate } = useUtils()
const toast = useToast()
const i18n = useI18nStore()
const reviews = ref([])
const loading = ref(false)
const filterRating = ref(0)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

onMounted(fetchReviews)

async function fetchReviews(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filterRating.value > 0) params.rating = filterRating.value
    const res = await api.get('/admin/reviews', { params })
    reviews.value = res.data.data || []
    if (res.data.meta) pagination.value = res.data.meta
    else if (res.data.last_page) pagination.value = res.data
  } catch (e) {
    console.warn('Backend endpoint /admin/reviews error or missing')
    reviews.value = []
  } finally { loading.value = false }
}

async function moderate(r, status) {
  try {
    const res = await api.put(`/admin/reviews/${r.id}/moderate`, { status })
    // Cập nhật trực tiếp vào object để Vue reactivity nhận biết ngay lập tức
    r.status = res.data.review.status
    toast.success(i18n.t('admin.review_moderated_success'))
  } catch (e) {
    toast.error(e.response?.data?.message || i18n.t('common.error'))
  }
}

async function deleteReview(id) {
  const result = await Swal.fire({
    title: i18n.t('admin.delete_review_confirm'),
    text: i18n.t('admin.confirm_action'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.t('common.delete'),
    cancelButtonText: i18n.t('common.cancel')
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/admin/reviews/${id}`)
    toast.success(i18n.t('admin.review_deleted'))
    fetchReviews()
  } catch (e) { 
    toast.error(e.response?.data?.message || i18n.t('common.error')) 
  }
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleString(i18n.locale === 'vi' ? 'vi-VN' : 'en-US')
}

function reviewStatusLabel(status) {
  if (status === 'approved') return i18n.t('admin.status_approved')
  if (status === 'hidden') return i18n.t('admin.status_hidden')
  return status || '-'
}

function goPage(p) { fetchReviews(p) }
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; flex-direction: column; gap: 10px; }
.header-info { display: flex; align-items: center; gap: 12px; }
.count-badge { background: #1d1d1f; color: #fff; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.filter-row { display: flex; flex-wrap: wrap; gap: 6px; }
.filter-tab { padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; border: 1.5px solid #d2d2d7; background: #fff; color: #555; cursor: pointer; transition: 0.2s; }
.filter-tab:hover { border-color: #f59e0b; color: #f59e0b; }
.filter-tab.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.table-card { background: #fff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
.table-empty { padding: 60px; text-align: center; color: #86868b; font-size: 14px; }
.table-empty div { font-size: 2.5rem; margin-bottom: 12px; }
.table-responsive { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.admin-table thead tr { background: #f9f9f9; }
.admin-table th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #86868b; text-align: left; }
.admin-table td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid #f5f5f7; vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: #fafafa; }
.fw-bold { font-weight: 700; }
.small { font-size: 12px; }
.text-muted { color: #86868b; font-size: 12px; }
.user-cell { display: flex; align-items: center; gap: 8px; }
.avatar { width: 30px; height: 30px; border-radius: 50%; background: #f5f5f7; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #555; flex-shrink: 0; }
.stars { display: flex; align-items: center; gap: 2px; }
.star-filled { color: #f59e0b; font-size: 14px; }
.star-empty { color: #e5e7eb; font-size: 14px; }
.rating-num { font-size: 11px; font-weight: 700; color: #86868b; margin-left: 4px; }
.review-comment { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #555; margin: 0; font-size: 13px; }
.action-row { display: flex; justify-content: flex-end; }
.btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-action.danger { background: #fef2f2; color: #dc2626; }
.btn-action.danger:hover { background: #dc2626; color: #fff; }
.text-right { text-align: right; }
.pagination-wrapper { display: flex; justify-content: center; }
.pagination-apple-wrapper { background: #fff; border-radius: 50px; padding: 6px 14px; display: inline-block; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.pagination-apple { display: flex; align-items: center; gap: 2px; padding: 0; list-style: none; margin: 0; }
.page-link { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1d1d1f; font-weight: 600; font-size: 13px; background: transparent; border: none; cursor: pointer; font-family: inherit; transition: 0.2s; }
.page-link:hover { background: #f5f5f7; }
.page-item.active .page-link { background: #1d1d1f; color: #fff; }
</style>
