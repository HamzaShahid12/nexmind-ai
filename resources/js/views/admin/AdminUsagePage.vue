<script setup>
import { ref, onMounted, watch } from 'vue'
import api, { parseError } from '@/services/api'
import { formatDate } from '@/utils/format'
import UsageChart from '@/components/dashboard/UsageChart.vue'
import Pagination from '@/components/ui/Pagination.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const summary = ref(null)
const logs = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const status = ref('')
const loading = ref(true)
const error = ref('')

async function load(page = 1) {
  try {
    const { data } = await api.get('/admin/usage', { params: { page, status: status.value || undefined } })
    summary.value = data.summary
    logs.value = data.logs.data
    meta.value = data.logs
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
}
onMounted(load)
watch(status, () => load(1))
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <h1 class="text-2xl font-bold">AI Usage</h1>
    <Skeleton v-if="loading" class="mt-6 h-40 w-full" />
    <EmptyState v-else-if="error" class="mt-6" title="Could not load usage" :message="error" />
    <template v-else>
      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5"><p class="text-sm">AI requests</p><p class="mt-1 text-2xl font-bold text-ink">{{ summary.requests_total }}</p></div>
        <div class="card p-5"><p class="text-sm">Failed requests</p><p class="mt-1 text-2xl font-bold text-ink">{{ summary.requests_failed }}</p></div>
        <div class="card p-5"><p class="text-sm">Prompt tokens</p><p class="mt-1 text-2xl font-bold text-ink">{{ summary.prompt_tokens.toLocaleString() }}</p></div>
        <div class="card p-5"><p class="text-sm">Completion tokens</p><p class="mt-1 text-2xl font-bold text-ink">{{ summary.completion_tokens.toLocaleString() }}</p></div>
      </div>
      <section class="card mt-6 p-5"><h2 class="font-semibold">Generations per day</h2><div class="mt-3"><UsageChart :rows="summary.trend" /></div></section>
      <section class="card mt-6 p-5">
        <div class="flex items-center justify-between gap-3"><h2 class="font-semibold">Request log</h2><select v-model="status" class="input !w-40" aria-label="Filter by status"><option value="">All</option><option value="success">Success</option><option value="error">Errors</option></select></div>
        <p v-if="!logs.length" class="mt-4 text-sm">No AI requests logged.</p>
        <div v-else class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 pr-3">Date</th><th class="pr-3">User</th><th class="pr-3">Tool</th><th class="pr-3">Model</th><th class="pr-3">Tokens</th><th class="pr-3">Status</th><th>ms</th></tr></thead>
          <tbody class="divide-y divide-line"><tr v-for="l in logs" :key="l.id"><td class="whitespace-nowrap py-2 pr-3">{{ formatDate(l.created_at, true) }}</td><td class="pr-3">{{ l.user?.name ?? '—' }}</td><td class="pr-3">{{ l.tool?.name ?? '—' }}</td><td class="pr-3">{{ l.model ?? '—' }}</td><td class="pr-3">{{ (l.prompt_tokens ?? 0) + (l.completion_tokens ?? 0) }}</td><td class="pr-3" :class="l.status === 'error' ? 'text-red-600' : 'text-emerald-600'">{{ l.status }}<span v-if="l.error_code"> ({{ l.error_code }})</span></td><td>{{ l.duration_ms }}</td></tr></tbody></table></div>
        <Pagination :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" @change="load" />
      </section>
    </template>
  </div>
</template>
