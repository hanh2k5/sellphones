<template>
  <nav class="glass-nav sticky-top">
    <div class="nav-container">
      <!-- LEFT: Logo -->
      <div class="nav-left">
        <router-link to="/" class="nav-brand">
          <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="url(#brandGradient)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <defs>
              <linearGradient id="brandGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#2563eb;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#4f46e5;stop-opacity:1" />
              </linearGradient>
            </defs>
            <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
            <!-- Dynamic Island Detail -->
            <path d="M10 5h4" stroke-width="2" stroke="url(#brandGradient)"></path>
            <line x1="12" y1="18" x2="12.01" y2="18"></line>
          </svg>
          <span class="brand-name">SELLPHONES</span>
        </router-link>
      </div>

      <!-- CENTER: Search -->
      <div class="nav-search-wrap">
        <div class="search-inner">
          <input 
            :value="searchQuery"
            @input="$emit('update:searchQuery', $event.target.value)"
            @keyup.enter="$emit('do-search')"
            type="text" 
            :placeholder="i18n.t('product.search_placeholder')" 
            class="search-box" 
          />
          <button class="search-btn" @click="$emit('do-search')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>
        </div>
      </div>

      <!-- RIGHT: Account -->
      <div class="nav-right">
        <!-- Nút chuyển ngôn ngữ -->
        <button class="lang-switcher-btn" @click="i18n.toggleLocale()" :title="i18n.isVietnamese ? 'Switch to English' : 'Chuyển sang Tiếng Việt'">
          <span class="lang-flag">{{ i18n.isVietnamese ? '🇻🇳' : '🇺🇸' }}</span>
          <span class="lang-code">{{ i18n.locale.toUpperCase() }}</span>
        </button>

        <!-- Cart Indicator (Thêm mới) -->
        <div v-if="isLoggedIn" class="cart-nav-indicator">
          <router-link to="/cart" class="cart-icon-btn">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
              <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.56-7.43H5.12"/>
            </svg>
            <span v-if="cartStore.totalItems > 0" class="cart-badge">{{ cartStore.totalItems }}</span>
          </router-link>
        </div>

        <!-- User Dropdown -->
        <div v-if="isLoggedIn" class="user-dropdown-wrap">
          <button class="user-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="user-name">{{ userName }}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="dropdown-menu-custom">
            <router-link v-if="isAdmin" to="/admin" class="dropdown-item-custom admin">🛡️ {{ i18n.t('nav.admin') }}</router-link>
            <router-link to="/profile" class="dropdown-item-custom">👤 {{ i18n.t('nav.profile') }}</router-link>
            <div class="dropdown-divider"></div>
            <button @click="$emit('logout')" class="dropdown-item-custom danger">📤 {{ i18n.t('nav.logout') }}</button>
          </div>
        </div>
        <router-link v-else to="/login" class="btn-login-nav">{{ i18n.t('nav.login') }}</router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { useI18nStore } from '../../stores/i18n'
import { useCartStore } from '../../stores/cart'

defineProps({
  searchQuery: String,
  isLoggedIn: Boolean,
  isAdmin: Boolean,
  userName: String
})

defineEmits(['update:searchQuery', 'do-search', 'logout'])

const i18n = useI18nStore()
const cartStore = useCartStore()
</script>

<style scoped>
.glass-nav {
  background: rgba(255,255,255,0.85);
  backdrop-filter: saturate(180%) blur(20px);
  border-bottom: 1px solid rgba(0,0,0,0.08);
  position: sticky; top: 0; z-index: 1030;
}
.nav-container {
  max-width: 1200px; margin: 0 auto; padding: 0 24px;
  height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 24px;
}
.nav-left { display: flex; align-items: center; gap: 20px; flex: 1; }
.nav-brand { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 17px; color: #1d1d1f; }
.brand-icon { width: 22px; height: 22px; }
.brand-name { font-family: 'Outfit', sans-serif; }

.nav-search-wrap { flex: 2; max-width: 500px; position: relative; }
.search-inner { position: relative; }
.search-box { width: 100%; background: rgba(0,0,0,0.05); border: none; border-radius: 20px; padding: 8px 44px 8px 18px; font-size: 14px; outline: none; transition: 0.2s; color: #1d1d1f; }
.search-box:focus { background: rgba(0,0,0,0.08); box-shadow: 0 0 0 3px rgba(0,113,227,0.1); }
.search-btn { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: transparent; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #86868b; cursor: pointer; }

.nav-right { display: flex; align-items: center; gap: 20px; flex: 1; justify-content: flex-end; }

.cart-nav-indicator { position: relative; }
.cart-icon-btn { 
  display: flex; align-items: center; justify-content: center; 
  color: #1d1d1f; transition: 0.2s; position: relative;
  width: 40px; height: 40px; border-radius: 50%;
}
.cart-icon-btn:hover { background: rgba(0,0,0,0.05); }
.cart-badge {
  position: absolute; top: 4px; right: 4px;
  background: #ff3b30; color: #fff; font-size: 10px; font-weight: 800;
  min-width: 18px; height: 18px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px; border: 2px solid #fff;
}

.lang-switcher-btn {
  display: flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.05);
  border: 1px solid rgba(0,0,0,0.05); padding: 6px 12px; border-radius: 14px;
  cursor: pointer; transition: 0.2s; height: 34px;
}
.lang-code { font-size: 11px; font-weight: 700; color: #1d1d1f; }

.user-dropdown-wrap { position: relative; }
.user-btn { display: flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; font-size: 13px; font-weight: 700; color: #1d1d1f; padding: 6px 10px; border-radius: 10px; transition: 0.2s; }
.dropdown-menu-custom { position: absolute; right: 0; top: calc(100% + 8px); width: 200px; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.06); padding: 8px; opacity: 0; visibility: hidden; transition: all 0.2s; z-index: 50; }
.user-dropdown-wrap:hover .dropdown-menu-custom { opacity: 1; visibility: visible; }
.dropdown-item-custom { display: block; width: 100%; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #1d1d1f; border-radius: 8px; transition: 0.15s; text-align: left; text-decoration: none; }
.dropdown-item-custom:hover { background: #f5f5f7; }
.dropdown-item-custom.admin { color: #2563eb; }
.dropdown-item-custom.danger { color: #ff3b30; }
.dropdown-divider { height: 1px; background: rgba(0,0,0,0.06); margin: 4px 8px; }
.btn-login-nav { background: #1d1d1f; color: #fff; padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; }

@media (max-width: 768px) {
  .brand-name, .user-name, .lang-code { display: none; }
  .nav-container { padding: 0 16px; gap: 8px; }
}
</style>
