<script setup>
import { reactive, ref } from 'vue'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { validateContact } from '@/utils/validation'

const toast = useToastStore()
const form = reactive({ name: '', email: '', subject: '', message: '' })
const errors = ref({})
const loading = ref(false)
const sent = ref(false)

async function submit() {
  errors.value = validateContact(form)
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    await api.post('/contact', form)
    sent.value = true
    toast.success('Message sent. We will get back to you soon.')
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
  <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6">
    <h1 class="text-4xl font-extrabold">Contact us</h1>
    <p class="mt-3">Questions or feedback? Send us a message.</p>

    <div v-if="sent" class="card mt-8 p-8 text-center">
      <h2 class="text-xl font-bold">Thank you!</h2>
      <p class="mt-2 text-sm">Your message has been received.</p>
    </div>

    <form v-else class="card mt-8 space-y-5 p-6 sm:p-8" novalidate @submit.prevent="submit">
      <div v-for="f in [['name', 'Name', 'text'], ['email', 'Email', 'email'], ['subject', 'Subject', 'text']]" :key="f[0]">
        <label :for="f[0]" class="label">{{ f[1] }}</label>
        <input :id="f[0]" v-model="form[f[0]]" :type="f[2]" class="input" :aria-invalid="!!errors[f[0]]" />
        <p v-if="errors[f[0]]" class="mt-1 text-xs text-red-600">{{ errors[f[0]] }}</p>
      </div>
      <div>
        <label for="message" class="label">Message</label>
        <textarea id="message" v-model="form.message" rows="5" class="input" :aria-invalid="!!errors.message" />
        <p v-if="errors.message" class="mt-1 text-xs text-red-600">{{ errors.message }}</p>
      </div>
      <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Sending…' : 'Send message' }}</button>
    </form>
  </section>
</template>
