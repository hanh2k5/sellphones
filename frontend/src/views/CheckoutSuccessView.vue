<template>
  <div class="min-h-screen bg-[#f3f4f6] flex items-center justify-center p-4 md:p-8 font-['Inter',sans-serif]">
    <div class="w-full max-w-[450px] bg-white rounded-[2.5rem] p-8 md:p-10 shadow-2xl shadow-slate-200/50 text-center animate-fade-in border border-slate-100 relative overflow-hidden">
      
      <!-- Decorative background blur -->
      <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Icon -->
      <div class="flex justify-center mb-6">
        <div class="w-24 h-24 bg-emerald-600 rounded-full flex items-center justify-center shadow-lg shadow-emerald-600/20">
          <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </div>

      <!-- Title -->
      <h1 class="text-3xl font-black text-slate-900 mb-2">{{ i18n.t('checkout.success_title') || 'Đặt hàng thành công!' }}</h1>
      <p class="text-slate-500 text-lg mb-8">
        {{ i18n.t('order.id') || 'Mã vận đơn:' }} 
        <span class="font-bold text-slate-900">#{{ orderCode }}</span>
      </p>

      <!-- Info Banner -->
      <div class="bg-cyan-50/80 border border-cyan-100 rounded-2xl p-5 flex items-center gap-4 text-left mb-8 shadow-sm">
        <div class="w-10 h-10 bg-cyan-600/10 text-cyan-700 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
        </div>
        <p class="text-sm font-medium text-cyan-800 leading-relaxed">
          {{ i18n.locale === 'vi' ? 'Đơn hàng đang được chuẩn bị và sẽ sớm giao đến bạn.' : 'Your order is being prepared and will be delivered to you soon.' }}
        </p>
      </div>

      <!-- Action Button -->
      <router-link 
        to="/" 
        class="block w-full bg-slate-900 hover:bg-slate-800 !text-white font-bold py-4 rounded-2xl uppercase tracking-widest text-[13px] transition-all active:scale-95 shadow-xl shadow-slate-900/10"
      >
        {{ i18n.locale === 'vi' ? 'TIẾP TỤC MUA SẮM' : 'CONTINUE SHOPPING' }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18nStore } from '../stores/i18n'

const route = useRoute()
const i18n = useI18nStore()
const orderCode = computed(() => route.query.order_code || '...')
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { 
  from { opacity: 0; transform: translateY(20px) scale(0.95); } 
  to { opacity: 1; transform: translateY(0) scale(1); } 
}
</style>
