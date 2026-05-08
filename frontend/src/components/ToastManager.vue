<template>
  <Teleport to="body">
    <div class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none" style="max-width: 380px;">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-center gap-4 px-5 py-4 rounded-2xl border bg-white/95 backdrop-blur-3xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] text-[15px] font-semibold transition-all hover:scale-[1.02] duration-300"
          :class="toastClass(toast.type)"
          @click="remove(toast.id)"
        >
          <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-white shadow-md" :class="iconBgClass(toast.type)" v-html="toastIcon(toast.type)"></div>
          <span class="flex-1 leading-relaxed text-slate-700">{{ toast.message }}</span>
          <button class="opacity-30 hover:opacity-100 transition-opacity flex-shrink-0 text-slate-400 p-1 hover:bg-slate-100 rounded-lg ml-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast'

const { toasts, remove } = useToast()

function toastClass(type) {
  return {
    success: 'border-emerald-100',
    error:   'border-rose-100',
    warning: 'border-amber-100',
    info:    'border-blue-100',
  }[type] || 'border-slate-100'
}

function iconBgClass(type) {
  return {
    success: 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/30',
    error:   'bg-gradient-to-br from-rose-400 to-rose-600 shadow-rose-500/30',
    warning: 'bg-gradient-to-br from-amber-400 to-amber-600 shadow-amber-500/30',
    info:    'bg-gradient-to-br from-blue-400 to-blue-600 shadow-blue-500/30',
  }[type] || 'bg-gradient-to-br from-slate-400 to-slate-600'
}

function toastIcon(type) {
  const icons = {
    success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`,
    error:   `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`,
    warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    info:    `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
  }
  return icons[type] || ''
}
</script>

<style scoped>
.toast-enter-active {
  animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
  animation: slideOut 0.25s ease-in forwards;
}
.toast-move {
  transition: transform 0.3s ease;
}
@keyframes slideIn {
  from { opacity: 0; transform: translateX(100%) scale(0.9); }
  to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes slideOut {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to   { opacity: 0; transform: translateX(100%) scale(0.9); }
}
</style>
