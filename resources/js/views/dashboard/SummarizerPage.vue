<script setup>
import { ref } from 'vue'
import { UploadCloud, FileText, Send } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { firstErrors, formatDate } from '@/utils/format'
import OutputView from '@/components/tools/OutputView.vue'

const MAX_MB = 5
const ALLOWED = ['pdf', 'txt', 'docx']

const auth = useAuthStore()
const toast = useToastStore()
const file = ref(null)
const fileError = ref('')
const loading = ref(false)
const doc = ref(null)
const summary = ref(null)
const question = ref('')
const asking = ref(false)
const qa = ref([])

function onPick(e) {
  fileError.value = ''
  const f = e.target.files?.[0]
  if (!f) return (file.value = null)
  const ext = f.name.split('.').pop().toLowerCase()
  // UX checks only: the server validates type, size and content again.
  if (!ALLOWED.includes(ext)) { fileError.value = 'Only PDF, TXT and DOCX files are allowed.'; e.target.value = ''; return (file.value = null) }
  if (f.size > MAX_MB * 1024 * 1024) { fileError.value = `The file is too large. Maximum size is ${MAX_MB} MB.`; e.target.value = ''; return (file.value = null) }
  file.value = f
}

async function summarize() {
  if (!file.value) return (fileError.value = 'Please choose a file first.')
  loading.value = true
  doc.value = summary.value = null
  qa.value = []
  try {
    const body = new FormData()
    body.append('file', file.value)
    const { data } = await api.post('/ai/document-summary', body)
    doc.value = data.document
    summary.value = data.generation.output
    if (auth.user) auth.user.credits_balance = data.credits_balance
    toast.success('Summary ready.')
  } catch (e) {
    const err = parseError(e)
    fileError.value = firstErrors(err.errors).file || ''
    toast.error(fileError.value || err.message)
  } finally {
    loading.value = false
  }
}

async function ask() {
  const q = question.value.trim()
  if (!q || !doc.value) return
  asking.value = true
  try {
    const { data } = await api.post(`/ai/document-summary/${doc.value.id}/ask`, { question: q })
    qa.value.push({ q, a: data.answer })
    question.value = ''
    if (auth.user) auth.user.credits_balance = data.credits_balance
  } catch (e) {
    toast.error(parseError(e).message)
  } finally {
    asking.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-4xl">
    <h1 class="text-2xl font-bold">Document Summarizer</h1>
    <p class="mt-1 text-sm">Upload a PDF, TXT or DOCX (max {{ MAX_MB }} MB). We extract the text on our server and delete the uploaded file immediately.</p>

    <form class="card mt-6 p-5" @submit.prevent="summarize">
      <label for="doc" class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-line p-8 text-center hover:border-primary">
        <UploadCloud class="h-8 w-8 text-primary" />
        <span class="text-sm font-medium text-ink">{{ file ? file.name : 'Choose a file' }}</span>
        <span class="text-xs">PDF, TXT or DOCX · scanned images are not supported</span>
      </label>
      <input id="doc" type="file" accept=".pdf,.txt,.docx" class="sr-only" @change="onPick" />
      <p v-if="fileError" class="mt-2 text-sm text-red-600" role="alert">{{ fileError }}</p>
      <div class="mt-4 flex justify-end"><button class="btn btn-primary" :disabled="loading || !file"><FileText class="h-4 w-4" />{{ loading ? 'Summarizing…' : 'Summarize' }}</button></div>
    </form>

    <div v-if="loading" class="mt-6 space-y-3" aria-busy="true"><div v-for="n in 3" :key="n" class="h-20 animate-pulse rounded-xl bg-slate-200" /></div>

    <template v-else-if="summary">
      <p class="mt-6 text-xs">{{ doc.original_name }} · {{ doc.char_count.toLocaleString() }} characters read<span v-if="doc.truncated"> (long document: only the first part was used)</span> · kept until {{ formatDate(doc.expires_at) }}</p>
      <div class="mt-3"><OutputView :data="summary" /></div>

      <section class="card mt-6 p-5">
        <h2 class="font-semibold">Ask a question about this document</h2>
        <div v-for="(item, i) in qa" :key="i" class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
          <p class="font-medium text-ink">Q: {{ item.q }}</p>
          <p class="mt-1 whitespace-pre-wrap">{{ item.a }}</p>
        </div>
        <form class="mt-4 flex gap-2" @submit.prevent="ask">
          <input v-model="question" maxlength="1000" class="input" placeholder="e.g. What are the main risks mentioned?" aria-label="Question" />
          <button class="btn btn-primary" :disabled="asking || !question.trim()" aria-label="Ask"><Send class="h-4 w-4" /></button>
        </form>
      </section>
    </template>
  </div>
</template>
