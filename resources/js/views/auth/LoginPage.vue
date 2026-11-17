<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { parseError } from '@/services/api'
import { validateLogin } from '@/utils/validation'
import GoogleAuthButton from '@/components/auth/GoogleAuthButton.vue'

const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const errors = ref({})
const loading = ref(false)
const showPassword = ref(false)

const googleError = computed(() => ({
  not_configured: 'Google sign-in is not set up yet. Please use your email and password.',
  cancelled: 'Google sign-in was cancelled.',
  failed: 'We could not sign you in with Google. Please try again.',
  unverified: 'Your Google account must have a verified email address.',
  unavailable: 'This account is unavailable. Please contact support.',
  registration_disabled: 'New account registration is currently closed.',
}[String(route.query.google || '')] || ''))

async function submit() {
  errors.value = validateLogin(form)
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    await auth.login(form)
    // Only allow same-site relative redirects.
    const r = String(route.query.redirect || '')
    router.push(r.startsWith('/') && !r.startsWith('//') ? r : { name: 'dashboard' })
  } catch (e) {
    const err = parseError(e)
    errors.value = Object.fromEntries(Object.entries(err.errors).map(([k, v]) => [k, v[0]]))
    toast.error(err.status === 422 ? 'Those credentials do not match our records.' : err.message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-7">
      <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Welcome back</p>
      <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-ink">Sign in to NexMind</h1>
      <p class="mt-2 text-sm leading-6 text-slate-500">Pick up where you left off and keep your ideas moving.</p>
    </div>

    <div v-if="googleError" role="alert" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-5 text-amber-900">
      {{ googleError }}
    </div>

    <GoogleAuthButton />

    <div class="my-6 flex items-center gap-4">
      <span class="h-px flex-1 bg-slate-200"></span>
      <span class="text-xs font-medium text-slate-400">OR CONTINUE WITH EMAIL</span>
      <span class="h-px flex-1 bg-slate-200"></span>
    </div>

    <form class="space-y-5" novalidate @submit.prevent="submit">
      <div>
        <label for="email" class="label">Email address</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          placeholder="you@company.com"
          class="input !rounded-xl !py-3"
          :aria-invalid="!!errors.email"
          :aria-describedby="errors.email ? 'email-error' : undefined"
        />
        <p v-if="errors.email" id="email-error" class="mt-1.5 text-xs text-red-600">{{ errors.email }}</p>
      </div>

      <div>
        <div class="mb-1.5 flex items-center justify-between">
          <label for="password" class="label !mb-0">Password</label>
          <RouterLink to="/forgot-password" class="text-xs font-semibold text-primary transition hover:text-primary-dark">Forgot password?</RouterLink>
        </div>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Enter your password"
            class="input !rounded-xl !py-3 !pr-12"
            :aria-invalid="!!errors.password"
            :aria-describedby="errors.password ? 'password-error' : undefined"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/40"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :aria-pressed="showPassword"
            @click="showPassword = !showPassword"
          >
            <svg v-if="showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" />
              <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c5.5 0 9 5.5 9 7a8.6 8.6 0 0 1-2.1 3.4M6.2 6.2C3.9 7.7 2 10.5 2 12c0 1.5 3.5 7 10 7a10.5 10.5 0 0 0 4-.8" />
            </svg>
            <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
        <p v-if="errors.password" id="password-error" class="mt-1.5 text-xs text-red-600">{{ errors.password }}</p>
      </div>

      <button class="btn btn-primary w-full !rounded-xl !py-3" :disabled="loading">
        {{ loading ? 'Signing in…' : 'Sign in' }}
        <svg v-if="!loading" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
      </button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-500">
      New to NexMind? <RouterLink to="/register" class="font-semibold text-primary transition hover:text-primary-dark">Create an account</RouterLink>
    </p>
  </div>
</template>
