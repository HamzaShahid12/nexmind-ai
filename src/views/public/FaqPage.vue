<script setup>
import { ref, onMounted, computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'

const faqs = ref([])
const loading = ref(true)
const error = ref('')
const open = ref(null)

// Group admin-managed FAQs by category.
const groups = computed(() => {
  const map = {}
  for (const f of faqs.value) (map[f.category || 'General'] ||= []).push(f)
  return Object.entries(map)
})

onMounted(async () => {
  try {
    const { data } = await api.get('/faqs')
    faqs.value = data.data ?? []
  } catch (e) {
    error.value = parseError(e).message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <h1 class="text-4xl font-extrabold">Frequently asked questions</h1>
    <Skeleton v-if="loading" class="mt-8 h-40 w-full" />
    <EmptyState v-else-if="error" class="mt-8" title="Could not load FAQs" :message="error" />
    <EmptyState v-else-if="!groups.length" class="mt-8" title="No FAQs yet" message="Questions will appear here once published." />
    <div v-else class="mt-8 space-y-8">
      <div v-for="[name, items] in groups" :key="name">
        <h2 class="mb-3 text-lg font-bold">{{ name }}</h2>
        <div class="divide-y divide-line rounded-2xl border border-line bg-white">
          <div v-for="f in items" :key="f.id">
            <button class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium text-ink" :aria-expanded="open === f.id" @click="open = open === f.id ? null : f.id">{{ f.question }}<ChevronDown class="h-4 w-4 shrink-0 transition" :class="open === f.id && 'rotate-180'" /></button>
            <p v-if="open === f.id" class="px-5 pb-4 text-sm">{{ f.answer }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
