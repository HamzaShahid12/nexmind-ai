<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { parseError } from '@/services/api'
import { validateRegister } from '@/utils/validation'
import GoogleAuthButton from '@/components/auth/GoogleAuthButton.vue'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const loading = ref(false)

const fields = [
  ['name', 'Full name', 'text', 'name'],
  ['email', 'Email', 'email', 'email'],
  ['password', 'Password', 'password', 'new-password'],
  ['password_confirmation', 'Confirm password', 'password', 'new-password'],
]

async function submit() {
  errors.value = validateRegister(form)
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    await auth.register(form)
    toast.success('Account created. Please verify your email.')
    router.push({ name: 'dashboard' })
  } catch (e) {
    const err = parseError(e)
    errors.value = Object.fromEntries(Object.entries(err.errors).map(([k, v]) => [k, v[0]]))
    toast.error(err.message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold">Create your account</h1>
    <p class="mt-1 text-sm">Start with free monthly credits. No card required.</p>
    <GoogleAuthButton label="Sign up with Google" class="mt-6" />
    <div class="my-5 flex items-center gap-4">
      <span class="h-px flex-1 bg-slate-200"></span>
      <span class="text-xs font-medium text-slate-400">OR SIGN UP WITH EMAIL</span>
      <span class="h-px flex-1 bg-slate-200"></span>
    </div>
    <form class="mt-6 space-y-4" novalidate @submit.prevent="submit">
      <div v-for="f in fields" :key="f[0]">
        <label :for="f[0]" class="label">{{ f[1] }}</label>
        <input :id="f[0]" v-model="form[f[0]]" :type="f[2]" :autocomplete="f[3]" class="input" :aria-invalid="!!errors[f[0]]" />
        <p v-if="errors[f[0]]" class="mt-1 text-xs text-red-600">{{ errors[f[0]] }}</p>
      </div>
      <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Creating account…' : 'Create account' }}</button>
    </form>
    <p class="mt-5 text-center text-sm">Already registered? <RouterLink to="/login" class="font-semibold text-primary">Log in</RouterLink></p>
  </div>
</template>
