import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
  function add(message, type = 'info', duration = 3500, action = null) {
    const id = ++nextId
    toasts.value.push({ id, message, type, action })
    setTimeout(() => remove(id), duration)
    return id
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  function success(msg, action = null, duration) { return add(msg, 'success', duration, action) }
  function error(msg, action = null, duration) { return add(msg, 'error', duration, action) }
  function warning(msg, action = null, duration) { return add(msg, 'warning', duration, action) }
  function info(msg, action = null, duration) { return add(msg, 'info', duration, action) }

  return { toasts, add, remove, success, error, warning, info }
}
