import { describe, it, expect } from 'vitest'
import { validateContact, validateLogin, validateRegister } from '@/utils/validation'

describe('validation', () => {
  it('flags empty contact form fields', () => {
    const e = validateContact({ name: '', email: 'x', subject: '', message: 'short' })
    expect(Object.keys(e).sort()).toEqual(['email', 'message', 'name', 'subject'])
  })
  it('accepts a valid contact form', () => {
    expect(validateContact({ name: 'A', email: 'a@b.co', subject: 'Hi', message: 'This is long enough.' })).toEqual({})
  })
  it('requires email and password on login', () => {
    expect(Object.keys(validateLogin({ email: '', password: '' }))).toEqual(['email', 'password'])
  })
  it('checks register password rules', () => {
    const e = validateRegister({ name: 'A', email: 'a@b.co', password: 'short', password_confirmation: 'other' })
    expect(e.password).toBeTruthy()
    expect(e.password_confirmation).toBeTruthy()
  })
})
