<script setup>
import { ref, onMounted, watch } from 'vue'
import { Search, Copy, Trash2, Bookmark } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useCopy } from '@/composables/useCopy'
import { formatDate, downloadText } from '@/utils/format'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Pagination from '@/components/ui/Pagination.vue'

const toast = useToastStore()
const { copy } = useCopy()

const search = ref('')
const category = ref('')
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const deleting = ref(null)
const expanded = ref(null)
let timer

const categories = ['Writing', 'Ecommerce', 'Marketing', 'SEO', 'Productivity']

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/saved-content', { params: { page, search: search.value || undefined, category: category.value || undefined } })
    items.value = data.data
    meta.value = data
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
}

async function confirmDelete() {
  try {
    await api.delete(`/saved-content/${deleting.value.id}`)
    toast.success('Deleted.')
    await load(meta.value.current_page)
  } catch (e) { toast.error(parseError(e).message) }
  deleting.value = null
}

watch(search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 350) })
watch(category, () => load(1))
onMounted(load)
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <h1 class="text-2xl font-bold">Saved Content</h1>
    <div class="mt-5 grid gap-3 sm:grid-cols-[1fr_200px]">
      <div class="relative"><Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" /><input v-model="search" type="search" class="input pl-10" placeholder="Search saved content…" aria-label="Search saved content" /></div>
      <select v-model="category" class="input" aria-label="Category"><option value="">All categories</option><option v-for="c in categories" :key="c">{{ c }}</option></select>
    </div>

    <div v-if="loading" class="mt-6 space-y-3"><Skeleton v-for="n in 3" :key="n" class="h-24 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load saved content" :message="error"><button class="btn btn-secondary" @click="load()">Try again</button></EmptyState>
    <EmptyState v-else-if="!items.length" class="mt-6" title="Nothing saved yet" message="Use the Save button on any generated result to keep it here.">
      <template #icon><Bookmark class="h-8 w-8 text-slate-300" /></template>
    </EmptyState>
    <ul v-else class="mt-6 space-y-3">
      <li v-for="s in items" :key="s.id" class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs"><span v-if="s.category" class="rounded-full bg-secondary/10 px-2 py-0.5 font-medium text-secondary">{{ s.category }}</span><span>{{ formatDate(s.created_at) }}</span></div>
            <h2 class="mt-1 font-semibold text-ink">{{ s.title }}</h2>
          </div>
          <div class="flex shrink-0 gap-1">
            <button class="btn btn-secondary !px-2.5" aria-label="Copy" @click="copy(s.content, 'Content copied')"><Copy class="h-4 w-4" /></button>
            <button class="btn btn-secondary !px-2.5 !text-red-600" aria-label="Delete" @click="deleting = s"><Trash2 class="h-4 w-4" /></button>
          </div>
        </div>
        <p class="mt-2 whitespace-pre-wrap text-sm" :class="expanded !== s.id && 'line-clamp-3'">{{ s.content }}</p>
        <div class="mt-2 flex gap-4 text-xs font-medium text-primary">
          <button @click="expanded = expanded === s.id ? null : s.id">{{ expanded === s.id ? 'Show less' : 'Show more' }}</button>
          <button @click="downloadText(s.title, s.content)">Download TXT</button>
        </div>
      </li>
    </ul>
    <Pagination :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" @change="load" />

    <ConfirmDialog :open="!!deleting" title="Delete saved content?" message="This cannot be undone." @cancel="deleting = null" @confirm="confirmDelete" />
  </div>
</template>
