<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api, { parseError } from '@/services/api'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'

const route = useRoute()
const post = ref(null)
const loading = ref(true)
const error = ref('')

// Content is rendered as plain-text paragraphs (never v-html) to prevent XSS.
const paragraphs = computed(() => (post.value?.content || '').split(/\n{2,}/).filter(Boolean))

function setMeta(name, content, attr = 'name') {
  if (!content) return
  let el = document.head.querySelector(`meta[${attr}="${name}"]`)
  if (!el) { el = document.createElement('meta'); el.setAttribute(attr, name); document.head.appendChild(el) }
  el.setAttribute('content', content)
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/blog/posts/${route.params.slug}`)
    post.value = data.data ?? data
    document.title = `${post.value.meta_title || post.value.title} | NexMind AI`
    setMeta('description', post.value.meta_description || post.value.excerpt)
    setMeta('og:title', post.value.meta_title || post.value.title, 'property')
    setMeta('og:description', post.value.meta_description || post.value.excerpt, 'property')
    if (post.value.featured_image) setMeta('og:image', post.value.featured_image, 'property')
  } catch (e) {
    error.value = e?.response?.status === 404 ? 'This article could not be found.' : parseError(e).message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <RouterLink to="/blog" class="text-sm text-primary">← All articles</RouterLink>
    <Skeleton v-if="loading" class="mt-6 h-64 w-full" />
    <EmptyState v-else-if="error" class="mt-6" title="Article unavailable" :message="error" />
    <template v-else-if="post">
      <h1 class="mt-4 text-4xl font-extrabold">{{ post.title }}</h1>
      <img v-if="post.featured_image" :src="post.featured_image" :alt="post.title" class="mt-6 w-full rounded-2xl" />
      <div class="mt-8 space-y-4 leading-relaxed"><p v-for="(p, i) in paragraphs" :key="i">{{ p }}</p></div>
    </template>
  </article>
</template>
