<template>
  <div class="flex-1 flex items-center justify-center py-20 px-4 relative z-10 bg-[#f9f9f9]">
    <div class="backdrop-blur-2xl bg-white/60 border border-white/80 rounded-[2.5rem] md:rounded-[3rem] shadow-[0_8px_30px_rgba(0,0,0,0.06)] w-full max-w-md p-6 md:p-10 relative overflow-hidden">
      <!-- Glow effect behind form -->
      <div class="absolute -top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-blue-300/30 blur-[80px] pointer-events-none"></div>
      <div class="absolute -bottom-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-indigo-300/30 blur-[80px] pointer-events-none"></div>
      
      <!-- Logo / Title -->
      <div class="text-center mb-10 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">{{ i18nStore.t('auth.welcome_back') }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" novalidate class="space-y-6 relative z-10">
        <div>
          <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.email') }}</label>
          <input v-model="form.email" type="email" inputmode="email" autocomplete="email" placeholder="email@example.com"
            @input="errors && (errors.email = null)"
            class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
            :class="{'input-error': errors?.email}" />
          <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
        </div>
        <div class="relative group">
          <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.password') }}</label>
          <div class="relative">
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="••••••••"
              @input="errors && (errors.password = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400 pr-14"
              :class="{'input-error': errors?.password}" />
            
            <button type="button" @click="showPassword = !showPassword"
              class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-blue-600 transition-colors">
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
          <p v-if="errors?.password" class="form-error-label">{{ errors.password[0] }}</p>
        </div>

        <!-- Brute-force & System Errors -->
        <div v-if="attemptsLeft !== null" class="bg-amber-50/80 backdrop-blur-sm border border-amber-200 rounded-2xl p-4 text-[13px] font-bold text-amber-700 shadow-sm">
          {{ i18nStore.t('auth.locked_warning', { count: attemptsLeft }) }}
        </div>
        <div v-if="lockedUntil" class="bg-rose-50/80 backdrop-blur-sm border border-rose-200 rounded-2xl p-4 text-[13px] font-bold text-rose-700 shadow-sm">
          {{ i18nStore.t('auth.locked_error', { count: lockedSeconds }) }}
        </div>

        <button type="submit" :disabled="authStore.loading || !!lockedUntil"
          class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em]">
          {{ authStore.loading ? i18nStore.t('common.processing').toUpperCase() : i18nStore.t('auth.login').toUpperCase() }}
        </button>
      </form>

      <div class="mt-12 flex justify-between items-center text-[13px] font-bold uppercase tracking-widest relative z-10 auth-links">
        <router-link to="/forgot-password" class="hover:text-blue-600 transition-all">{{ i18nStore.t('auth.forgot_password') }}</router-link>
        <router-link to="/register" class="hover:text-blue-600 transition-all">{{ i18nStore.t('auth.create_account') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'

const authStore = useAuthStore()
const i18nStore = useI18nStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const form = ref({ email: '', password: '' })
const errorMsg = ref('')
const errors = ref({})
const showPassword = ref(false)
const attemptsLeft = ref(null)
const lockedUntil = ref(false)
const lockedSeconds = ref(0)
let lockTimer = null

async function handleLogin() {
  errorMsg.value = ''
  errors.value = {}
  attemptsLeft.value = null

  // Client-side validation
  let hasError = false
  if (!form.value.email?.trim()) {
    errors.value.email = [i18nStore.t('auth.email_error')]
    hasError = true
  }
  if (!form.value.password?.trim()) {
    errors.value.password = [i18nStore.t('auth.password_error')]
    hasError = true
  }
  if (hasError) return

  const result = await authStore.login(form.value.email, form.value.password)
  if (result.success) {
    toast.success(i18nStore.t('common.login_success'))
    router.push(route.query.redirect || '/')
  } else {
    // Nếu là lỗi validation hoặc sai tài khoản
    if (result.data?.errors) {
      errors.value = result.data.errors
    } else {
      const msg = result.message || i18nStore.t('common.error')
      if (msg.toLowerCase().includes('email')) {
        errors.value.email = [msg]
      } else if (msg.toLowerCase().includes('password') || msg.toLowerCase().includes(i18nStore.t('common.password').toLowerCase())) {
        errors.value.password = [msg]
      } else {
        errors.value.email = [msg]
      }
    }

    if (result.attemptsLeft !== undefined) {
      attemptsLeft.value = result.attemptsLeft
    }
    if (result.data?.locked) {
      lockedUntil.value = true
      lockedSeconds.value = result.data.retry_after || 900
      lockTimer = setInterval(() => {
        lockedSeconds.value--
        if (lockedSeconds.value <= 0) {
          clearInterval(lockTimer)
          lockedUntil.value = false
        }
      }, 1000)
    }
  }
}

onUnmounted(() => clearInterval(lockTimer))
</script>

<style scoped>
.auth-links a {
  color: #334155 !important; /* slate-700 */
  text-decoration: none;
}
.auth-links a:hover {
  color: #2563eb !important; /* blue-600 */
}
</style>
