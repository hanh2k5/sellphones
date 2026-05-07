import { onMounted, onUnmounted } from 'vue'

export function useProtection() {
  const handleKeyDown = (e) => {
    // Disable F12
    if (e.keyCode === 123) {
      e.preventDefault()
      return false
    }
    // Disable Ctrl+Shift+I, Ctrl+Shift+C, Ctrl+Shift+J
    if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 67 || e.keyCode === 74)) {
      e.preventDefault()
      return false
    }
    // Disable Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode === 85) {
      e.preventDefault()
      return false
    }
  }

  const handleContextMenu = (e) => {
    e.preventDefault()
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
    window.addEventListener('contextmenu', handleContextMenu)
    
    // Console anti-debug message
    console.log('%cDừng lại!', 'color: red; font-size: 50px; font-weight: bold; -webkit-text-stroke: 1px black;')
    console.log('%cĐây là tính năng trình duyệt dành cho các nhà phát triển. Nếu ai đó bảo bạn sao chép-dán nội dung nào đó vào đây để kích hoạt tính năng hoặc "hack" tài khoản của người khác, thì đó là một trò lừa đảo và sẽ cấp cho họ quyền truy cập vào tài khoản của bạn.', 'font-size: 20px;')
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('contextmenu', handleContextMenu)
  })
}
