<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { MessageSquare, PenLine, ShoppingBag, Share2, SearchCheck, Mail, FileText } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { formatDate } from '@/utils/format'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import UsageChart from '@/components/dashboard/UsageChart.vue'

const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const stats = ref(null)
const usage = ref([])
const loading = ref(true)
const error = ref('')

const quick = [
  ['AI Chat', '/dashboard/ai-chat', MessageSquare], ['Content Writer', '/dashboard/content-writer', PenLine],
  ['Product Description', '/dashboard/product-description', ShoppingBag], ['Social Media', '/dashboard/social-media', Share2],
  ['SEO Assistant', '/dashboard/seo-assistant', SearchCheck], ['Email Generator', '/dashboard/email-generator', Mail],
  ['Document Summarizer', '/dashboard/document-summarizer', FileText],
]

// Every number shown here comes from the backend; nothing is hardcoded.
onMounted(async () => {
  if (route.query.verified) toast.success('Your email has been verified.')
  try {
    const [d, a] = await Promise.all([api.get('/dashboard'), api.get('/dashboard/analytics', { params: { days: 30 } })])
    stats.value = d.data.data
    usage.value = a.data.data
    if (auth.user) auth.user.credits_balance = stats.value.credits_balance
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
})
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <h1 class="text-2xl font-bold">Welcome back, {{ auth.user?.name }}</h1>
    <p v-if="auth.user && !auth.user.email_verified_at" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Please verify your email address. <RouterLink to="/verify-email" class="font-semibold underline">Resend verification email</RouterLink></p>

    <div v-if="loading" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><Skeleton v-for="n in 4" :key="n" class="h-24 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load your dashboard" :message="error" />
    <template v-else>
      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5"><p class="text-sm">Current plan</p><p class="mt-1 text-2xl font-bold text-ink">{{ stats.plan?.name ?? '—' }}</p></div>
        <div class="card p-5"><p class="text-sm">Remaining credits</p><p class="mt-1 text-2xl font-bold text-ink">{{ stats.credits_balance }}</p></div>
        <div class="card p-5"><p class="text-sm">Total generations</p><p class="mt-1 text-2xl font-bold text-ink">{{ stats.total_generations }}</p></div>
        <div class="card p-5"><p class="text-sm">This month</p><p class="mt-1 text-2xl font-bold text-ink">{{ stats.monthly_generations }}</p><p class="text-xs">{{ stats.monthly_credits_used }} credits used</p></div>
      </div>

      <section class="card mt-6 p-5"><h2 class="font-semibold">Usage (last 30 days)</h2><div class="mt-3"><UsageChart :rows="usage" /></div></section>

      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
          <h2 class="font-semibold">Recent activity</h2>
          <p v-if="!stats.recent_activity.length" class="mt-3 text-sm">Nothing yet. Try one of the tools below.</p>
          <ul v-else class="mt-3 divide-y divide-line text-sm">
            <li v-for="a in stats.recent_activity" :key="a.id" class="flex items-center justify-between gap-3 py-2"><div class="min-w-0"><p class="truncate font-medium text-ink">{{ a.title }}</p><p class="text-xs">{{ a.tool }} · {{ formatDate(a.created_at, true) }}</p></div><span class="shrink-0 text-xs">{{ a.credits_used }} cr</span></li>
          </ul>
        </section>
        <section class="card p-5">
          <h2 class="font-semibold">Frequently used tools</h2>
          <p v-if="!stats.top_tools.length" class="mt-3 text-sm">Your most-used tools will show up here.</p>
          <ul v-else class="mt-3 space-y-2 text-sm"><li v-for="t in stats.top_tools" :key="t.slug" class="flex justify-between rounded-lg bg-slate-50 px-3 py-2"><span>{{ t.name }}</span><span class="font-semibold text-ink">{{ t.total }}</span></li></ul>
        </section>
      </div>

      <section class="mt-6"><h2 class="font-semibold">Quick access</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <RouterLink v-for="q in quick" :key="q[1]" :to="q[1]" class="card flex items-center gap-3 p-4 text-sm font-medium text-ink transition hover:shadow-md"><component :is="q[2]" class="h-5 w-5 text-primary" />{{ q[0] }}</RouterLink>
        </div>
      </section>
    </template>
  </div>
</template>
