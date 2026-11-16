<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { KeyRound } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { firstErrors } from '@/utils/format'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

// Serves both /admin/ai-settings and /admin/settings (route.meta.kind = 'ai' | 'general').
const route = useRoute()
const toast = useToastStore()
const isAi = computed(() => route.meta.kind === 'ai')
const url = computed(() => (isAi.value ? '/admin/ai-settings' : '/admin/settings'))

const form = reactive({})
const info = ref(null)
const errors = ref({})
const loading = ref(true)
const loadError = ref('')
const saving = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get(url.value)
    info.value = data.data
    Object.assign(form, data.data)
  } catch (e) { loadError.value = parseError(e).message } finally { loading.value = false }
})

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const body = isAi.value
      ? { provider: form.provider, model: form.model, timeout: Number(form.timeout) }
      : { site_name: form.site_name, contact_email: form.contact_email || null, registration_enabled: !!form.registration_enabled, documents_retention_days: Number(form.documents_retention_days) }
    const { data } = await api.put(url.value, body)
    info.value = data.data
    toast.success('Settings saved.')
  } catch (e) {
    const err = parseError(e)
    errors.value = firstErrors(err.errors)
    toast.error(err.message)
  } finally { saving.value = false }
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold">{{ isAi ? 'AI Model Settings' : 'General Settings' }}</h1>
    <Skeleton v-if="loading" class="mt-6 h-48 w-full" />
    <EmptyState v-else-if="loadError" class="mt-6" title="Could not load settings" :message="loadError" />
    <form v-else class="card mt-6 space-y-4 p-5" novalidate @submit.prevent="save">
      <template v-if="isAi">
        <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 text-sm">
          <KeyRound class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
          <div>
            <p><strong>API key:</strong> {{ info.api_key_configured ? `configured (${info.api_key_hint})` : 'not configured' }}</p>
            <p class="text-xs">{{ info.note }}</p>
          </div>
        </div>
        <div><label for="provider" class="label">Provider</label><select id="provider" v-model="form.provider" class="input"><option v-for="p in info.available_providers" :key="p" :value="p">{{ p }}</option></select><p v-if="errors.provider" class="mt-1 text-xs text-red-600">{{ errors.provider }}</p></div>
        <div><label for="model" class="label">Model name</label><input id="model" v-model="form.model" class="input" /><p v-if="errors.model" class="mt-1 text-xs text-red-600">{{ errors.model }}</p></div>
        <div><label for="timeout" class="label">Timeout (seconds)</label><input id="timeout" v-model="form.timeout" type="number" min="5" max="300" class="input" /><p v-if="errors.timeout" class="mt-1 text-xs text-red-600">{{ errors.timeout }}</p></div>
        <p class="text-xs">Per-tool output length and temperature are edited under AI Tools. The server-wide output ceiling is {{ info.max_output_tokens_ceiling }} tokens.</p>
      </template>
      <template v-else>
        <div><label for="site" class="label">Site name</label><input id="site" v-model="form.site_name" class="input" /><p v-if="errors.site_name" class="mt-1 text-xs text-red-600">{{ errors.site_name }}</p></div>
        <div><label for="mail" class="label">Contact email</label><input id="mail" v-model="form.contact_email" type="email" class="input" /><p v-if="errors.contact_email" class="mt-1 text-xs text-red-600">{{ errors.contact_email }}</p></div>
        <div><label for="ret" class="label">Uploaded-document text retention (days)</label><input id="ret" v-model="form.documents_retention_days" type="number" min="1" max="365" class="input" /><p v-if="errors.documents_retention_days" class="mt-1 text-xs text-red-600">{{ errors.documents_retention_days }}</p></div>
        <label class="flex items-center gap-2 text-sm"><input v-model="form.registration_enabled" type="checkbox" class="h-4 w-4 rounded border-line" />Allow new user registration</label>
      </template>
      <div class="flex justify-end"><button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save settings' }}</button></div>
    </form>
  </div>
</template>
