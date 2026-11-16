import axios from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? ''

// Cookie-based Sanctum SPA auth: no tokens are ever stored in localStorage.
const api = axios.create({
  baseURL: `${baseURL}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  timeout: 90000,
})

export const csrf = () => axios.get(`${baseURL}/sanctum/csrf-cookie`, { withCredentials: true })

/** Convert any axios error into { message, status, errors } safe for display. */
export function parseError(error) {
  const status = error?.response?.status ?? 0
  const data = error?.response?.data
  let message = data?.message
  if (!message) {
    if (error?.code === 'ECONNABORTED') message = 'The request timed out. Please try again.'
    else if (!error?.response) message = 'Cannot reach the server. Check your connection.'
    else message = 'Something went wrong. Please try again.'
  }
  if (status === 402) message = data?.message || 'You do not have enough credits for this action.'
  if (status === 429) message = 'Too many requests. Please wait a moment and try again.'
  return { message, status, errors: data?.errors || {} }
}

export default api