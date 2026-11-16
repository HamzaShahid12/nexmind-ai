<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { parseError } from '@/services/api'
import { validateLogin } from '@/utils/validation'

const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const errors = ref({})
const loading = ref(false)

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
    <h1 class="text-2xl font-bold">Welcome back</h1>
    <p class="mt-1 text-sm">Log in to your NexMind AI account.</p>
    <form class="mt-6 space-y-4" novalidate @submit.prevent="submit">
      <div>
        <label for="email" class="label">Email</label>
        <input id="email" v-model="form.email" type="email" autocomplete="email" class="input" :aria-invalid="!!errors.email" />
        <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
      </div>
      <div>
        <div class="flex items-center justify-between"><label for="password" class="label">Password</label><RouterLink to="/forgot-password" class="text-xs text-primary">Forgot password?</RouterLink></div>
        <input id="password" v-model="form.password" type="password" autocomplete="current-password" class="input" :aria-invalid="!!errors.password" />
        <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
      </div>
      <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Logging in…' : 'Log in' }}</button>
    </form>
    <p class="mt-5 text-center text-sm">New here? <RouterLink to="/register" class="font-semibold text-primary">Create an account</RouterLink></p>
  </div>
</template>
