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

      <div class="text-center mb-8 relative z-10">
        <h1 class="text-4xl font-bold tracking-tight text-slate-800">
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Sell</span>phones
        </h1>
        <p class="text-slate-500 mt-2 font-medium">{{ t('auth.forgot_page_title') }}</p>
      </div>

      <!-- BƯỚC 1: Nhập email -->
      <div v-if="step === 1" class="relative z-10">
        <p class="text-[14px] text-slate-500 mb-6 text-center font-semibold">
          {{ t('auth.forgot_subtitle') }}
        </p>
        <form @submit.prevent="handleSendOtp" novalidate class="space-y-6">
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ t('auth.forgot_email_label') }}</label>
            <input v-model="email" type="email" placeholder="email@example.com"
              @input="errors && (errors.email = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.email}" />
            <p v-if="errors?.email" class="form-error-label">{{ errors.email[0] }}</p>
          </div>
          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? t('auth.forgot_sending') : t('auth.forgot_send_otp') }}
          </button>
        </form>
      </div>

      <!-- BƯỚC 2: Nhập OTP + mật khẩu mới -->
      <div v-else-if="step === 2" class="relative z-10">
        <!-- Email badge -->
        <div class="flex items-center gap-2 bg-blue-50/80 border border-blue-100 rounded-xl px-4 py-2.5 mb-6">
          <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          <span class="text-[13px] text-blue-700 font-semibold truncate">{{ t('auth.forgot_otp_sent') }} <strong>{{ email }}</strong></span>
        </div>

        <form @submit.prevent="handleResetPassword" novalidate class="space-y-5">
          <!-- OTP 6 ô -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-3 uppercase tracking-[0.2em]">{{ t('auth.forgot_otp_label') }}</label>
            <div class="flex gap-2 justify-center" @paste.prevent="handlePaste">
              <input
                v-for="(_, idx) in otpDigits"
                :key="idx"
                :ref="el => otpInputs[idx] = el"
                v-model="otpDigits[idx]"
                type="text"
                inputmode="numeric"
                maxlength="1"
                @keydown="handleOtpKeydown($event, idx)"
                @input="handleOtpInput($event, idx)"
                class="otp-input"
                :class="{'otp-input-error': errors?.otp}"
              />
            </div>
            <p v-if="errors?.otp" class="form-error-label text-center mt-2">{{ errors.otp[0] }}</p>
          </div>

          <!-- Mật khẩu mới -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ t('auth.forgot_new_password') }}</label>
            <div class="relative">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                @keydown="blockSpace"
                @paste="handlePasswordPaste($event, 'password')"
                @input="errors && (errors.password = null)"
                class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 pr-12 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
                :class="{'input-error': errors?.password}" />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
              </button>
            </div>
            <p v-if="errors?.password" class="form-error-label">{{ errors.password[0] }}</p>
          </div>

          <!-- Xác nhận mật khẩu -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-[0.2em]">{{ t('auth.forgot_confirm_password') }}</label>
            <input v-model="passwordConfirmation" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
              @keydown="blockSpace"
              @paste="handlePasswordPaste($event, 'passwordConfirmation')"
              @input="errors && (errors.passwordConfirmation = null)"
              class="w-full bg-white/80 border border-slate-200/50 shadow-sm rounded-2xl px-5 py-4 text-[15px] font-semibold text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
              :class="{'input-error': errors?.passwordConfirmation}" />
            <p v-if="errors?.passwordConfirmation" class="form-error-label">{{ errors.passwordConfirmation[0] }}</p>
          </div>

          <!-- Countdown + Resend -->
          <div class="flex items-center justify-between text-[12px]">
            <span v-if="countdown > 0" class="text-slate-400 font-medium">
              ⏱ {{ t('auth.forgot_resend_after') }} <span class="text-blue-600 font-bold">{{ countdown }}s</span>
            </span>
            <button v-else type="button" @click="handleSendOtp" :disabled="resendLoading" class="text-blue-600 hover:text-blue-700 font-bold underline underline-offset-2 disabled:opacity-50">
              {{ resendLoading ? t('auth.forgot_resending') : t('auth.forgot_resend_btn') }}
            </button>
            <button type="button" @click="step = 1" class="text-slate-400 hover:text-slate-600 font-semibold">
              {{ t('auth.forgot_change_email') }}
            </button>
          </div>

          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:grayscale text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? t('common.processing') : t('auth.forgot_submit') }}
          </button>
        </form>
      </div>

      <!-- BƯỚC 3: Thành công -->
      <div v-else class="text-center py-6 relative z-10">
        <div class="w-20 h-20 bg-emerald-100/80 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner border border-emerald-200/50">✅</div>
        <h3 class="font-bold text-slate-800 text-xl mb-3">{{ t('auth.forgot_success_title') }}</h3>
        <p class="text-slate-500 text-[14px] mb-8 leading-relaxed font-semibold">
          {{ t('auth.forgot_success_desc') }}
        </p>
        <router-link to="/login" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-blue-500/20 transition-all text-xs uppercase tracking-wider">
          {{ t('auth.forgot_login_now') }}
        </router-link>
      </div>

      <p v-if="step === 1" class="text-center text-xs font-bold mt-8 relative z-10 uppercase tracking-widest">
        <router-link to="/login" class="text-slate-500 hover:text-blue-600 transition-colors">{{ t('auth.forgot_back_login') }}</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'
import { authApi } from '../api'

const router = useRouter()
const i18nStore = useI18nStore()
const toast = useToast()
const t = (key) => i18nStore.t(key)

