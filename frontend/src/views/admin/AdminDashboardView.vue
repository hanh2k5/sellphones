<template>
  <div class="dashboard-view animate-fade-in">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
      <!-- Total Revenue -->
      <div class="stat-card glass-card group">
        <div class="stat-icon bg-blue-100 text-blue-600 group-hover:scale-110 transition-transform">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="stat-info">
          <p class="stat-label">{{ i18n.t('admin.total_revenue') }}</p>
          <h3 class="stat-value text-blue-600">{{ fmtPrice(stats.totalRevenue) }}</h3>
        </div>
      </div>

      <!-- Total Orders -->
      <div class="stat-card glass-card group">
        <div class="stat-icon bg-emerald-100 text-emerald-600 group-hover:scale-110 transition-transform">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="stat-info">
          <p class="stat-label">{{ i18n.t('admin.stats_orders') }}</p>
          <h3 class="stat-value text-emerald-600">{{ stats.totalOrders }}</h3>
        </div>
      </div>

      <!-- Total Users -->
      <div class="stat-card glass-card group">
        <div class="stat-icon bg-indigo-100 text-indigo-600 group-hover:scale-110 transition-transform">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><circle cx="19" cy="11" r="2"/></svg>
        </div>
        <div class="stat-info">
          <p class="stat-label">{{ i18n.t('admin.stats_users') }}</p>
          <h3 class="stat-value text-indigo-600">{{ stats.totalUsers }}</h3>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="stat-card glass-card group" :class="{ 'alert-glow': stats.lowStockCount > 0 }">
        <div class="stat-icon bg-rose-100 text-rose-600 group-hover:scale-110 transition-transform">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
          <p class="stat-label">{{ i18n.t('admin.stats_low_stock') }}</p>
          <h3 class="stat-value text-rose-600">{{ stats.lowStockCount }}</h3>
        </div>
      </div>
    </div>

    <!-- Pending Orders Quick Link -->
    <div v-if="stats.pendingOrders > 0" class="pending-banner">
      <div class="pending-content">
        <span class="pending-icon">⏳</span>
        <div>
          <p class="pending-title">{{ stats.pendingOrders }} đơn hàng đang chờ duyệt</p>
          <p class="pending-sub">Nhấn vào đây để xử lý ngay</p>
        </div>
      </div>
      <router-link to="/admin/orders" class="pending-btn">
        Duyệt đơn →
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '../../api'
import { useUtils } from '../../composables/useUtils'
import { useI18nStore } from '../../stores/i18n'
import { useToast } from '../../composables/useToast'

const { fmtPrice } = useUtils()
const i18n = useI18nStore()
const toast = useToast()
const stats = ref({
  totalRevenue: 0,
  totalOrders: 0,
  totalUsers: 0,
  lowStockCount: 0,
  pendingOrders: 0
})

onMounted(async () => {
  try {
    const res = await adminApi.dashboard()
    const data = res.data
    stats.value = {
      totalRevenue:  data.stats?.totalRevenue  ?? data.totalRevenue  ?? 0,
      totalOrders:   data.stats?.totalOrders   ?? data.totalOrders   ?? 0,
      totalUsers:    data.stats?.totalUsers    ?? data.totalUsers    ?? 0,
      lowStockCount: data.stats?.lowStockCount ?? data.lowStockCount ?? 0,
      pendingOrders: data.stats?.pendingOrders ?? data.pendingOrders ?? 0,
    }
  } catch (e) {
    toast.error(e.response?.data?.message || i18n.t('common.error'))
  }
})
</script>

<style scoped>
.dashboard-view { padding-bottom: 2rem; }

.glass-card {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.06);
  border-radius: 1.5rem;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.02);
  transition: all 0.3s ease;
}
.glass-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.06); }

.stat-card { display: flex; align-items: center; gap: 1rem; }
.stat-icon { width: 54px; height: 54px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #86868b; margin-bottom: 2px; }
.stat-value { font-size: 1.5rem; font-weight: 700; margin: 0; letter-spacing: -0.01em; }
.stat-info { min-width: 0; }

.alert-glow { border-color: rgba(244,63,94,0.2); box-shadow: 0 0 20px rgba(244,63,94,0.05); }

/* Pending Banner */
.pending-banner {
  background: linear-gradient(135deg, #fff7ed, #fff);
  border: 1.5px solid #fed7aa;
  border-radius: 1.5rem;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  animation: slideIn 0.4s ease-out;
}
.pending-content { display: flex; align-items: center; gap: 1rem; }
.pending-icon { font-size: 2rem; animation: pulse 2s infinite; }
.pending-title { font-weight: 700; color: #9a3412; font-size: 14px; margin: 0 0 2px; }
.pending-sub { font-size: 12px; color: #c2410c; margin: 0; }
.pending-btn {
  background: #ea580c;
  color: #fff;
  text-decoration: none;
  padding: 8px 20px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
  transition: 0.2s;
}
.pending-btn:hover { background: #c2410c; transform: translateY(-1px); }

.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }

@media (max-width: 640px) {
  .stat-value { font-size: 1.25rem; }
  .pending-banner { flex-direction: column; align-items: flex-start; }
}
</style>
