<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue'
import { Sparkles, Copy, RefreshCw, Bookmark, Download, Trash2, Info } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useCopy } from '@/composables/useCopy'
import { toolForms, initialForm, validateForm, buildPayload } from '@/utils/toolForms'
import { downloadText, firstErrors } from '@/utils/format'
import OutputView from '@/components/tools/OutputView.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

// One generic page drives all single-shot generators from the config in utils/toolForms.js
const props = defineProps({ slug: { type: String, required: true } })

const auth = useAuthStore()
const toast = useToastStore()
const { copy } = useCopy()

const config = computed(() => toolForms[props.slug])
const form = reactive({})
const errors = ref({})
const loading = ref(false)
const result = ref(null)
const saved = ref(false)
const cost = ref(null)
const confirmDelete = ref(false)

function reset() {
  Object.keys(form).forEach((k) => delete form[k])
  Object.assign(form, initialForm(config.value, auth.user))
  errors.value = {}
  result.value = null
  saved.value = false
}

async function loadCost() {
  try {
    const { data } = await api.get('/ai/tools')
    const mine = (data.data ?? []).find((t) => t.slug === props.slug)
    cost.value = mine ? mine.credit_cost : null
  } catch { /* cost label is optional */ }
}

async function generate() {
  errors.value = validateForm(config.value, form)
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    const { data } = await api.post(`/ai/${config.value.endpoint}`, buildPayload(config.value, form))
    result.value = data.data
    saved.value = false
    if (auth.user && data.credits_balance != null) auth.user.credits_balance = data.credits_balance
    toast.success('Generated successfully.')
  } catch (e) {
    const err = parseError(e)
    errors.value = firstErrors(err.errors)
    toast.error(err.message)
  } finally {
    loading.value = false
  }
}

async function save() {
  try {
    await api.post('/saved-content', { ai_generation_id: result.value.id })
    saved.value = true
    toast.success('Saved to your library.')
  } catch (e) { toast.error(parseError(e).message) }
}

async function remove() {
  try {
    await api.delete(`/history/${result.value.id}`)
    result.value = null
    toast.success('Deleted.')
  } catch (e) { toast.error(parseError(e).message) }
  confirmDelete.value = false
}

watch(() => props.slug, () => { reset(); loadCost() })
onMounted(() => { reset(); loadCost() })
</script>

<template>
  <div v-if="config" class="mx-auto max-w-6xl">
    <h1 class="text-2xl font-bold">{{ config.title }}</h1>
    <p class="mt-1 text-sm">{{ config.description }}</p>
    <p v-if="config.disclaimer" class="mt-3 flex gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"><Info class="mt-0.5 h-4 w-4 shrink-0" />{{ config.disclaimer }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <form class="card space-y-4 p-5" novalidate @submit.prevent="generate">
        <div class="grid gap-4 sm:grid-cols-2">
          <div v-for="f in config.fields" :key="f.key" :class="f.wide && 'sm:col-span-2'">
            <label :for="f.key" class="label">{{ f.label }}<span v-if="f.required" class="text-red-500"> *</span></label>
            <select v-if="f.type === 'select'" :id="f.key" v-model="form[f.key]" class="input">
              <option v-for="o in f.options" :key="o[0]" :value="o[0]">{{ o[1] }}</option>
            </select>
            <textarea v-else-if="f.type === 'textarea'" :id="f.key" v-model="form[f.key]" :rows="f.rows || 4" class="input" :placeholder="f.placeholder" :aria-invalid="!!errors[f.key]" />
            <input v-else :id="f.key" v-model="form[f.key]" type="text" class="input" :placeholder="f.placeholder" :aria-invalid="!!errors[f.key]" />
            <p v-if="errors[f.key]" class="mt-1 text-xs text-red-600">{{ errors[f.key] }}</p>
          </div>
        </div>
        <div class="flex items-center justify-between gap-3">
          <p class="text-xs">
            <template v-if="cost !== null">Costs {{ cost }} credit{{ cost === 1 ? '' : 's' }} · </template>
            Balance: {{ auth.user?.credits_balance ?? '—' }}
          </p>
          <button class="btn btn-primary" :disabled="loading"><Sparkles class="h-4 w-4" />{{ loading ? 'Generating…' : config.button }}</button>
        </div>
      </form>

      <div>
        <div v-if="loading" class="space-y-3" aria-busy="true" aria-live="polite">
          <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-xl bg-slate-200" />
        </div>
        <div v-else-if="result">
          <div class="mb-3 flex flex-wrap gap-2">
            <button class="btn btn-secondary !px-3 !py-2" @click="copy(result.plain_text, 'All output copied')"><Copy class="h-4 w-4" />Copy all</button>
            <button class="btn btn-secondary !px-3 !py-2" :disabled="loading" @click="generate"><RefreshCw class="h-4 w-4" />Regenerate</button>
            <button class="btn btn-secondary !px-3 !py-2" :disabled="saved" @click="save"><Bookmark class="h-4 w-4" />{{ saved ? 'Saved' : 'Save' }}</button>
            <button class="btn btn-secondary !px-3 !py-2" @click="downloadText(result.title, result.plain_text)"><Download class="h-4 w-4" />Download TXT</button>
            <button class="btn btn-secondary !px-3 !py-2 !text-red-600" @click="confirmDelete = true"><Trash2 class="h-4 w-4" />Delete</button>
          </div>
          <OutputView :data="result.output" />
        </div>
        <div v-else class="grid h-full min-h-48 place-items-center rounded-2xl border border-dashed border-line bg-white p-8 text-center text-sm">
          Your result will appear here.
        </div>
      </div>
    </div>

    <ConfirmDialog :open="confirmDelete" title="Delete this result?" message="It will also be removed from your history." @cancel="confirmDelete = false" @confirm="remove" />
  </div>
</template>
