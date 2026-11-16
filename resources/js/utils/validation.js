// Client-side validation is for UX only; the backend validates everything again.
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function validateContact(f) {
  const e = {}
  if (!f.name?.trim()) e.name = 'Please enter your name.'
  if (!EMAIL.test(f.email || '')) e.email = 'Please enter a valid email address.'
  if (!f.subject?.trim()) e.subject = 'Please enter a subject.'
  if ((f.message || '').trim().length < 10) e.message = 'Message must be at least 10 characters.'
  return e
}

export function validateLogin(f) {
  const e = {}
  if (!EMAIL.test(f.email || '')) e.email = 'Please enter a valid email address.'
  if (!f.password) e.password = 'Please enter your password.'
  return e
}

export function validateRegister(f) {
  const e = {}
  if (!f.name?.trim()) e.name = 'Please enter your name.'
  if (!EMAIL.test(f.email || '')) e.email = 'Please enter a valid email address.'
  if ((f.password || '').length < 8) e.password = 'Password must be at least 8 characters.'
  if (f.password !== f.password_confirmation) e.password_confirmation = 'Passwords do not match.'
  return e
}
