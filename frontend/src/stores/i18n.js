import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import vi from '../locales/vi.json'
import en from '../locales/en.json'

export const useI18nStore = defineStore('i18n', () => {
  const locale = ref(localStorage.getItem('locale') || 'vi')
  
  const messages = { vi, en }

  const t = (key, params = {}) => {
    if (!key) return ''
    const keys = key.split('.')
    let result = messages[locale.value]
    
    for (const k of keys) {
      if (result && result[k] !== undefined) {
        result = result[k]
      } else {
        result = undefined
        break
      }
    }

    if (result === undefined) return key

    if (typeof result === 'string' && Object.keys(params).length > 0) {
      result = result.replace(/\{(\w+)\}/g, (_, k) => params[k] !== undefined ? params[k] : `{${k}}`)
    }

    return result
  }

  function setLocale(newLocale) {
    locale.value = newLocale
    localStorage.setItem('locale', newLocale)
    document.documentElement.lang = newLocale
  }

  function toggleLocale() {
    const next = locale.value === 'vi' ? 'en' : 'vi'
    setLocale(next)
  }

  const isVietnamese = computed(() => locale.value === 'vi')

  function transName(name) {
    if (!name) return ''
    const key = `admin.categories.data.${name}`
    const translated = t(key)
    return translated !== key ? translated : name
  }

  return { locale, isVietnamese, setLocale, toggleLocale, t, transName }
})
