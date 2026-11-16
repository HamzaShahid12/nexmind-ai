import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

describe('parseError', () => {
  it('uses server message and field errors for 422', () => {
    const r = parseError({ response: { status: 422, data: { message: 'Invalid', errors: { email: ['bad'] } } } })
    expect(r.message).toBe('Invalid')
    expect(r.errors.email[0]).toBe('bad')
  })
  it('gives friendly message for rate limiting', () => {
    expect(parseError({ response: { status: 429, data: {} } }).message).toMatch(/Too many requests/)
  })
  it('gives friendly message when network is down', () => {
    expect(parseError({}).message).toMatch(/Cannot reach the server/)
  })
  it('keeps insufficient credit message for 402', () => {
    expect(parseError({ response: { status: 402, data: { message: 'Not enough credits' } } }).message).toBe('Not enough credits')
  })
})

describe('auth store', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('starts logged out', () => {
    const s = useAuthStore()
    expect(s.isAuthenticated).toBe(false)
    expect(s.isAdmin).toBe(false)
  })
  it('detects admin role', () => {
    const s = useAuthStore()
    s.user = { name: 'A', roles: ['admin'] }
    expect(s.isAuthenticated).toBe(true)
    expect(s.isAdmin).toBe(true)
  })
  it('fetchUser sets user and marks ready', async () => {
    vi.spyOn(api, 'get').mockResolvedValue({ data: { data: { name: 'Demo', roles: ['user'] } } })
    const s = useAuthStore()
    await s.fetchUser()
    expect(s.user.name).toBe('Demo')
    expect(s.ready).toBe(true)
  })
  it('fetchUser clears user on 401', async () => {
    vi.spyOn(api, 'get').mockRejectedValue({ response: { status: 401 } })
    const s = useAuthStore()
    s.user = { name: 'Old' }
    await s.fetchUser()
    expect(s.user).toBeNull()
    expect(s.ready).toBe(true)
  })
})
