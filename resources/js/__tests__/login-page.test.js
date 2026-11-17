import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import LoginPage from '@/views/auth/LoginPage.vue'
import RegisterPage from '@/views/auth/RegisterPage.vue'

vi.mock('@/stores/auth', () => ({ useAuthStore: () => ({ login: vi.fn() }) }))
vi.mock('@/stores/toast', () => ({ useToastStore: () => ({ error: vi.fn() }) }))

async function mountPage(path, page) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', component: LoginPage },
      { path: '/register', component: RegisterPage },
      { path: '/forgot-password', component: { template: '<div />' } },
    ],
  })
  await router.push(path)
  await router.isReady()
  return mount(page, { global: { plugins: [router] } })
}

describe('LoginPage', () => {
  beforeEach(() => vi.clearAllMocks())

  it('lets the user show and hide their password', async () => {
    const wrapper = await mountPage('/login', LoginPage)

    expect(wrapper.get('#password').attributes('type')).toBe('password')
    await wrapper.get('button[aria-label="Show password"]').trigger('click')
    expect(wrapper.get('#password').attributes('type')).toBe('text')
    await wrapper.get('button[aria-label="Hide password"]').trigger('click')
    expect(wrapper.get('#password').attributes('type')).toBe('password')
  })

  it('shows a useful message when Google sign-in needs configuration', async () => {
    const wrapper = await mountPage('/login?google=not_configured', LoginPage)

    expect(wrapper.get('[role="alert"]').text()).toContain('Google sign-in is not set up yet')
    expect(wrapper.get('a[href="/auth/google/redirect"]').text()).toContain('Continue with Google')
  })

  it('offers Google signup on the registration page', async () => {
    const wrapper = await mountPage('/register', RegisterPage)

    expect(wrapper.get('a[href="/auth/google/redirect"]').text()).toContain('Sign up with Google')
  })
})
