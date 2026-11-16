<script setup>
import { ref, onMounted } from 'vue'
import api, { parseError } from '@/services/api'
import { formatDate } from '@/utils/format'
import UsageChart from '@/components/dashboard/UsageChart.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const d = ref(null)
const loading = ref(true)
const error = ref('')

// All figures come from real database queries on the backend.
onMounted(async () => {
  try { d.value = (await api.get('/admin/dashboard')).data.data } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
})

const cards = () => [
  ['Registered users', d.value.total_users], ['Active users (30 days)', d.value.active_users], ['Total generations', d.value.total_generations],
  ["Today's generations", d.value.today_generations], ['Generations this month', d.value.monthly_generations], ['Credits consumed', d.value.total_credits_consumed],
]
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
    <div v-if="loading" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><Skeleton v-for="n in 6" :key="n" class="h-24 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load analytics" :message="error" />
    <template v-else>
      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div v-for="c in cards()" :key="c[0]" class="card p-5"><p class="text-sm">{{ c[0] }}</p><p class="mt-1 text-3xl font-bold text-ink">{{ c[1] }}</p></div></div>
      <section class="card mt-6 p-5"><h2 class="font-semibold">Usage trend (30 days)</h2><div class="mt-3"><UsageChart :rows="d.usage_trend" /></div></section>
      <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="card p-5"><h2 class="font-semibold">Most popular tools</h2>
          <p v-if="!d.popular_tools.length" class="mt-3 text-sm">No generations yet.</p>
          <ul v-else class="mt-3 space-y-2 text-sm"><li v-for="t in d.popular_tools" :key="t.slug" class="flex justify-between rounded-lg bg-slate-50 px-3 py-2"><span>{{ t.name }}</span><span class="font-semibold text-ink">{{ t.total }}</span></li></ul></section>
        <section class="card p-5"><h2 class="font-semibold">Recent registrations</h2>
          <p v-if="!d.recent_registrations.length" class="mt-3 text-sm">No users yet.</p>
          <ul v-else class="mt-3 divide-y divide-line text-sm"><li v-for="u in d.recent_registrations" :key="u.id" class="py-2"><p class="font-medium text-ink">{{ u.name }}</p><p class="text-xs">{{ u.email }} · {{ formatDate(u.created_at) }}</p></li></ul></section>
        <section class="card p-5"><h2 class="font-semibold">Recent admin activity</h2>
          <p v-if="!d.recent_activities.length" class="mt-3 text-sm">No activity recorded.</p>
          <ul v-else class="mt-3 divide-y divide-line text-sm"><li v-for="(a, i) in d.recent_activities" :key="i" class="py-2"><p class="font-medium text-ink">{{ a.action }}</p><p class="text-xs">{{ a.user ?? 'System' }} · {{ formatDate(a.created_at, true) }}</p></li></ul></section>
      </div>
    </template>
  </div>
</template>
