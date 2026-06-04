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
        <p class="text-slate-500 mt-2 font-medium">{{ i18nStore.t('auth.reset_title') }}</p>
      </div>

      <div v-if="!success" class="relative z-10">
        <p class="text-[13px] text-slate-500 mb-6 text-center font-medium">
          {{ i18nStore.t('auth.reset_instruction') }}
          <router-link to="/forgot-password" class="text-blue-600 font-bold hover:underline">{{ i18nStore.t('auth.forgot_page_title') }}</router-link>
          {{ i18nStore.t('auth.reset_instruction_suffix') }}
        </p>
        <router-link to="/forgot-password" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-5 rounded-2xl transition-all duration-300 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 active:scale-95 text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2">
          {{ i18nStore.t('auth.reset_go_to_forgot') }}
        </router-link>
      </div>

      <div v-else class="text-center py-6 relative z-10">
        <div class="w-20 h-20 bg-emerald-100/80 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner border border-emerald-200/50">✅</div>
        <h3 class="font-bold text-slate-800 text-xl mb-3">{{ i18nStore.t('auth.reset_password_success') }}</h3>
        <p class="text-slate-500 text-[14px] mb-8 leading-relaxed font-semibold">{{ i18nStore.t('auth.reset_success_desc') }}</p>
        <router-link to="/login" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-blue-500/20 transition-all text-xs uppercase tracking-wider">
          {{ i18nStore.t('auth.forgot_login_now') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18nStore } from '../stores/i18n'

const i18nStore = useI18nStore()
const success = ref(false)
</script>

<style scoped>
</style>
