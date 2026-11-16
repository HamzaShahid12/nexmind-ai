<script setup>
import { ref, onMounted, watch } from 'vue'
import api, { parseError } from '@/services/api'
import { formatDate } from '@/utils/format'
import UsageChart from '@/components/dashboard/UsageChart.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Pagination from '@/components/ui/Pagination.vue'

const info = ref(null)
const usage = ref([])
const tx = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const type = ref('')
const loading = ref(true)
const error = ref('')

const types = [['', 'All types'], ['signup', 'Signup'], ['renewal', 'Renewal'], ['usage', 'Usage'], ['admin_add', 'Admin credit'], ['admin_deduct', 'Admin deduction'], ['expiry', 'Expired']]

async function loadTx(page = 1) {
  try {
    const { data } = await api.get('/credits/transactions', { params: { page, type: type.value || undefined } })
    tx.value = data.data
    meta.value = data
  } catch (e) { error.value = parseError(e).message }
}

onMounted(async () => {
  try {
    const [c, u] = await Promise.all([api.get('/credits'), api.get('/dashboard/analytics', { params: { days: 30 } })])
    info.value = c.data.data
    usage.value = u.data.data
    await loadTx()
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
})
watch(type, () => loadTx(1))
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <h1 class="text-2xl font-bold">Usage &amp; Credits</h1>
    <div v-if="loading" class="mt-6 grid gap-4 sm:grid-cols-3"><Skeleton v-for="n in 3" :key="n" class="h-24 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load credits" :message="error" />
    <template v-else>
      <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="card p-5"><p class="text-sm">Remaining credits</p><p class="mt-1 text-3xl font-bold text-ink">{{ info.balance }}</p></div>
        <div class="card p-5"><p class="text-sm">Current plan</p><p class="mt-1 text-xl font-bold text-ink">{{ info.plan?.name ?? '—' }}</p><p class="text-xs">{{ info.plan?.monthly_credits }} credits / month</p></div>
        <div class="card p-5"><p class="text-sm">Next renewal</p><p class="mt-1 text-xl font-bold text-ink">{{ formatDate(info.next_renewal_at) }}</p><p class="text-xs">Unused credits expire at renewal.</p></div>
      </div>

      <section class="card mt-6 p-5"><h2 class="font-semibold">Credits used per day (last 30 days)</h2><div class="mt-3"><UsageChart :rows="usage" field="credits" label="Credits used" /></div></section>

      <section class="card mt-6 p-5">
        <h2 class="font-semibold">Tool costs</h2>
        <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2"><li v-for="t in info.tool_costs" :key="t.slug" class="flex justify-between rounded-lg bg-slate-50 px-3 py-2"><span>{{ t.name }}</span><span class="font-semibold text-ink">{{ t.credit_cost }}</span></li></ul>
      </section>

      <section class="card mt-6 p-5">
        <div class="flex items-center justify-between gap-3"><h2 class="font-semibold">Credit history</h2><select v-model="type" class="input !w-44" aria-label="Filter by type"><option v-for="t in types" :key="t[0]" :value="t[0]">{{ t[1] }}</option></select></div>
        <p v-if="!tx.length" class="mt-4 text-sm">No transactions yet.</p>
        <div v-else class="mt-3 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2 pr-3">Date</th><th class="pr-3">Type</th><th class="pr-3">Description</th><th class="pr-3 text-right">Amount</th><th class="text-right">Balance</th></tr></thead>
            <tbody class="divide-y divide-line">
              <tr v-for="t in tx" :key="t.id"><td class="py-2 pr-3 whitespace-nowrap">{{ formatDate(t.created_at, true) }}</td><td class="pr-3">{{ t.type }}</td><td class="pr-3">{{ t.description }}</td><td class="pr-3 text-right font-medium" :class="t.amount < 0 ? 'text-red-600' : 'text-emerald-600'">{{ t.amount > 0 ? '+' : '' }}{{ t.amount }}</td><td class="text-right">{{ t.balance_after }}</td></tr>
            </tbody>
          </table>
        </div>
        <Pagination :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" @change="loadTx" />
      </section>
    </template>
  </div>
</template>
