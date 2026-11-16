<script setup>
import { ref, onMounted, watch } from 'vue'
import { Search } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'

const posts = ref([])
const cats = ref([])
const q = ref('')
const category = ref('')
const loading = ref(true)
const error = ref('')
let timer

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/blog/posts', { params: { search: q.value || undefined, category: category.value || undefined } })
    posts.value = data.data ?? []
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  api.get('/blog/categories').then((r) => (cats.value = r.data.data ?? [])).catch(() => {})
  load()
})
watch(q, () => { clearTimeout(timer); timer = setTimeout(load, 300) })
watch(category, load)
</script>

<template>
  <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <h1 class="text-4xl font-extrabold">Blog</h1>
    <p class="mt-3">Guides and updates from the NexMind AI team.</p>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
      <div class="relative flex-1"><Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" /><input v-model="q" type="search" class="input pl-10" placeholder="Search articles…" aria-label="Search articles" /></div>
      <select v-model="category" class="input sm:w-56" aria-label="Category"><option value="">All categories</option><option v-for="c in cats" :key="c.id" :value="c.slug">{{ c.name }}</option></select>
    </div>

    <div v-if="loading" class="mt-8 grid gap-6 md:grid-cols-3"><Skeleton v-for="n in 3" :key="n" class="h-64 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-8" title="Could not load articles" :message="error"><button class="btn btn-secondary" @click="load">Try again</button></EmptyState>
    <EmptyState v-else-if="!posts.length" class="mt-8" title="No articles yet" message="Check back soon, or adjust your search." />
    <div v-else class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="p in posts" :key="p.id" class="card overflow-hidden">
        <img v-if="p.featured_image" :src="p.featured_image" :alt="p.title" loading="lazy" class="h-44 w-full object-cover" />
        <div class="p-5">
          <span v-if="p.category" class="text-xs font-semibold text-primary">{{ p.category.name }}</span>
          <h2 class="mt-1 text-lg font-semibold"><RouterLink :to="`/blog/${p.slug}`" class="hover:text-primary">{{ p.title }}</RouterLink></h2>
          <p class="mt-2 text-sm">{{ p.excerpt }}</p>
        </div>
      </article>
    </div>
  </section>
</template>
