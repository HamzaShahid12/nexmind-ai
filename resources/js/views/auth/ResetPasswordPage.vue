<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { csrf, parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const form = reactive({ email: String(route.query.email || ''), token: String(route.query.token || ''), password: '', password_confirmation: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  if (form.password.length < 8) return (error.value = 'Password must be at least 8 characters.')
  if (form.password !== form.password_confirmation) return (error.value = 'Passwords do not match.')
  error.value = ''
  loading.value = true
  try {
    await csrf()
    await api.post('/reset-password', form)
    toast.success('Password reset. You can now log in.')
    router.push({ name: 'login' })
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold">Reset password</h1>
    <form class="mt-6 space-y-4" novalidate @submit.prevent="submit">
      <div><label for="password" class="label">New password</label><input id="password" v-model="form.password" type="password" autocomplete="new-password" class="input" /></div>
      <div><label for="confirm" class="label">Confirm password</label><input id="confirm" v-model="form.password_confirmation" type="password" autocomplete="new-password" class="input" /></div>
      <p v-if="error" class="text-xs text-red-600" role="alert">{{ error }}</p>
      <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Saving…' : 'Reset password' }}</button>
    </form>
  </div>
</template>
