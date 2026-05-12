<template>
  <aside class="admin-sidebar" :class="{ 'sidebar-open': isOpen }">
    <!-- Brand -->
    <div class="sidebar-header">
      <router-link to="/" class="sidebar-brand">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
          <path d="M10 5h4" stroke-width="2"></path>
          <line x1="12" y1="18" x2="12.01" y2="18"></line>
        </svg>
        <span>SELLPHONES</span>
      </router-link>
      <button class="sidebar-close-btn" @click="$emit('close')" :aria-label="i18n.t('admin.close_menu')">×</button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <div class="nav-section-label">{{ i18n.t('admin.overview') }}</div>
      <router-link to="/admin" class="nav-link" :class="{ active: isExact('/admin') }" @click="$emit('close')">
        <span class="nav-icon">📊</span> {{ i18n.t('admin.dashboard') }}
      </router-link>

      <div class="nav-section-label">{{ i18n.t('admin.categories') }}</div>
      <router-link to="/admin/categories" class="nav-link" :class="{ active: isActive('/admin/categories') }" @click="$emit('close')">
        <span class="nav-icon">🏷️</span> {{ i18n.t('admin.categories') }}
      </router-link>
      <router-link to="/admin/products" class="nav-link" :class="{ active: isActive('/admin/products') }" @click="$emit('close')">
        <span class="nav-icon">📱</span> {{ i18n.t('admin.products_stock') }}
      </router-link>
      <router-link to="/admin/products/trash" class="nav-link" :class="{ active: isActive('/admin/products/trash') }" @click="$emit('close')">
        <span class="nav-icon">🗑️</span> {{ i18n.t('admin.trash') }}
      </router-link>

      <div class="nav-section-label">{{ i18n.t('admin.business') }}</div>
      <router-link to="/admin/orders" class="nav-link" :class="{ active: isActive('/admin/orders') }" @click="$emit('close')">
        <span class="nav-icon">🛒</span> {{ i18n.t('admin.orders') }}
      </router-link>
      <router-link to="/admin/users" class="nav-link" :class="{ active: isActive('/admin/users') }" @click="$emit('close')">
        <span class="nav-icon">👥</span> {{ i18n.t('admin.customers') }}
      </router-link>
      <router-link to="/admin/reviews" class="nav-link" :class="{ active: isActive('/admin/reviews') }" @click="$emit('close')">
        <span class="nav-icon">⭐</span> {{ i18n.t('admin.reviews') }}
      </router-link>


      <hr class="nav-divider" />
      <router-link to="/" class="nav-link info" @click="$emit('close')">
        <span class="nav-icon">🌐</span> {{ i18n.t('admin.view_site') }}
      </router-link>
      <button class="nav-link danger" @click="handleLogout">
        <span class="nav-icon">🚪</span> {{ i18n.t('admin.logout') }}
      </button>
    </nav>
  </aside>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'

const props = defineProps({ isOpen: { type: Boolean, default: false } })
const emit = defineEmits(['close'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const i18n = useI18nStore()

const isActive = (path) => route.path.startsWith(path)
const isExact = (path) => route.path === path

async function handleLogout() {
  emit('close')
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
/* ===== SIDEBAR (sticky for desktop, fixed+slide for mobile) ===== */
.admin-sidebar {
  width: 240px;
  flex-shrink: 0;
  background: #1d1d1f;
  color: #fff;
  height: 100vh;
  position: sticky;
  top: 0;
  z-index: 1040;
  overflow-y: auto;
  scrollbar-width: none;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
}
.admin-sidebar::-webkit-scrollbar { display: none; }

/* ===== HEADER ===== */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  text-decoration: none;
  letter-spacing: -0.3px;
}
.sidebar-close-btn {
  display: none;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.1);
  color: #fff;
  font-size: 18px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: 0.2s;
}
.sidebar-close-btn:hover { background: rgba(255,255,255,0.2); }

/* ===== NAV ===== */
.sidebar-nav {
  flex: 1;
  padding: 8px 0 20px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.nav-section-label {
  padding: 12px 20px 4px;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(255,255,255,0.3);
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  color: rgba(255,255,255,0.55);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  border-left: 3px solid transparent;
  cursor: pointer;
  background: none;
  border-right: none;
  border-top: none;
  border-bottom: none;
  width: 100%;
  text-align: left;
  font-family: 'Inter', sans-serif;
  transition: all 0.18s;
}
.nav-link:hover {
  color: #fff;
  background: rgba(255,255,255,0.05);
}
.nav-link.active {
  color: #fff;
  background: rgba(255,255,255,0.06);
  border-left-color: #0071e3;
}
.nav-icon { font-size: 13px; width: 18px; text-align: center; flex-shrink: 0; font-style: normal; }

.nav-link.info { color: #60a5fa; }
.nav-link.info:hover { color: #93c5fd; }
.nav-link.danger { color: #f87171; }
.nav-link.danger:hover { background: rgba(248,113,113,0.08); }

.nav-divider {
  margin: 10px 16px;
  border: none;
  border-top: 1px solid rgba(255,255,255,0.07);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100dvh;
    transform: translateX(-100%);
    box-shadow: 4px 0 24px rgba(0,0,0,0.4);
  }
  .admin-sidebar.sidebar-open {
    transform: translateX(0);
  }
  .sidebar-close-btn { display: flex; }
  .nav-section-label { padding: 10px 20px 4px; }
  .nav-link { padding: 12px 20px; font-size: 14px; }
}
</style>
