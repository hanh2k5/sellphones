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
            @focus="$emit('show-suggest')" 
            @blur="$emit('hide-suggest')"
            @keyup.enter="$emit('do-search')"
            type="text" 
            :placeholder="i18n.t('nav.search_placeholder')" 
            class="search-box" 
          />
          <button class="search-btn" @click="$emit('do-search')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>
        </div>
        <!-- Suggest Dropdown -->
        <Transition name="suggest">
          <div v-if="showSuggest && suggestions.length" class="suggest-box">
            <div v-for="p in suggestions" :key="p.id" @mousedown="$emit('go-product', p.id)" class="suggest-item">
              <div class="suggest-img">
                <img :src="getImageUrl(p.hinh_anh)" :alt="p.name" @error="onImgError" />
              </div>
              <div class="suggest-info">
                <p class="suggest-name">{{ p.name }}</p>
                <p class="suggest-price">{{ fmtPrice(p.price) }}</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <!-- RIGHT: Icons + Account -->
      <div class="nav-right">
        <!-- Language Switcher -->
        <button class="lang-switcher" @click="i18n.toggleLocale()">
          <span class="lang-code">{{ i18n.locale.toUpperCase() }}</span>
        </button>

        <router-link to="/cart" class="nav-icon-btn" :title="i18n.t('nav.cart')">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span v-if="cartCount > 0" class="badge-notify">{{ cartCount }}</span>
        </router-link>

        <div v-if="isLoggedIn" class="user-dropdown-wrap">
          <button class="user-btn-wrap" @click="showDropdown = !showDropdown">
            <span class="user-name-text">Hi, {{ userName }}</span>
            <div class="user-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
          </button>
          <div v-if="showDropdown" class="mobile-dropdown">
            <router-link v-if="isAdmin" to="/admin" class="dropdown-item" @click="showDropdown = false">🛡️ {{ i18n.t('nav.admin') }}</router-link>
            <router-link to="/profile" class="dropdown-item" @click="showDropdown = false">👤 {{ i18n.t('nav.profile') }}</router-link>
            <router-link to="/orders" class="dropdown-item" @click="showDropdown = false">📦 {{ i18n.t('nav.my_orders') }}</router-link>
            <div class="dropdown-divider"></div>
            <button @click="handleLogout" class="dropdown-item danger">📤 {{ i18n.t('nav.logout') }}</button>
          </div>
        </div>
        <router-link v-else to="/login" class="login-btn">{{ i18n.t('nav.login') }}</router-link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18nStore } from '../../stores/i18n'
import { useUtils } from '../../composables/useUtils'

const props = defineProps({
  searchQuery: String,
  showSuggest: Boolean,
  suggestions: Array,
  cartCount: Number,
  isLoggedIn: Boolean,
  isAdmin: Boolean,
  userName: String
})

const emit = defineEmits([
  'update:searchQuery', 
  'do-search', 
  'show-suggest', 
  'hide-suggest', 
  'go-product', 
  'logout'
])

const i18n = useI18nStore()
const { fmtPrice, getImageUrl } = useUtils()
const showDropdown = ref(false)

function handleLogout() {
  showDropdown.value = false
  emit('logout')
}

function closeDropdown(e) {
  if (!e.target.closest('.user-dropdown-wrap')) showDropdown.value = false
}

function onImgError(e) {
  e.target.src = 'https://via.placeholder.com/40'
}

onMounted(() => window.addEventListener('click', closeDropdown))
onUnmounted(() => window.removeEventListener('click', closeDropdown))
</script>

<style scoped>
.glass-nav {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky; top: 0; z-index: 1030;
}
.nav-container {
  max-width: 1400px; margin: 0 auto; padding: 0 24px;
  height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 40px;
}
.nav-left { display: flex; align-items: center; gap: 20px; }
.nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 20px; color: #1e293b; letter-spacing: -0.03em; }
.brand-icon { width: 28px; height: 28px; }

