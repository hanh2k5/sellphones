<template>
  <div class="flex-1 flex items-center justify-center py-12 md:py-20 px-4 relative z-10 bg-[#f9f9f9]">
    <!-- Back Button -->
    <button @click="router.push('/login')" class="auth-back-btn">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
    </button>

    <div class="backdrop-blur-2xl bg-white/60 border border-white/80 rounded-[2.5rem] md:rounded-[3rem] shadow-[0_8px_30px_rgba(0,0,0,0.06)] w-full max-w-md p-6 md:p-10 relative overflow-hidden">
      <!-- Glow effect behind form -->
      <div class="absolute -top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-blue-300/20 blur-[80px] pointer-events-none"></div>
      <div class="absolute -bottom-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-indigo-300/20 blur-[80px] pointer-events-none"></div>

      <div class="text-center mb-10 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">Quên mật khẩu</p>
      </div>

      <div v-if="!sent" class="relative z-10">
        <p class="text-[14px] text-slate-500 mb-6 text-center font-semibold">Nhập email tài khoản để nhận link đặt lại mật khẩu</p>
        <form @submit.prevent="handleSubmit" novalidate class="space-y-6">
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">Email khôi phục</label>
            <input v-model="email" type="email" placeholder="email@example.com" 
              @input="errors && (errors.email = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400" 
              :class="{'input-error': errors?.email}" />
            <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
          </div>
          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Đang xử lý...' : 'Xác nhận ➔' }}
          </button>
        </form>
      </div>

      <div v-else class="text-center py-6 relative z-10">
        <div class="w-20 h-20 bg-blue-100/80 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner border border-blue-200/50">📧</div>
        <h3 class="font-bold text-slate-800 text-xl mb-3">Yêu cầu đã được gửi!</h3>
        <p class="text-slate-500 text-[14px] mb-6 leading-relaxed font-semibold">Chúng tôi đã tạo liên kết đặt lại mật khẩu cho email <strong class="text-slate-700 font-bold">{{ email }}</strong>.</p>
        
        <!-- Mock Reset Link for Demo -->
        <div class="mb-8 p-4 bg-amber-50/80 border border-amber-200/50 rounded-2xl text-left shadow-sm">
          <p class="text-[11px] text-amber-800 font-bold mb-2 uppercase tracking-wider">💡 Chế độ giả lập (Đồ án):</p>
          <p class="text-[13px] text-amber-700 mb-4 font-medium leading-relaxed">Hệ thống không tích hợp gửi email thực tế. Vui lòng click trực tiếp vào link dưới đây để đổi mật khẩu:</p>
          <router-link :to="`/reset-password?token=${resetToken}&email=${encodeURIComponent(email)}`" class="text-blue-600 hover:text-blue-700 font-bold break-all text-xs underline block p-2 bg-white/80 border border-slate-200/50 rounded-xl">
            Đặt lại mật khẩu tại đây
          </router-link>
        </div>

        <router-link to="/login" class="inline-block bg-white/80 border border-slate-200 shadow-sm hover:shadow-md text-blue-600 font-bold py-3.5 px-6 rounded-xl hover:bg-white transition-all text-xs uppercase tracking-wider">
          ← Quay lại đăng nhập
        </router-link>
      </div>

      <p v-if="!sent" class="text-center text-xs font-bold mt-8 relative z-10 uppercase tracking-widest">
        <router-link to="/login" class="text-slate-500 hover:text-blue-600 transition-colors">← Quay lại đăng nhập</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const email = ref('')
const sent = ref(false)
const loading = ref(false)
const errors = ref({})
const resetToken = ref('')

async function handleSubmit() {
  loading.value = true
  errors.value = {}
  
  if (!email.value?.trim()) {
    errors.value.email = ['Vui lòng nhập email']
    loading.value = false
    return
  }

  // Basic regex check
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.value.trim())) {
    errors.value.email = ['Vui lòng nhập email đúng định dạng']
    loading.value = false
    return
  }

  try {
    const response = await api.post('/forgot-password', { email: email.value.trim() })
    resetToken.value = response.data?.reset_token || ''
    sent.value = true
  } catch (e) {
    const msg = e.response?.data?.message || 'Email không tồn tại trong hệ thống.'
    errors.value.email = [msg]
  } finally {
    loading.value = false
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
