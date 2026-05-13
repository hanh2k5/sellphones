<template>
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <AdminSidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Overlay (mobile) -->
    <div class="sidebar-overlay" :class="{ show: sidebarOpen }" @click="sidebarOpen = false"></div>

    <!-- Main content -->
    <main class="admin-main-content">
      <!-- Top Header -->
      <header class="admin-topbar">
        <div class="topbar-left">
          <button class="mobile-menu-btn" @click="toggleSidebar" :aria-label="i18n.t('admin.open_menu')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="3" y1="6" x2="21" y2="6"/>
              <line x1="3" y1="12" x2="21" y2="12"/>
              <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </button>
          <h2 class="topbar-title">{{ pageTitle }}</h2>
        </div>
        <div class="topbar-right">
          <span class="topbar-time">{{ currentTime }}</span>
          <button @click="i18n.toggleLocale()" class="topbar-btn lang-btn">
            {{ i18n.locale.toUpperCase() }}
          </button>
          <router-link to="/" class="topbar-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span class="topbar-btn-text">{{ i18n.t('admin.view_site') }}</span>
          </router-link>
        </div>
      </header>

      <!-- Page Content -->
      <div class="admin-page-content">
        <router-view v-slot="{ Component }">
          <transition name="admin-fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>

      <!-- Admin Footer (Now using shared AppFooter for consistency) -->
      <AppFooter style="margin-top: auto;" />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18nStore } from '../../stores/i18n'
import AdminSidebar from '../../components/AdminSidebar.vue'
import AppFooter from '../../components/layout/AppFooter.vue'

const sidebarOpen = ref(false)
const route = useRoute()
const i18n = useI18nStore()
const now = ref(new Date())
let timer = null

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

const pageTitle = computed(() => {
  const map = {
    '/admin/categories': i18n.t('admin.manage_categories'),
    '/admin/products': i18n.t('admin.manage_products'),
    '/admin/orders': i18n.t('admin.manage_orders'),
    '/admin/users': i18n.t('admin.manage_users'),
    '/admin/reviews': i18n.t('admin.reviews'),
    '/admin/products/trash': i18n.t('admin.trash'),
    '/admin': i18n.t('admin.dashboard'),
  }
  if (map[route.path]) return map[route.path]
  if (route.path.startsWith('/admin/products/edit')) return i18n.t('admin.edit') + ' ' + i18n.t('admin.manage_products').toLowerCase()
  if (route.path.startsWith('/admin/products/create')) return i18n.t('admin.add') + ' ' + i18n.t('admin.manage_products').toLowerCase()
  return i18n.t('admin.dashboard')
})

const currentTime = computed(() =>
  now.value.toLocaleTimeString(i18n.locale === 'vi' ? 'vi-VN' : 'en-US', { hour: '2-digit', minute: '2-digit' })
)

onMounted(() => { timer = setInterval(() => { now.value = new Date() }, 1000) })
onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
/* ===== WRAPPER ===== */
.admin-wrapper {
  display: flex;
  min-height: 100vh;
  background: #f5f5f7;
}

/* ===== OVERLAY (mobile) ===== */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(3px);
  z-index: 1035;
  transition: opacity 0.3s;
}

/* ===== MAIN CONTENT ===== */
.admin-main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 100vh;
}

/* ===== TOPBAR ===== */
.admin-topbar {
  background: #fff;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  height: 56px;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}

.topbar-left { display: flex; align-items: center; gap: 12px; }

.mobile-menu-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid rgba(0,0,0,0.08);
  background: #f5f5f7;
  cursor: pointer;
  color: #1d1d1f;
  transition: 0.2s;
  flex-shrink: 0;
}
.mobile-menu-btn:hover { background: #e5e5ea; }

.topbar-title {
  font-size: 15px;
  font-weight: 700;
  color: #1d1d1f;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.topbar-right { display: flex; align-items: center; gap: 14px; }

.topbar-time {
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  font-variant-numeric: tabular-nums;
}

.topbar-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #0071e3;
  text-decoration: none;
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid rgba(0,113,227,0.2);
  transition: 0.2s;
  white-space: nowrap;
}
.topbar-btn:hover { background: rgba(0,113,227,0.06); }

/* ===== PAGE CONTENT ===== */
.admin-page-content {
  flex: 1;
  padding: 24px 24px 60px; /* Thêm padding bottom để nội dung không bị dính footer */
  overflow-x: hidden;
}

/* ===== ADMIN FOOTER OVERRIDE (Compact Dark Mode) ===== */
:deep(.site-footer) {
  margin-top: auto;
  padding: 24px 0 16px; /* Giảm padding cực mạnh */
  background: #1c1c1e !important; /* Trả lại màu đen */
  color: #fff !important;
  border-top: none;
}
:deep(.footer-container) {
  max-width: none;
  padding: 0 40px;
}
:deep(.footer-grid) {
  gap: 20px; /* Giảm gap giữa các cột */
  padding-bottom: 20px;
}
:deep(.footer-desc) { display: none; } /* Ẩn mô tả dài dòng trong Admin */
:deep(.footer-heading) { 
  font-size: 10px; 
  margin-bottom: 12px;
  color: rgba(255,255,255,0.4) !important; 
}
:deep(.footer-list li), :deep(.footer-list a) { 
  font-size: 12px; 
  color: rgba(255,255,255,0.6) !important; 
}
:deep(.footer-brand) { font-size: 15px; margin-bottom: 8px; }
:deep(.social-btn) { 
  width: 28px; height: 28px; font-size: 11px; 
  border-color: rgba(255,255,255,0.1) !important;
}
:deep(.footer-bottom) { 
  margin-top: 16px; padding-top: 16px;
  font-size: 10px;
}

/* ===== TRANSITIONS ===== */
.admin-fade-enter-active, .admin-fade-leave-active { transition: all 0.22s ease; }
.admin-fade-enter-from { opacity: 0; transform: translateY(6px); }
.admin-fade-leave-to { opacity: 0; }

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .mobile-menu-btn { display: flex; }
  .sidebar-overlay.show { display: block; }
  .topbar-time { font-size: 10px; }
  .admin-page-content { padding: 16px 12px; }
}
@media (max-width: 480px) {
  .topbar-title { font-size: 13px; }
  .topbar-btn-text { display: none; }
  .topbar-btn { padding: 6px 10px; }
  .admin-page-content { padding: 12px 10px; }
}
</style>
