<template>
  <div class="flex-1 flex items-center justify-center py-12 md:py-20 px-4 relative z-10 bg-[#f9f9f9]">
    <div class="backdrop-blur-2xl bg-white/60 border border-white/80 rounded-[2.5rem] md:rounded-[3rem] shadow-[0_8px_30px_rgba(0,0,0,0.06)] w-full max-w-md p-6 md:p-10 relative overflow-hidden">
      <!-- Glow effect -->
      <div class="absolute -top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-blue-300/20 blur-[80px] pointer-events-none"></div>
      <div class="absolute -bottom-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-indigo-300/20 blur-[80px] pointer-events-none"></div>

      <div class="text-center mb-10 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">Đặt lại mật khẩu</p>
      </div>

      <div v-if="!success" class="relative z-10">
        <form @submit.prevent="handleSubmit" novalidate class="space-y-6">
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">Mật khẩu mới</label>
            <input v-model="password" type="password" placeholder="••••••••" 
              @input="errors && (errors.password = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400" 
              :class="{'input-error': errors?.password}" />
            <p v-if="errors?.password" class="form-error-label">{{ errors.password[0] }}</p>
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">Xác nhận mật khẩu</label>
            <input v-model="password_confirmation" type="password" placeholder="••••••••" 
              @input="errors && (errors.password_confirmation = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400" 
              :class="{'input-error': errors?.password_confirmation}" />
            <p v-if="errors?.password_confirmation" class="form-error-label">{{ errors.password_confirmation[0] }}</p>
          </div>
          
          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Đang xử lý...' : 'Cập nhật mật khẩu ➔' }}
          </button>
        </form>
      </div>

      <div v-else class="text-center py-6 relative z-10">
        <div class="w-20 h-20 bg-emerald-100/80 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner border border-emerald-200/50">✅</div>
        <h3 class="font-bold text-slate-800 text-xl mb-3">Thành công!</h3>
        <p class="text-slate-500 text-[14px] mb-8 leading-relaxed font-semibold">Mật khẩu của bạn đã được thay đổi thành công. Bạn có thể đăng nhập ngay bây giờ.</p>
        <router-link to="/login" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-blue-500/20 transition-all text-xs uppercase tracking-wider">
          Đăng nhập ngay
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const password = ref('')
const password_confirmation = ref('')
const loading = ref(false)
const errors = ref({})
const success = ref(false)

const token = route.query.token
const email = route.query.email

onMounted(() => {
  if (!token || !email) {
    toast.error('Token hoặc Email không hợp lệ!')
    router.push('/login')
  }
})

async function handleSubmit() {
  errors.value = {}
  let hasError = false
  
  if (!password.value) {
    errors.value.password = ['Vui lòng nhập mật khẩu mới']
    hasError = true
  } else if (password.value.length < 8) {
    errors.value.password = ['Mật khẩu mới phải có ít nhất 8 ký tự.']
    hasError = true
  }

  if (!password_confirmation.value) {
    errors.value.password_confirmation = ['Vui lòng xác nhận mật khẩu mới']
    hasError = true
  } else if (password.value !== password_confirmation.value) {
    errors.value.password_confirmation = ['Mật khẩu xác nhận không khớp.']
    hasError = true
  }

  if (hasError) return

  loading.value = true
  try {
    await api.post('/reset-password', {
      token,
      email,
      password: password.value,
      password_confirmation: password_confirmation.value
    })
    success.value = true
    toast.success('Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.')
  } catch (e) {
    const msg = e.response?.data?.message || 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    } else {
      errors.value.password = [msg]
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.form-error-label { color: #ef4444; font-size: 11px; font-weight: 700; margin-top: 4px; }
.input-error { border-color: #ef4444 !important; }
</style>
