<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center space-y-8">
      <!-- 404 Illustration -->
      <div class="relative w-64 h-64 mx-auto mb-8 select-none">
        <div class="absolute inset-0 bg-blue-100 rounded-full blur-3xl opacity-50 animate-pulse"></div>
        <div class="relative flex items-center justify-center h-full">
          <span class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 drop-shadow-sm">
            404
          </span>
        </div>
      </div>

      <!-- Content -->
      <div class="space-y-4">
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
          {{ i18n.t('common.not_found_title') }}
        </h1>
        <p class="text-slate-500 text-lg">
          {{ i18n.t('common.not_found_desc') }}
        </p>
      </div>

      <!-- Action -->
      <div class="pt-8">
        <router-link
          to="/"
          class="inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-white bg-slate-900 rounded-full hover:bg-slate-800 focus:ring-4 focus:ring-slate-200 transition-all duration-200 ease-in-out shadow-sm hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
        >
          {{ i18n.t('common.back_home') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18nStore } from '../stores/i18n'

const i18n = useI18nStore()
</script>
