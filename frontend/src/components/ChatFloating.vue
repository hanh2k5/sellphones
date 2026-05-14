<template>
  <div class="fixed bottom-24 right-4 md:bottom-6 md:right-6 z-[9999] font-sans">
    <!-- Bubble Button -->
    <button 
      @click="toggleChat" 
      class="w-12 h-12 md:w-16 md:h-16 bg-blue-600 text-white rounded-full shadow-[0_8px_25px_rgba(37,99,235,0.4)] flex items-center justify-center hover:scale-110 active:scale-95 transition-all group"
    >
      <svg v-if="!isOpen" class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
      <svg v-else class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
    </button>

    <!-- Chat Window -->
    <transition name="chat-slide">
      <div 
        v-if="isOpen" 
        class="absolute bottom-14 right-0 md:bottom-20 md:right-[-8px] w-[calc(100vw-32px)] sm:w-[380px] h-[450px] md:h-[550px] max-h-[calc(100vh-160px)] bg-white rounded-[1.5rem] md:rounded-[2rem] shadow-[0_20px_60px_rgba(0,0,0,0.25)] border border-slate-100 flex flex-col overflow-hidden origin-bottom-right"
      >
        
        <!-- Header: Light Blue Style -->
        <div class="bg-blue-600 p-4 md:p-5 text-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md text-xl md:text-2xl">
              🎧
            </div>
            <div>
              <h3 class="font-bold text-[15px] md:text-lg leading-tight">{{ i18n.t('chat.title') }}</h3>
              <p class="text-[10px] md:text-xs text-blue-100 font-medium flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                {{ i18n.t('chat.status') }}
              </p>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <!-- Nút Xóa Chat -->
            <button 
              @click="handleClear"
              class="px-3 py-1 bg-white/10 hover:bg-white/20 rounded-full text-[10px] md:text-xs font-bold transition-colors border border-white/20"
            >
              {{ i18n.t('chat.clear') }}
            </button>
          </div>
        </div>

        <!-- Messages Area: Auto scroll -->
        <div ref="msgContainer" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4 md:space-y-6 bg-[#f8fafc] scroll-smooth custom-scrollbar">
          <!-- Welcome Message -->
          <div class="flex justify-start">
            <div class="bg-white border border-slate-100 shadow-sm rounded-2xl rounded-tl-none p-3 md:p-4 text-[13px] md:text-sm text-slate-700 max-w-[90%] md:max-w-[85%] leading-relaxed">
              {{ i18n.t('chat.welcome') }}
            </div>
          </div>

          <div v-for="msg in chatStore.messages" :key="msg.id" :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="animate-message-in">
            <div 
              :class="msg.role === 'user' 
                ? 'bg-blue-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-blue-500/20' 
                : 'bg-white border border-slate-100 shadow-sm text-slate-800 rounded-2xl rounded-tl-none'" 
              class="max-w-[90%] md:max-w-[85%] p-3 md:p-4 text-[13px] md:text-sm font-medium leading-relaxed whitespace-pre-wrap break-words overflow-hidden"
            >
              {{ msg.message_content }}
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="chatStore.loading" class="flex justify-start animate-message-in">
            <div class="bg-white border border-slate-100 shadow-sm rounded-2xl rounded-tl-none p-3 flex gap-1">
              <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-bounce"></span>
              <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
              <span class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
            </div>
          </div>
        </div>

        <!-- Input Area: Large Touch Area for Mobile -->
        <div class="p-4 md:p-5 bg-white border-t border-slate-100 shrink-0">
          <div class="relative flex items-center gap-2">
            <input 
              v-model="inputText"
              @keyup.enter="handleSend"
              :disabled="chatStore.loading"
              type="text" 
              :placeholder="i18n.t('chat.placeholder')"
              class="flex-1 px-5 py-3 md:py-4 bg-slate-50 border border-slate-200 rounded-full text-[13px] md:text-sm focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400"
            />
            <button 
              @click="handleSend"
              :disabled="!inputText.trim() || chatStore.loading"
              class="w-10 h-10 md:w-12 md:h-12 bg-blue-600 text-white rounded-full flex items-center justify-center hover:scale-105 active:scale-95 disabled:opacity-30 transition-all shadow-lg shadow-blue-500/30 shrink-0"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M12 5l7 7-7 7" /></svg>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import { useChatStore } from '../stores/chat'
import { useAuthStore } from '../stores/auth'
import { useI18nStore } from '../stores/i18n'
import { useToast } from '../composables/useToast'

const chatStore = useChatStore()
const authStore = useAuthStore()
const i18n = useI18nStore()
const toast = useToast()
const isOpen = ref(false)
const inputText = ref('')
const msgContainer = ref(null)

const toggleChat = () => {
  if (!authStore.user) return alert(i18n.t('chat.login_required'))
  isOpen.value = !isOpen.value
  if (isOpen.value && chatStore.messages.length === 0) chatStore.fetchHistory()
}

const handleSend = async () => {
  if (!inputText.value.trim() || chatStore.loading) return
  const text = inputText.value
  inputText.value = ''
  
  // Gửi tin và nhận phản hồi
  const response = await chatStore.sendMessage(text)
  
  // Kiểm tra nếu AI đã thực hiện thêm vào giỏ hàng
  if (response && response.action === 'cart_updated') {
    // [Best Practice] Dùng Toast chuẩn của hệ thống để đồng bộ giao diện
    toast.success(i18n.locale === 'vi' 
      ? 'Ngọc (AI) đã thêm sản phẩm vào giỏ hàng cho bạn!' 
      : 'Ngọc (AI) added the product to your cart!', {
      label: i18n.t('product.view_cart'),
      url: '/cart'
    })
  }
  
  scrollToBottom()
}

const handleClear = async () => {
  if (confirm(i18n.t('chat.clear_confirm'))) {
    await chatStore.clearHistory()
  }
}

const scrollToBottom = () => { 
  nextTick(() => { 
    if (msgContainer.value) msgContainer.value.scrollTop = msgContainer.value.scrollHeight 
  }) 
}

watch(() => [chatStore.messages.length, chatStore.loading], scrollToBottom)

onMounted(() => { if (authStore.user) chatStore.fetchHistory() })
</script>

<style scoped>
.chat-slide-enter-active, .chat-slide-leave-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.chat-slide-enter-from, .chat-slide-leave-to { opacity: 0; transform: translateY(40px) scale(0.8); }

@keyframes messageIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.animate-message-in { animation: messageIn 0.4s ease-out forwards; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
