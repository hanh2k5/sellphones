import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
  function add(message, type = 'info', duration = 3500) {
    const id = ++nextId
    toasts.value.push({ id, message, type })
    setTimeout(() => remove(id), duration)
    return id
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  function success(msg, duration) { return add(msg, 'success', duration) }
  function error(msg, duration) { return add(msg, 'error', duration) }
  function warning(msg, duration) { return add(msg, 'warning', duration) }
  function info(msg, duration) { return add(msg, 'info', duration) }

  return { toasts, add, remove, success, error, warning, info }
}