// --- State ---
const step = ref(1)
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const loading = ref(false)
const resendLoading = ref(false)
const errors = ref({})
const countdown = ref(0)

// OTP 6 ô
const otpDigits = reactive(['', '', '', '', '', ''])
const otpInputs = ref([])

let countdownTimer = null

// --- Gửi OTP ---
async function handleSendOtp() {
  errors.value = {}

  if (!email.value?.trim()) {
    errors.value.email = [t('auth.forgot_email_required')]
    return
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.value.trim())) {
    errors.value.email = [t('auth.forgot_email_invalid')]
    return
  }

  if (step.value === 1) loading.value = true
  else resendLoading.value = true

  try {
    await authApi.forgotPassword(email.value.trim())
    step.value = 2
    startCountdown(60)
    await nextTick()
    otpInputs.value[0]?.focus()
  } catch (e) {
    const msg = e.response?.data?.message || t('auth.forgot_email_invalid')
    errors.value.email = [msg]
    if (step.value === 2) step.value = 1
  } finally {
    loading.value = false
    resendLoading.value = false
  }
}

// --- OTP input handlers ---
function handleOtpInput(event, idx) {
  const val = event.target.value.replace(/\D/g, '')
  otpDigits[idx] = val.slice(-1)
  if (val && idx < 5) {
    nextTick(() => otpInputs.value[idx + 1]?.focus())
  }
  errors.value.otp = null
}

function handleOtpKeydown(event, idx) {
  if (event.key === 'Backspace') {
    if (!otpDigits[idx] && idx > 0) {
      otpDigits[idx - 1] = ''
      nextTick(() => otpInputs.value[idx - 1]?.focus())
    }
  } else if (event.key === 'ArrowLeft' && idx > 0) {
    nextTick(() => otpInputs.value[idx - 1]?.focus())
  } else if (event.key === 'ArrowRight' && idx < 5) {
    nextTick(() => otpInputs.value[idx + 1]?.focus())
  }
}

function handlePaste(event) {
  const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6)
  text.split('').forEach((ch, i) => { otpDigits[i] = ch })
  nextTick(() => otpInputs.value[Math.min(text.length, 5)]?.focus())
}

// --- Chặn khoảng trắng trong ô mật khẩu ---
function blockSpace(event) {
  if (event.key === ' ') event.preventDefault()
}

function handlePasswordPaste(event, field) {
  event.preventDefault()
  const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\s/g, '')
  if (field === 'password') password.value = text
  else passwordConfirmation.value = text
}

// --- Đặt lại mật khẩu ---
async function handleResetPassword() {
  errors.value = {}
  const otp = otpDigits.join('')

  let hasError = false
  if (otp.length !== 6) {
    errors.value.otp = [t('auth.forgot_otp_required')]
    hasError = true
  }
  if (!password.value) {
    errors.value.password = [t('auth.new_password_required')]
    hasError = true
  } else if (/\s/.test(password.value)) {
    errors.value.password = [t('auth.forgot_password_no_space')]
    hasError = true
  } else if (password.value.length < 8) {
    errors.value.password = [t('auth.forgot_password_min')]
    hasError = true
  }
  if (!passwordConfirmation.value) {
    errors.value.passwordConfirmation = [t('auth.forgot_confirm_required')]
    hasError = true
  } else if (password.value !== passwordConfirmation.value) {
    errors.value.passwordConfirmation = [t('auth.forgot_confirm_mismatch')]
    hasError = true
  }
  if (hasError) return

  loading.value = true
  try {
    const res = await authApi.resetPassword({
      email: email.value.trim(),
      otp,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    clearCountdown()
    toast.success(res.data?.message || 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.')
    step.value = 3
    setTimeout(() => {
      router.push('/login')
    }, 1500)
  } catch (e) {
    const msg = e.response?.data?.message || t('common.error')
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    } else {
      errors.value.otp = [msg]
    }
  } finally {
    loading.value = false
  }
}

// --- Countdown ---
function startCountdown(seconds) {
  clearCountdown()
  countdown.value = seconds
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearCountdown()
  }, 1000)
}

function clearCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  countdown.value = 0
}

onUnmounted(clearCountdown)
</script>

<style scoped>
.form-error-label { color: #ef4444; font-size: 11px; font-weight: 700; margin-top: 4px; display: block; }
.input-error { border-color: #ef4444 !important; }

.auth-back-btn {
  position: absolute; top: 20px; left: 20px; width: 44px; height: 44px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border-radius: 14px; color: #64748b;
  border: 1px solid #e2e8f0; cursor: pointer; transition: 0.2s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 50;
}
.auth-back-btn:hover { color: #1e293b; border-color: #cbd5e1; transform: translateX(-4px); }

/* OTP Input Boxes */
.otp-input {
  width: 48px; height: 56px;
  text-align: center;
  font-size: 22px; font-weight: 800; color: #1e293b;
  background: rgba(255,255,255,0.85);
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  outline: none;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  caret-color: #2563eb;
}
.otp-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
  transform: translateY(-2px);
  background: #fff;
}
.otp-input:not(:placeholder-shown) {
  border-color: #2563eb;
  background: linear-gradient(135deg, #eff6ff, #eef2ff);
  color: #2563eb;
}
.otp-input-error { border-color: #ef4444 !important; }

@media (max-width: 768px) {
  .auth-back-btn { top: 12px; left: 12px; width: 38px; height: 38px; }
  .otp-input { width: 40px; height: 50px; font-size: 18px; border-radius: 10px; }
}
</style>
