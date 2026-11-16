import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import { routes } from '@/router'
import { useAuthStore } from '@/stores/auth'

// Re-creates the app router guards with a memory history so navigation can be tested.
function makeRouter() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.beforeEach(async (to) => {
    const store = useAuthStore()
    if (to.matched.some((r) => r.meta.requiresAuth) && !store.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } }
    if (to.matched.some((r) => r.meta.requiresAdmin) && !store.isAdmin) return { name: 'dashboard' }
    if (to.meta.guest && store.isAuthenticated) return { name: 'dashboard' }
  })
  return router
}

describe('route configuration', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('redirects guests from the dashboard to login and remembers the target', async () => {
    const router = makeRouter()
    await router.push('/dashboard/history')
    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/dashboard/history')
  })

  it('lets authenticated users in and bounces them away from login', async () => {
    useAuthStore().user = { name: 'A', roles: ['user'] }
    const router = makeRouter()
    await router.push('/dashboard')
    expect(router.currentRoute.value.name).toBe('dashboard')
    await router.push('/login')
    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('keeps regular users out of the admin area', async () => {
    useAuthStore().user = { name: 'A', roles: ['user'] }
    const router = makeRouter()
    await router.push('/admin/users')
    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('lets admins into the admin area', async () => {
    useAuthStore().user = { name: 'Admin', roles: ['admin'] }
    const router = makeRouter()
    await router.push('/admin/plans')
    expect(router.currentRoute.value.path).toBe('/admin/plans')
  })

  it('marks dashboard and admin as noindex', () => {
    const find = (p) => routes.find((r) => r.path === p)
    expect(find('/dashboard').meta.noindex).toBe(true)
    expect(find('/admin').meta.noindex).toBe(true)
  })

  it('defines every required admin and dashboard route', () => {
    const flat = (p) => routes.find((r) => r.path === p).children.map((c) => c.path)
    expect(flat('/admin')).toEqual(expect.arrayContaining(['dashboard', 'users', 'roles', 'ai-tools', 'ai-settings', 'plans', 'credits', 'generations', 'usage', 'transactions', 'blogs', 'categories', 'faqs', 'contact-messages', 'settings']))
    expect(flat('/dashboard')).toEqual(expect.arrayContaining(['ai-chat', 'content-writer', 'product-description', 'social-media', 'seo-assistant', 'email-generator', 'document-summarizer', 'history', 'saved-content', 'credits', 'settings', 'support']))
  })
})