/* CENTER: Search */
.nav-search-wrap { flex: 1; max-width: 600px; position: relative; }
.search-inner { position: relative; }
.search-box {
  width: 100%; background: #f1f5f9; border: 1px solid transparent; border-radius: 14px;
  padding: 12px 50px 12px 20px; font-size: 14.5px; font-weight: 500; outline: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #1e293b;
}
.search-box:focus {
  background: #fff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}
.search-btn {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  width: 36px; height: 36px; border-radius: 10px; background: transparent;
  border: none; color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: 0.2s;
}
.search-btn:hover { background: #e2e8f0; color: #3b82f6; }

/* Suggestion Box */
.suggest-box {
  position: absolute; top: calc(100% + 10px); left: 0; right: 0;
  background: #fff; border-radius: 18px; box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  border: 1px solid rgba(0,0,0,0.06); overflow: hidden; z-index: 2000;
}
.suggest-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; transition: 0.2s; }
.suggest-item:hover { background: #f8fafc; }
.suggest-img { width: 44px; height: 44px; background: #f1f5f9; border-radius: 10px; flex-shrink: 0; padding: 4px; }
.suggest-img img { width: 100%; height: 100%; object-fit: contain; }
.suggest-info { flex: 1; min-width: 0; }
.suggest-name { font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.suggest-price { font-size: 12px; font-weight: 700; color: #3b82f6; margin: 2px 0 0; }

/* RIGHT: Icons */
.nav-right { display: flex; align-items: center; gap: 12px; }
.lang-switcher {
  background: #f1f5f9; border: none; padding: 4px 10px; border-radius: 10px;
  font-size: 11px; font-weight: 800; color: #475569; cursor: pointer; transition: 0.2s;
  height: 32px; display: flex; align-items: center; justify-content: center;
}
.lang-switcher:hover { background: #e2e8f0; color: #1e293b; }
.nav-icon-btn { position: relative; color: #475569; transition: 0.2s; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; }
.nav-icon-btn:hover { color: #1e293b; transform: translateY(-2px); }
.badge-notify {
  position: absolute; top: 0; right: 0; min-width: 18px; height: 18px;
  background: #ef4444; color: #fff; border-radius: 9px; font-size: 9px;
  font-weight: 800; display: flex; align-items: center; justify-content: center;
  border: 2px solid #fff; padding: 0 3px;
}

.login-btn {
  background: #1e293b; color: #fff; padding: 8px 18px; border-radius: 10px;
  font-size: 13.5px; font-weight: 700; transition: 0.3s;
}
.login-btn:hover { background: #334155; transform: translateY(-2px); }

.nav-search-wrap { flex: 1; max-width: 500px; margin: 0 40px; position: relative; }
.search-inner { 
  display: flex; align-items: center; background: rgba(241, 245, 249, 0.6); 
  border-radius: 20px; padding: 2px 6px; border: 1px solid rgba(226, 232, 240, 0.5);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  backdrop-filter: blur(5px);
}
.search-inner:focus-within { 
  background: #fff; 
  border-color: #3b82f6; 
  box-shadow: 0 10px 30px rgba(59,130,246,0.08);
  transform: translateY(-1px);
}
.search-box { 
  flex: 1; border: none; background: transparent; padding: 12px 18px; 
  font-size: 15px; font-weight: 500; color: #1e293b; outline: none;
}
.search-btn { 
  width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
  border-radius: 16px; border: none; background: transparent; color: #64748b;
  cursor: pointer; transition: all 0.3s;
}
.search-inner:focus-within .search-btn { color: #2563eb; background: rgba(59,130,246,0.05); }
.search-btn:hover { transform: scale(1.1); }

.user-dropdown-wrap { position: relative; }
.user-btn-wrap { 
  display: flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; padding: 2px 4px; border-radius: 10px; transition: 0.2s;
}
.user-btn-wrap:hover { background: #f1f5f9; }
.user-name-text { font-size: 13px; font-weight: 700; color: #1e293b; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-btn { color: #475569; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; }

.mobile-dropdown {
  position: absolute; top: 100%; right: 0; background: #fff; border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #f1f5f9;
  min-width: 180px; z-index: 100; overflow: hidden; margin-top: 8px;
}
.dropdown-item {
  display: flex; align-items: center; gap: 10px; padding: 10px 14px;
  color: #475569; font-size: 13.5px; font-weight: 600; transition: 0.2s;
}
.dropdown-item:hover { background: #f8fafc; color: #1e293b; }
.dropdown-item.danger { color: #ef4444; }
.dropdown-divider { height: 1px; background: #f1f5f9; margin: 4px 0; }

/* Transitions */
.suggest-enter-active, .suggest-leave-active { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.suggest-enter-from, .suggest-leave-to { opacity: 0; transform: translateY(-10px); }

@media (max-width: 768px) {
  .nav-container { height: auto; flex-wrap: wrap; padding: 10px 12px; gap: 8px; }
  .nav-left { order: 1; flex: 1; }
  .nav-right { order: 2; flex: 1; justify-content: flex-end; gap: 8px; }
  .nav-search-wrap { order: 3; flex: 0 0 100%; max-width: none; margin: 0; }
  .brand-name { display: block; font-size: 15px; font-weight: 800; }
  .brand-icon { width: 22px; height: 22px; }
  .user-name-text { display: block; font-size: 11px; max-width: 60px; }
  .search-box { padding: 9px 40px 9px 14px; font-size: 13px; border-radius: 10px; }
  .search-btn { right: 4px; width: 32px; height: 32px; }
  .login-btn { padding: 6px 12px; font-size: 12.5px; border-radius: 8px; }
  .lang-switcher { height: 28px; padding: 0 8px; font-size: 10px; border-radius: 8px; }
  .nav-icon-btn { width: 32px; height: 32px; }
  .user-btn { width: 32px; height: 32px; }
}
</style>
