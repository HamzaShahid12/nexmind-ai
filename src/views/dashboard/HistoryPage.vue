<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { Search, Eye, Copy, Bookmark, Trash2, History } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useCopy } from '@/composables/useCopy'
import { formatDate } from '@/utils/format'
import OutputView from '@/components/tools/OutputView.vue'
import Modal from '@/components/ui/Modal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Pagination from '@/components/ui/Pagination.vue'

const toast = useToastStore()
const { copy } = useCopy()

const filters = reactive({ search: '', tool: '', from: '', to: '' })
const tools = ref([])
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const viewing = ref(null)
const deleting = ref(null)
let timer

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)) }
    const { data } = await api.get('/history', { params })
    items.value = data.data
    meta.value = data.meta ?? data
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
}

async function view(item) {
  try {
    const { data } = await api.get(`/history/${item.id}`)
    viewing.value = data.data
  } catch (e) { toast.error(parseError(e).message) }
}

async function save(item) {
  try {
    await api.post('/saved-content', { ai_generation_id: item.id })
    toast.success('Saved to your library.')
  } catch (e) { toast.error(parseError(e).message) }
}

async function copyItem(item) {
  try {
    const { data } = await api.get(`/history/${item.id}`)
    await copy(data.data.plain_text, 'Output copied')
  } catch (e) { toast.error(parseError(e).message) }
}

async function confirmDelete() {
  try {
    await api.delete(`/history/${deleting.value.id}`)
    toast.success('Deleted.')
    await load(meta.value.current_page)
  } catch (e) { toast.error(parseError(e).message) }
  deleting.value = null
}

watch(() => filters.search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 350) })
watch(() => [filters.tool, filters.from, filters.to], () => load(1))
onMounted(async () => {
  load()
  try { tools.value = (await api.get('/ai/tools')).data.data } catch { /* filter stays empty */ }
})
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <h1 class="text-2xl font-bold">My History</h1>
    <div class="mt-5 grid gap-3 md:grid-cols-[1fr_200px_160px_160px]">
      <div class="relative"><Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" /><input v-model="filters.search" type="search" class="input pl-10" placeholder="Search history…" aria-label="Search history" /></div>
      <select v-model="filters.tool" class="input" aria-label="Filter by tool"><option value="">All tools</option><option v-for="t in tools" :key="t.slug" :value="t.slug">{{ t.name }}</option></select>
      <input v-model="filters.from" type="date" class="input" aria-label="From date" />
      <input v-model="filters.to" type="date" class="input" aria-label="To date" />
    </div>

    <div v-if="loading" class="mt-6 space-y-3"><Skeleton v-for="n in 4" :key="n" class="h-20 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load history" :message="error"><button class="btn btn-secondary" @click="load()">Try again</button></EmptyState>
    <EmptyState v-else-if="!items.length" class="mt-6" title="No generations found" message="Results you generate with AI tools will appear here.">
      <template #icon><History class="h-8 w-8 text-slate-300" /></template>
      <RouterLink to="/ai-tools" class="btn btn-primary">Explore tools</RouterLink>
    </EmptyState>
    <ul v-else class="mt-6 space-y-3">
      <li v-for="i in items" :key="i.id" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2 text-xs"><span class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary">{{ i.tool?.name }}</span><span>{{ formatDate(i.created_at, true) }}</span><span>· {{ i.credits_used }} credit{{ i.credits_used === 1 ? '' : 's' }}</span></div>
          <h2 class="mt-1 truncate font-semibold text-ink">{{ i.title }}</h2>
          <p class="line-clamp-2 text-sm">{{ i.preview }}</p>
        </div>
        <div class="flex gap-1">
          <button class="btn btn-secondary !px-2.5" aria-label="View" @click="view(i)"><Eye class="h-4 w-4" /></button>
          <button class="btn btn-secondary !px-2.5" aria-label="Copy output" @click="copyItem(i)"><Copy class="h-4 w-4" /></button>
          <button class="btn btn-secondary !px-2.5" aria-label="Save" @click="save(i)"><Bookmark class="h-4 w-4" /></button>
          <button class="btn btn-secondary !px-2.5 !text-red-600" aria-label="Delete" @click="deleting = i"><Trash2 class="h-4 w-4" /></button>
        </div>
      </li>
    </ul>
    <Pagination :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" @change="load" />

    <Modal :open="!!viewing" :title="viewing?.title" wide @close="viewing = null"><OutputView v-if="viewing" :data="viewing.output" /></Modal>
    <ConfirmDialog :open="!!deleting" title="Delete this item?" message="It will be permanently removed from your history." @cancel="deleting = null" @confirm="confirmDelete" />
  </div>
</template>
