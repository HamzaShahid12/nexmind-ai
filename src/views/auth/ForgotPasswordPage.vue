<script setup>
import { ref } from 'vue'
import api, { csrf, parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const email = ref('')
const error = ref('')
const loading = ref(false)
const sent = ref(false)

async function submit() {
  error.value = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ? '' : 'Please enter a valid email address.'
  if (error.value) return
  loading.value = true
  try {
    await csrf()
    await api.post('/forgot-password', { email: email.value })
    sent.value = true
  } catch (e) {
    toast.error(parseError(e).message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold">Forgot password</h1>
    <p v-if="sent" class="mt-4 text-sm">If an account exists for that email, a reset link has been sent.</p>
    <form v-else class="mt-6 space-y-4" novalidate @submit.prevent="submit">
      <div>
        <label for="email" class="label">Email</label>
        <input id="email" v-model="email" type="email" class="input" :aria-invalid="!!error" />
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
      </div>
      <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Sending…' : 'Send reset link' }}</button>
    </form>
    <p class="mt-5 text-center text-sm"><RouterLink to="/login" class="font-semibold text-primary">Back to login</RouterLink></p>
  </div>
</template>
