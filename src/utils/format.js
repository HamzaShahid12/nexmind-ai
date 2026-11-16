/** Read a dotted path ("tool.name") from an object. */
export function get(obj, path) {
  return String(path).split('.').reduce((o, k) => (o == null ? o : o[k]), obj)
}

export const labelize = (key) => String(key).replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase())

/** Flatten any generation output (string / array / object) into readable plain text. */
export function toPlainText(value, depth = 0) {
  if (value == null) return ''
  if (typeof value !== 'object') return String(value)
  if (Array.isArray(value)) {
    return value.map((v) => (typeof v === 'object' && v !== null ? toPlainText(v, depth + 1) : `- ${v}`)).join('\n')
  }
  return Object.entries(value)
    .map(([k, v]) => (typeof v === 'object' && v !== null ? `${labelize(k)}:\n${toPlainText(v, depth + 1)}` : `${labelize(k)}: ${v}`))
    .join('\n')
}

export function formatDate(value, withTime = false) {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString(undefined, withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' })
}

export function downloadText(filename, text) {
  const safe = String(filename || 'nexmind-output').replace(/[^\w\- ]+/g, '').trim().slice(0, 60) || 'nexmind-output'
  const url = URL.createObjectURL(new Blob([text], { type: 'text/plain;charset=utf-8' }))
  const a = document.createElement('a')
  a.href = url
  a.download = `${safe}.txt`
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}

/** Turn a Laravel validation error bag ({field: [msg]}) into {field: msg}. */
export const firstErrors = (errors = {}) => Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
