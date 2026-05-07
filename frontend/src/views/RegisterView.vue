<template>
  <div class="flex-1 flex items-center justify-center py-20 px-4 relative z-10 bg-[#f9f9f9]">
    <div class="backdrop-blur-2xl bg-white/60 border border-white/80 rounded-[3rem] shadow-[0_8px_30px_rgba(0,0,0,0.06)] w-full max-w-md p-10 relative overflow-hidden">
      <!-- Glow effect behind form -->
      <div class="absolute -top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-blue-300/30 blur-[80px] pointer-events-none"></div>
      <div class="absolute -bottom-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-indigo-300/30 blur-[80px] pointer-events-none"></div>
      
      <!-- Logo / Title -->
      <div class="text-center mb-10 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">{{ i18nStore.t('auth.start_experience') }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleRegister" novalidate class="space-y-5 relative z-10">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest">{{ i18nStore.t('auth.name') }}</label>
          <input v-model="form.name" type="text" :placeholder="i18nStore.t('auth.name')"
            @input="errors && (errors.name = null)"
            class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-6 py-4 text-[15px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all placeholder:text-slate-300"
            :class="{'input-error': errors?.name}" />
          <p v-if="errors?.name" class="form-error-label">{{ errors.name[0] }}</p>
        </div>
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest">{{ i18nStore.t('auth.email') }}</label>
          <input v-model="form.email" type="email" placeholder="email@example.com"
            @input="errors && (errors.email = null)"
            class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-6 py-4 text-[15px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all placeholder:text-slate-300"
            :class="{'input-error': errors?.email}" />
          <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-widest">{{ i18nStore.t('auth.address') }}</label>
          <input v-model="form.address" type="text" :placeholder="i18nStore.t('auth.address_placeholder')"
            @input="errors && (errors.address = null)"
            class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-6 py-4 text-[15px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all placeholder:text-slate-300"
            :class="{'input-error': errors?.address}" />
          <p v-if="errors?.address" class="form-error-label">{{ errors.address[0] }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-slate-700 mb-2 uppercase tracking-widest">{{ i18nStore.t('auth.password') }}</label>
            <div class="relative">
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                @input="errors && (errors.password = null)"
                class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-5 py-4 text-[14px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all placeholder:text-slate-300 pr-12"
                :class="{'input-error': errors?.password}" />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-blue-600 transition-colors">
                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
              </button>
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-700 mb-2 uppercase tracking-widest">{{ i18nStore.t('auth.confirm_password') }}</label>
            <div class="relative">
              <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" placeholder="••••••••"
                @input="errors && (errors.password_confirmation = null)"
                class="w-full bg-white/80 border border-white shadow-inner rounded-2xl px-5 py-4 text-[14px] font-medium focus:outline-none focus:ring-2 focus:ring-blue-100 transition-all placeholder:text-slate-300 pr-12"
                :class="{'input-error': errors?.password_confirmation}" />
              <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-blue-600 transition-colors">
                <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        <p v-if="errors?.password || errors?.password_confirmation" class="form-error-label">
          {{ (errors.password?.[0] || errors.password_confirmation?.[0]) }}
        </p>

        <button type="submit" :disabled="authStore.loading"
          class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] mt-4">
          {{ authStore.loading ? i18nStore.t('auth.creating').toUpperCase() : i18nStore.t('auth.create_now').toUpperCase() }}
        </button>
      </form>

      <div class="mt-10 text-center text-xs font-bold uppercase tracking-widest relative z-10">
        <span class="text-slate-400">{{ i18nStore.t('auth.already_have_account') }} </span>
        <router-link to="/login" class="text-indigo-600 hover:text-indigo-700 transition-all ml-1">{{ i18nStore.t('auth.login_now') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'

const authStore = useAuthStore()
const i18nStore = useI18nStore()
const router = useRouter()
const toast = useToast()

const form = ref({ name: '', email: '', address: '', password: '', password_confirmation: '' })
const errorMsg = ref('')
const errors = ref({})
const showPassword = ref(false)
const showConfirmPassword = ref(false)

async function handleRegister() {
  errorMsg.value = ''
  errors.value = {}

  // Client-side validation
  let hasError = false
  if (!form.value.name?.trim()) {
    errors.value.name = [i18nStore.t('auth.name_error') || 'Vui lòng nhập họ tên']
    hasError = true
  }
  if (!form.value.email?.trim()) {
    errors.value.email = [i18nStore.t('auth.email_error') || 'Vui lòng nhập email']
    hasError = true
  }
  if (!form.value.address?.trim()) {
    errors.value.address = [i18nStore.t('auth.address_error') || 'Vui lòng nhập địa chỉ']
    hasError = true
  }
  if (!form.value.password?.trim()) {
    errors.value.password = [i18nStore.t('auth.password_error') || 'Vui lòng nhập mật khẩu']
    hasError = true
  }
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = ['Mật khẩu xác nhận không khớp']
    hasError = true
  }

  if (hasError) return

  const result = await authStore.register(form.value.name, form.value.email, form.value.address, form.value.password, form.value.password_confirmation)

  if (result.success) {
    toast.success(i18nStore.t('auth.register_success') || 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.')
    router.push('/login')
  } else {
    // Nếu là lỗi validation
    if (result.errors) {
      errors.value = result.errors
    } else {
      const msg = result.message || 'Lỗi đăng ký hệ thống'
      // Đẩy vào label đỏ thay vì alert
      if (msg.toLowerCase().includes('email')) {
        errors.value.email = [msg]
      } else if (msg.toLowerCase().includes('name') || msg.toLowerCase().includes('tên')) {
        errors.value.name = [msg]
      } else {
        errors.value.name = [msg]
      }
    }
  }
}
</script>
