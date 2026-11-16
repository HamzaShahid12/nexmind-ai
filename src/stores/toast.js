import { defineStore } from 'pinia'

let nextId = 1

export const useToastStore = defineStore('toast', {
  state: () => ({ items: [] }),
  actions: {
    push(type, message, timeout = 4000) {
      const id = nextId++
      this.items.push({ id, type, message })
      if (timeout) setTimeout(() => this.remove(id), timeout)
    },
    success(message) { this.push('success', message) },
    error(message) { this.push('error', message, 6000) },
    remove(id) { this.items = this.items.filter((t) => t.id !== id) },
  },
})
