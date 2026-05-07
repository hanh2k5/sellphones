import { useI18nStore } from '../stores/i18n'
import { getApiBaseUrl } from '../utils/url'

export function useUtils() {
  const i18n = useI18nStore()

  function fmtPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price || 0) + 'đ'
  }

  function fmtDate(date, includeTime = false) {
    if (!date) return ''
    const d = new Date(date)
    
    if (isNaN(d.getTime())) return date

    const options = includeTime 
      ? { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }
      : { year: 'numeric', month: '2-digit', day: '2-digit' }

    return d.toLocaleString(i18n.locale === 'vi' ? 'vi-VN' : 'en-US', options)
  }

  function getImageUrl(url) {
    if (!url) return 'https://placehold.co/400x400'
    if (url.startsWith('http') || url.startsWith('blob:')) return url
    
    const baseUrl = getApiBaseUrl()
    let cleanUrl = url.startsWith('/') ? url : `/${url}`
    
    // Nếu là đường dẫn cục bộ (không phải http) và chưa có /storage/, thì thêm vào
    if (!cleanUrl.startsWith('/storage/') && !cleanUrl.startsWith('http')) {
        cleanUrl = `/storage${cleanUrl}`
    }
    
    return `${baseUrl}${cleanUrl}`
  }

  return { fmtPrice, fmtDate, getImageUrl }
}
