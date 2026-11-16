import { useToastStore } from '@/stores/toast'

export function useCopy() {
  const toast = useToastStore()

  async function copy(text, label = 'Copied to clipboard') {
    try {
      await navigator.clipboard.writeText(String(text ?? ''))
      toast.success(label)
    } catch {
      // Fallback for browsers/contexts without the async clipboard API.
      const ta = document.createElement('textarea')
      ta.value = String(text ?? '')
      document.body.appendChild(ta)
      ta.select()
      try {
        document.execCommand('copy')
        toast.success(label)
      } catch {
        toast.error('Could not copy. Please copy manually.')
      }
      ta.remove()
    }
  }

  return { copy }
}
