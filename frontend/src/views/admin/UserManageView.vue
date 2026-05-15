<template>
  <div class="admin-page">
    <!-- Stats summary -->
    <div class="stats-row">
      <div class="mini-stat">
        <span class="mini-label">{{ i18n.t('admin.total_customers') }}</span>
        <span class="mini-val">{{ pagination.total || users.length }}</span>
      </div>
      <div class="mini-stat warning">
        <span class="mini-label">{{ i18n.t('admin.locked_count') }}</span>
        <span class="mini-val">{{ users.filter(u => !u.is_active).length }}</span>
      </div>
    </div>

    <!-- Search & Add -->
    <div class="toolbar">
      <input v-model="search" @keyup.enter="fetchUsers()" type="text"
        :placeholder="i18n.t('admin.search_users_placeholder')" class="admin-search" />
      <button @click="openCreateModal" class="btn-primary">+ {{ i18n.t('admin.add_customer') }}</button>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div v-if="loading" class="table-empty"><div>⏳</div><p>{{ i18n.t('common.loading') }}</p></div>
      <div v-else-if="!users.length" class="table-empty">
        <div>👥</div><p>{{ i18n.t('common.no_data') }}</p>
      </div>
      <div v-else class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>{{ i18n.t('admin.customer') }}</th>
              <th>{{ i18n.t('common.email') }}</th>
              <th class="text-center">{{ i18n.t('admin.user_orders') }}</th>
              <th>{{ i18n.t('admin.spending') }}</th>
              <th>{{ i18n.t('admin.status') }}</th>
              <th class="text-center">{{ i18n.t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td :data-label="i18n.t('admin.customer')">
                <div class="user-cell">
                  <div class="avatar" :class="{ locked: u.is_locked || !u.is_active }">
                    {{ (u.name || 'K').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="fw-bold">{{ u.name }}</div>
                    <div v-if="u.role === 'admin'" class="admin-tag">Admin</div>
                  </div>
                </div>
              </td>
              <td class="text-muted" :data-label="i18n.t('common.email')">{{ u.email }}</td>
              <td class="text-center fw-bold" :data-label="i18n.t('admin.user_orders')">{{ u.orders_count || 0 }}</td>
              <td :data-label="i18n.t('admin.spending')"><span class="text-danger fw-bold">{{ fmtPrice(u.total_spent || 0) }}</span></td>
              <td :data-label="i18n.t('admin.status')">
                <span class="status-badge" :class="(u.is_locked || !u.is_active) ? 'badge-danger' : 'badge-success'">
                  {{ !u.is_active ? i18n.t('admin.locked') : (u.is_locked ? 'Khóa tạm thời' : i18n.t('admin.active')) }}
                </span>
              </td>
              <td :data-label="i18n.t('admin.actions')">
                <div class="action-row">
                  <!-- Unlock nếu đang bị khóa (Bất kể khóa cứng hay khóa tạm thời) -> Hiển thị ổ khóa đóng màu vàng -->
                  <button v-if="(!u.is_active || u.is_locked) && u.role !== 'admin'" @click="unlockUser(u.id)" class="btn-action warning" :title="i18n.t('admin.unlock')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </button>
                  <!-- Khóa nếu đang hoạt động bình thường -> Hiển thị ổ khóa mở màu xanh -->
                  <button v-else-if="u.is_active && !u.is_locked && u.role !== 'admin'" @click="lockUser(u.id)" class="btn-action success" :title="i18n.t('admin.lock') || 'Khóa'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                  </button>
                  <!-- Chỉnh sửa -->
                  <button @click="openEditModal(u)" class="btn-action info" :title="i18n.t('common.edit')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  </button>
                  <!-- Xóa (không xóa Admin) -->
                  <button v-if="u.role !== 'admin'" @click="deleteUser(u.id)" class="btn-action danger" :title="i18n.t('common.delete')">
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

    <!-- Modal Tạo/Sửa -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3>{{ editUser ? i18n.t('admin.edit_customer') : i18n.t('admin.add_customer') }}</h3>
            <button @click="showModal = false" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>{{ i18n.t('common.name') }}</label>
              <input v-model="form.name" type="text" class="form-input" :placeholder="i18n.t('common.name') + '...'" :class="{'input-error': errors?.name}" @input="errors && (errors.name = null)" />
              <p v-if="errors?.name" class="form-error-label">{{ errors.name[0] }}</p>
            </div>
            <div class="form-group">
              <label>{{ i18n.t('common.email') }}</label>
              <input v-model="form.email" type="email" class="form-input" placeholder="email@example.com" :class="{'input-error': errors?.email}" @input="errors && (errors.email = null)" />
              <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
            </div>
            <div class="form-group">
              <label>{{ i18n.t('common.password') }} <span v-if="editUser" class="text-muted" style="font-weight: normal; font-size: 11px;">(Để trống nếu không đổi)</span></label>
              <input v-model="form.password" type="password" class="form-input" :placeholder="i18n.t('common.password_min_8')" :class="{'input-error': errors?.password}" @input="errors && (errors.password = null)" />
              <p v-if="errors?.password" class="form-error-label">{{ errors.password[0] }}</p>
            </div>
            <div class="form-group">
              <label>{{ i18n.t('admin.user_role') }}</label>
              <select v-model="form.role" class="form-input" :class="{'input-error': errors?.role}" @change="errors && (errors.role = null)">
                <option value="user">{{ i18n.t('admin.customer') }}</option>
                <option value="admin">Admin</option>
              </select>
              <p v-if="errors?.role" class="form-error-label">{{ errors.role[0] }}</p>
            </div>
            <!-- Thêm SĐT và Địa chỉ -->
            <div class="form-row-2">
              <div class="form-group">
                <label>{{ i18n.t('auth.phone') }}</label>
                <input v-model="form.phone" type="text" class="form-input" placeholder="0901234..." :class="{'input-error': errors?.phone}" @input="errors && (errors.phone = null)" />
                <p v-if="errors?.phone" class="form-error-label">{{ errors.phone[0] }}</p>
              </div>
              <div class="form-group">
                <label>{{ i18n.t('auth.address') }}</label>
                <input v-model="form.address" type="text" class="form-input" :placeholder="i18n.t('auth.address_placeholder')" :class="{'input-error': errors?.address}" @input="errors && (errors.address = null)" />
                <p v-if="errors?.address" class="form-error-label">{{ errors.address[0] }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="showModal = false" class="btn-secondary">{{ i18n.t('common.cancel') }}</button>
            <button @click="saveUser" class="btn-primary" :disabled="saving">
              {{ saving ? i18n.t('admin.saving') : (editUser ? i18n.t('admin.save_changes') : i18n.t('admin.create_new')) }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useUtils } from '../../composables/useUtils'
import { useToast } from '../../composables/useToast'
import { useI18nStore } from '../../stores/i18n'
import Swal from 'sweetalert2'

const { fmtPrice } = useUtils()
const toast = useToast()
const i18n = useI18nStore()
const users = ref([])
const loading = ref(false)
const search = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const showModal = ref(false)
const editUser = ref(null)
const saving = ref(false)
const errors = ref({})
const form = ref({ name: '', email: '', password: '', role: 'user', phone: '', address: '' })

onMounted(() => fetchUsers())

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/admin/users', { params: { page, search: search.value || undefined } })
    users.value = res.data.data
    pagination.value = res.data.meta || { 
      current_page: res.data.current_page || 1, 
      last_page: res.data.last_page || 1, 
      total: res.data.total || res.data.data?.length || 0 
    }
  } catch { toast.error(i18n.t('common.error')) } finally { loading.value = false }
}

async function unlockUser(id) {
  const result = await Swal.fire({
    title: i18n.t('admin.unlock_confirm'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.t('admin.unlock'),
    cancelButtonText: i18n.t('common.cancel')
  })

  if (!result.isConfirmed) return

  try {
    await api.post(`/admin/users/${id}/unlock`)
    toast.success(i18n.t('admin.user_unlocked_success'))
    fetchUsers()
  } catch { toast.error(i18n.t('common.error')) }
}

async function lockUser(id) {
  const result = await Swal.fire({
    title: i18n.t('admin.lock_confirm'),
    text: 'Tài khoản này sẽ không thể đăng nhập.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Khóa ngay',
    cancelButtonText: i18n.t('common.cancel')
  })

  if (!result.isConfirmed) return

  try {
    await api.post(`/admin/users/${id}/lock`)
    toast.success(i18n.t('admin.user_locked_success'))
    fetchUsers()
  } catch { toast.error(i18n.t('common.error')) }
}

async function deleteUser(id) {
  const result = await Swal.fire({
    title: i18n.t('admin.delete_user_confirm'),
    text: i18n.t('admin.delete_user_text'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: i18n.t('common.delete'),
    cancelButtonText: i18n.t('common.cancel')
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/admin/users/${id}`)
    toast.success(i18n.t('admin.user_deleted_success'))
    fetchUsers()
  } catch { toast.error(i18n.t('common.error')) }
}

function openCreateModal() { 
  editUser.value = null; 
  errors.value = {}
  form.value = { name: '', email: '', password: '', role: 'user', phone: '', address: '' }; 
  showModal.value = true 
}

function openEditModal(u) { 
  editUser.value = u; 
  errors.value = {}
  form.value = { name: u.name, email: u.email, password: '', role: u.role, phone: u.phone || '', address: u.address || '' }; 
  showModal.value = true 
}

async function saveUser() {
  errors.value = {}
  
  // Client-side validation: Chặn ngay nếu các trường bắt buộc bị trống
  let hasError = false
  if (!form.value.name?.trim()) {
    errors.value.name = [i18n.t('auth.name_error') || 'Vui lòng nhập họ tên']
    hasError = true
  }
  if (!form.value.email?.trim()) {
    errors.value.email = [i18n.t('auth.email_error') || 'Vui lòng nhập email']
    hasError = true
  }
  if (!editUser.value && !form.value.password?.trim()) {
    errors.value.password = [i18n.t('auth.password_error') || 'Vui lòng nhập mật khẩu']
    hasError = true
  }

  if (hasError) return

  saving.value = true
  try {
    if (editUser.value) {
      await api.put(`/admin/users/${editUser.value.id}`, form.value)
      toast.success(i18n.t('admin.user_saved_success'))
    } else {
      await api.post('/admin/users', form.value)
      toast.success(i18n.t('admin.user_saved_success'))
    }
    showModal.value = false
    fetchUsers()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || e.response.data
    } else {
      const msg = e.response?.data?.message || i18n.t('common.error')
      // Fallback: Nếu lỗi SQL hoặc lỗi hệ thống, đẩy vào nhãn đỏ cho Admin đọc thay vì messbox
      if (msg.toLowerCase().includes('email')) {
        errors.value.email = [msg]
      } else if (msg.toLowerCase().includes('name') || msg.toLowerCase().includes('tên')) {
        errors.value.name = [msg]
      } else {
        errors.value.name = [msg]
      }
    }
  } finally { saving.value = false }
}

function goPage(p) { fetchUsers(p) }
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 20px; }
.stats-row { display: flex; gap: 12px; }
.mini-stat { background: #fff; border: 1px solid rgba(0,0,0,0.05); border-radius: 14px; padding: 14px 20px; display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
.mini-stat.warning { background: #fef2f2; border-color: rgba(239,68,68,0.1); }
.mini-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #86868b; }
.mini-val { font-size: 22px; font-weight: 800; color: #1d1d1f; }
.mini-stat.warning .mini-val { color: #dc2626; }
.toolbar { display: flex; gap: 12px; align-items: center; }
.admin-search { flex: 1; max-width: 400px; background: #fff; border: 1.5px solid #d2d2d7; border-radius: 12px; padding: 9px 16px; font-size: 13px; font-family: inherit; outline: none; transition: 0.2s; }
.admin-search:focus { border-color: #0071e3; box-shadow: 0 0 0 3px rgba(0,113,227,0.1); }
.btn-primary { background: #1d1d1f; color: #fff; border: none; border-radius: 12px; padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: 0.2s; white-space: nowrap; }
.btn-primary:hover { background: #3a3a3c; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 12px; padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: 0.2s; }
.btn-secondary:hover { background: #e5e5ea; }
.table-card { background: #fff; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
.table-empty { padding: 60px; text-align: center; color: #86868b; font-size: 14px; }
.table-empty div { font-size: 2.5rem; margin-bottom: 12px; }
.table-responsive { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; min-width: 720px; }
.admin-table thead tr { background: #f9f9f9; }
.admin-table th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #86868b; text-align: left; }
.admin-table td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid #f5f5f7; vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: #fafafa; }
.user-cell { display: flex; align-items: center; gap: 10px; }
.avatar { width: 36px; height: 36px; border-radius: 50%; background: #f0f4ff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: #3b82f6; flex-shrink: 0; }
.avatar.locked { background: #fef2f2; color: #dc2626; }
.fw-bold { font-weight: 700; }
.text-muted { color: #86868b; font-size: 12px; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-danger { color: #d70018; }
.admin-tag { display: inline-block; background: #dbeafe; color: #1e40af; font-size: 9px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
.status-badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
.badge-success { background: #dcfce7; color: #166534; }
.badge-danger { background: #fef2f2; color: #991b1b; }
.action-row { display: flex; align-items: center; gap: 6px; justify-content: center; }
.btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-action.success { background: #dcfce7; color: #166534; }
.btn-action.success:hover { background: #16a34a; color: #fff; }
.btn-action.warning { background: #fef9c3; color: #854f0b; }
.btn-action.warning:hover { background: #d97706; color: #fff; }
.btn-action.info { background: #dbeafe; color: #1e40af; }
.btn-action.info:hover { background: #3b82f6; color: #fff; }
.btn-action.danger { background: #fef2f2; color: #dc2626; }
.btn-action.danger:hover { background: #dc2626; color: #fff; }
/* Responsive Adjustments */
@media (max-width: 768px) {
  .stats-row { flex-wrap: wrap; }
  .mini-stat { flex: 1; min-width: calc(50% - 6px); }
  .toolbar { flex-direction: column; align-items: stretch; }
  .admin-search { max-width: none; }
  .table-card { border-radius: 14px; }
  .admin-table th, .admin-table td { padding: 10px; font-size: 12px; }
  .avatar { width: 30px; height: 30px; font-size: 12px; }
  .modal-box { border-radius: 16px; }
}

@media (max-width: 480px) {
  .mini-stat { min-width: 100%; }
  .action-row { gap: 4px; }
  .btn-action { width: 28px; height: 28px; }
  .status-badge { padding: 4px 8px; font-size: 10px; }
}

/* Validation Styles */
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 700; color: #1d1d1f; margin-bottom: 6px; }
.form-input { width: 100%; background: #f5f5f7; border: 1px solid #d2d2d7; border-radius: 12px; padding: 10px 14px; font-size: 14px; font-family: inherit; outline: none; transition: 0.2s; }
.form-input:focus { background: #fff; border-color: #0071e3; box-shadow: 0 0 0 4px rgba(0,113,227,0.1); }
.form-input.border-rose-400 { border-color: #f43f5e !important; background-color: #fff1f2; }
.form-input.ring-2 { box-shadow: 0 0 0 2px #fecdd3; }
.form-error-label { color: #e11d48; font-size: 12px; font-weight: 600; margin-top: 5px; animation: slideIn 0.2s ease-out; }

.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 480px) { .form-row-2 { grid-template-columns: 1fr; } }

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>