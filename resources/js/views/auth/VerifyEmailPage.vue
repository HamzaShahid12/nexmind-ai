<script setup>
import { ref } from 'vue'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

const toast = useToastStore()
const auth = useAuthStore()
const loading = ref(false)

async function resend() {
  loading.value = true
  try {
    await api.post('/email/verification-notification')
    toast.success('Verification link sent.')
  } catch (e) {
    toast.error(parseError(e).message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold">Verify your email</h1>
    <p class="mt-3 text-sm">We sent a verification link to <strong>{{ auth.user?.email || 'your email address' }}</strong>. Click the link in the email to verify your account.</p>
    <button class="btn btn-secondary mt-6 w-full" :disabled="loading" @click="resend">{{ loading ? 'Sending…' : 'Resend verification email' }}</button>
    <RouterLink to="/dashboard" class="mt-4 block text-center text-sm text-primary">Continue to dashboard</RouterLink>
  </div>
</template>
