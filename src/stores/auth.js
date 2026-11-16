import { defineStore } from 'pinia'
import api, { csrf, parseError } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, ready: false }),
  getters: {
    isAuthenticated: (s) => !!s.user,
    isAdmin: (s) => (s.user?.roles || []).some((r) => ['admin', 'super_admin'].includes(r)),
  },
  actions: {
    /** Load the current session user once at startup. */
    async fetchUser() {
      try {
        const { data } = await api.get('/user')
        this.user = data.data ?? data
      } catch {
        this.user = null
      } finally {
        this.ready = true
      }
    },
    async login(payload) {
      await csrf()
      await api.post('/login', payload)
      await this.fetchUser()
    },
    async register(payload) {
      await csrf()
      await api.post('/register', payload)
      await this.fetchUser()
    },
    async logout() {
      try { await api.post('/logout') } finally { this.user = null }
    },
  },
})

export { parseError }
