<script setup>
import { ref, computed } from 'vue'
import { Search, Lock } from 'lucide-vue-next'
import { tools, categories } from '@/utils/tools'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const query = ref('')
const category = ref('All')

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  return tools.filter((t) => (category.value === 'All' || t.category === category.value) && (!q || t.name.toLowerCase().includes(q) || t.description.toLowerCase().includes(q)))
})
const href = (t) => (auth.isAuthenticated ? t.path : `/login?redirect=${encodeURIComponent(t.path)}`)
</script>

<template>
  <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
      <h1 class="text-4xl font-extrabold">AI Tools</h1>
      <p class="mt-3">Browse every tool available in your workspace.</p>
    </div>

    <div class="mx-auto mt-8 flex max-w-3xl flex-col gap-4">
      <div class="relative">
        <Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
        <input v-model="query" type="search" class="input pl-10" placeholder="Search tools…" aria-label="Search tools" />
      </div>
      <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by category">
        <button v-for="c in ['All', ...categories]" :key="c" class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition" :class="category === c ? 'border-primary bg-primary text-white' : 'border-line bg-white hover:bg-slate-50'" @click="category = c">{{ c }}</button>
      </div>
    </div>

    <div v-if="filtered.length" class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="t in filtered" :key="t.slug" class="card flex flex-col p-6">
        <div class="flex items-start justify-between">
          <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary/10 text-primary"><component :is="t.icon" class="h-5 w-5" /></span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium">{{ t.category }}</span>
        </div>
        <h2 class="mt-4 text-lg font-semibold">{{ t.name }}</h2>
        <p class="mt-1.5 flex-1 text-sm">{{ t.description }}</p>
        <RouterLink :to="href(t)" class="btn btn-secondary mt-5">
          <Lock v-if="!auth.isAuthenticated" class="h-4 w-4" />{{ auth.isAuthenticated ? 'Open tool' : 'Log in to use' }}
        </RouterLink>
      </article>
    </div>
    <EmptyState v-else class="mt-10" title="No tools found" message="Try a different search term or category." />
  </section>
</template>
