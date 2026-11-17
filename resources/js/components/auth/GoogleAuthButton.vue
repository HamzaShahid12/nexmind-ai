<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { appBaseUrl } from '@/services/api'

const props = defineProps({
  label: { type: String, default: 'Continue with Google' },
})

const route = useRoute()
const oauthUrl = computed(() => {
  const redirect = String(route.query.redirect || '')
  const query = redirect.startsWith('/') && !redirect.startsWith('//')
    ? `?${new URLSearchParams({ redirect }).toString()}`
    : ''

  return `${appBaseUrl.replace(/\/+$/, '')}/auth/google/redirect${query}`
})
</script>

<template>
  <a
    :href="oauthUrl"
    class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
  >
    <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
      <path fill="#4285F4" d="M47.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h13.47c-.58 2.96-2.26 5.48-4.81 7.18v5.99h7.73c4.53-4.17 7.14-10.31 7.14-17.64z" />
      <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-5.99c-2.13 1.43-4.86 2.28-8.18 2.28-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
      <path fill="#FBBC05" d="M10.53 28.59a14.4 14.4 0 0 1 0-9.18l-7.98-6.19a23.9 23.9 0 0 0 0 21.56l7.98-6.19z" />
      <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
    </svg>
    {{ props.label }}
  </a>
</template>
