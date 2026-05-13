<template>
  <div class="flex-1 flex items-center justify-center py-12 md:py-20 px-4 relative z-10 bg-[#f9f9f9]">


    <div class="backdrop-blur-2xl bg-white/60 border border-white/80 rounded-[2.5rem] md:rounded-[3rem] shadow-[0_8px_30px_rgba(0,0,0,0.06)] w-full max-w-md p-6 md:p-10 relative overflow-hidden">
      <!-- Glow effect -->
      <div class="absolute -top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-blue-300/20 blur-[80px] pointer-events-none"></div>
      
      <div class="text-center mb-10 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">{{ i18nStore.t('auth.start_experience') }}</p>
      </div>

      <form @submit.prevent="handleRegister" novalidate class="space-y-4 relative z-10">
        <div>
          <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.name') }}</label>
          <input v-model="form.name" type="text" :placeholder="i18nStore.t('auth.name')"
            @input="errors && (errors.name = null)"
            class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
            :class="{'input-error': errors?.name}" />
          <p v-if="errors?.name" class="form-error-label">{{ errors.name[0] }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.email') }}</label>
            <input v-model="form.email" type="email" inputmode="email" autocomplete="email" placeholder="admin@gmail.com"
              @input="errors && (errors.email = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.email}" />
            <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.phone') || 'SỐ ĐIỆN THOẠI' }}</label>
            <input v-model="form.phone" type="tel" inputmode="tel" placeholder="0901234567"
              @input="errors && (errors.phone = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.phone}" />
            <p v-if="errors?.phone" class="form-error-label">{{ errors.phone[0] }}</p>
          </div>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.address') }}</label>
          <input v-model="form.address" type="text" :placeholder="i18nStore.t('auth.address_placeholder')"
            @input="errors && (errors.address = null)"
            class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
            :class="{'input-error': errors?.address}" />
          <p v-if="errors?.address" class="form-error-label">{{ errors.address[0] }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.password') }}</label>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••"
              @input="errors && (errors.password = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[14px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.password}" />
            <p v-if="errors?.password" class="form-error-label">{{ errors.password[0] }}</p>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ i18nStore.t('auth.confirm_password') }}</label>
            <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="••••"
              @input="errors && (errors.password_confirmation = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[14px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.password_confirmation}" />
            <p v-if="errors?.password_confirmation" class="form-error-label">{{ errors.password_confirmation[0] }}</p>
          </div>
        </div>

        <button type="submit" :disabled="authStore.loading"
          class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 text-white font-bold py-5 rounded-2xl transition-all shadow-lg shadow-blue-500/20 active:scale-95 text-xs uppercase tracking-[0.2em] mt-4">
          {{ authStore.loading ? i18nStore.t('auth.creating').toUpperCase() : i18nStore.t('auth.create_now').toUpperCase() }}
        </button>
      </form>

      <div class="mt-8 text-center text-xs font-bold uppercase tracking-widest relative z-10">
        <span class="text-slate-500">{{ i18nStore.t('auth.already_have_account') }} </span>
        <router-link to="/login" class="text-blue-600 hover:underline ml-1">{{ i18nStore.t('auth.login_now') }}</router-link>
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

const form = ref({ name: '', email: '', phone: '', address: '', password: '', password_confirmation: '' })
const errors = ref({})
const showPassword = ref(false)

async function handleRegister() {
  errors.value = {}
  let hasError = false
  if (!form.value.name?.trim()) { errors.value.name = [i18nStore.t('auth.name_error')]; hasError = true }
  if (!form.value.email?.trim()) { errors.value.email = [i18nStore.t('auth.email_error')]; hasError = true }
  
  if (!form.value.phone?.trim()) { 
    errors.value.phone = [i18nStore.t('auth.phone_error') || 'Vui lòng nhập số điện thoại']; 
    hasError = true 
  } else if (!/^0[0-9]{9}$/.test(form.value.phone.trim())) {
    errors.value.phone = [i18nStore.t('auth.phone_error') || 'Vui lòng nhập số điện thoại hợp lệ (10 chữ số)']; 
    hasError = true 
  }

  if (!form.value.address?.trim()) { errors.value.address = [i18nStore.t('auth.address_error')]; hasError = true }
  if (!form.value.password?.trim()) { errors.value.password = [i18nStore.t('auth.password_error')]; hasError = true }
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = [i18nStore.t('auth.password_confirmation_error')]
    hasError = true
  }
  if (hasError) return

  const result = await authStore.register(form.value.name, form.value.email, form.value.address, form.value.phone, form.value.password, form.value.password_confirmation)
  if (result.success) {
    toast.success(i18nStore.t('auth.register_success'))
    router.push('/login')
  } else {
    if (result.errors) errors.value = result.errors
    else toast.error(result.message || 'Error')
  }
}
</script>

<style scoped>
.form-error-label { color: #ef4444; font-size: 11px; font-weight: 700; margin-top: 4px; }
.input-error { border-color: #ef4444 !important; }

.auth-back-btn {
  position: absolute; top: 20px; left: 20px; width: 44px; height: 44px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border-radius: 14px; color: #64748b;
  border: 1px solid #e2e8f0; cursor: pointer; transition: 0.2s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 50;
}
.auth-back-btn:hover { color: #1e293b; border-color: #cbd5e1; transform: translateX(-4px); }

@media (max-width: 768px) {
  .auth-back-btn { top: 12px; left: 12px; width: 38px; height: 38px; }
}
</style>
