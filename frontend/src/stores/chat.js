import { ref } from 'vue'
import { defineStore } from 'pinia'
import { chatApi } from '../api'
import { useCartStore } from './cart'

/**
 * [Phan Đình Hạnh - 4.1.11 & 4.1.12] Store quản lý hội thoại và Agent giỏ hàng
 */
export const useChatStore = defineStore('chat', () => {
  const messages = ref([])
  const loading = ref(false)
  const cartStore = useCartStore()

  async function fetchHistory() {
    try {
      const res = await chatApi.getHistory()
      messages.value = res.data
    } catch (e) {}
  }

  async function sendMessage(text) {
    if (!text.trim()) return
    const userMsg = { id: Date.now(), role: 'user', message_content: text, created_at: new Date().toISOString() }
    messages.value.push(userMsg)
    loading.value = true
    try {
      const res = await chatApi.sendMessage(text)
      messages.value.push(res.data)

      // Xử lý đồng bộ giỏ hàng khi AI thêm đồ (4.1.12 STT 4)
      if (res.data.action === 'cart_updated') {
        cartStore.fetchCart()
      }
      return res.data; // QUAN TRỌNG: Trả về để Component biết mà hiện thông báo
    } catch (e) {
      console.error("AI Chat Error:", e)
      messages.value.push({ 
        id: Date.now()+1, 
        role: 'assistant', 
        message_content: "Rất tiếc, tôi đang gặp khó khăn khi kết nối. Bạn vui lòng thử lại sau nhé!", 
        created_at: new Date().toISOString() 
      })
      return null;
    } finally {
      loading.value = false
    }
  }

  /** Xóa lịch sử */
  async function clearHistory() {
    try {
      await chatApi.clearHistory()
      messages.value = []
    } catch (e) {
      console.error("Clear history failed", e)
    }
  }

  return { messages, loading, fetchHistory, sendMessage, clearHistory }
})
