<script setup>
import { ref, onMounted } from 'vue'
import { Check } from 'lucide-vue-next'
import api from '@/services/api'

// Plan names/descriptions are structural; credit allowances are loaded from the
// backend (admin-editable). If the API is unreachable we show no numbers.
const plans = ref([
  { key: 'free', name: 'Free', available: true, cta: 'Get Started Free', to: '/register', features: ['Limited monthly AI credits', 'Access to selected AI tools', 'Basic generation history', 'Standard output'] },
  { key: 'pro', name: 'Pro', available: false, cta: 'Coming soon', features: ['Higher monthly credit allowance', 'Access to all standard AI tools', 'Extended generation history', 'Priority processing where supported'] },
  { key: 'business', name: 'Business', available: false, cta: 'Coming soon', features: ['Higher usage allowance', 'Business-focused AI tools', 'Team functionality (future)', 'Advanced usage reports'] },
])
const credits = ref({})

onMounted(async () => {
  try {
    const { data } = await api.get('/plans')
    for (const p of data.data ?? data) credits.value[p.slug] = p.monthly_credits
  } catch { /* leave credit numbers hidden */ }
})
</script>

<template>
  <div class="grid gap-6 md:grid-cols-3">
    <div v-for="p in plans" :key="p.key" class="card flex flex-col p-7" :class="p.key === 'free' && 'ring-2 ring-primary'">
      <h3 class="text-xl font-bold">{{ p.name }}</h3>
      <p v-if="credits[p.key] != null" class="mt-2 text-sm font-medium text-primary">{{ credits[p.key] }} credits / month</p>
      <p v-else class="mt-2 text-sm text-slate-500">Credit allowance configured by admin</p>
      <ul class="mt-5 flex-1 space-y-2.5 text-sm">
        <li v-for="f in p.features" :key="f" class="flex gap-2"><Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />{{ f }}</li>
      </ul>
      <RouterLink v-if="p.available" :to="p.to" class="btn btn-primary mt-6">{{ p.cta }}</RouterLink>
      <button v-else class="btn btn-secondary mt-6" disabled>{{ p.cta }}</button>
    </div>
  </div>
</template>
