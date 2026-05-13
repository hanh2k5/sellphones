import api from '../services/api'

export const chatApi = {
  getHistory: () => api.get('/ai/chats'),
  sendMessage: (message) => api.post('/ai/chats', { message }),
  clearHistory: () => api.delete('/ai/chats'),
}
