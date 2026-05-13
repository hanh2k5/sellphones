import { ref } from 'vue'
import { defineStore } from 'pinia'
import { chatApi } from '../api'

/**
 * [Phan Đình Hạnh - 4.1.11 STT 4] Store quản lý hội thoại và trạng thái AI
 */
export const useChatStore = defineStore('chat', () => {
  const messages = ref([])
  const loading = ref(false)

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
    } catch (e) {
      console.error("AI Chat Error:", e)
      messages.value.push({ 
        id: Date.now()+1, 
        role: 'assistant', 
        message_content: "Rất tiếc, tôi đang gặp khó khăn khi kết nối. Bạn vui lòng thử lại sau nhé!", 
        created_at: new Date().toISOString() 
      })
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
