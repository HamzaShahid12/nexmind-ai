<script setup>
import { PenLine, ShoppingBag, Share2, SearchCheck, FileText, MessageSquare, BarChart3 } from 'lucide-vue-next'

const features = [
  { icon: PenLine, title: 'AI-powered content creation', text: 'Generate articles, website copy, bios and marketing text in six tones and many languages.' },
  { icon: ShoppingBag, title: 'Ecommerce productivity', text: 'Produce titles, descriptions, key features, meta tags and product tags for Shopify, WooCommerce and more.' },
  { icon: Share2, title: 'Marketing assistance', text: 'Create captions, hashtags and calls to action with multiple variations per platform.' },
  { icon: SearchCheck, title: 'SEO optimization', text: 'Get title, meta and keyword suggestions. These are recommendations, not guaranteed rankings.' },
  { icon: FileText, title: 'Document summarization', text: 'Upload PDF, TXT or DOCX files, get key points and ask follow-up questions.' },
  { icon: MessageSquare, title: 'AI conversation', text: 'Chat with an assistant, rename and revisit conversations any time.' },
  { icon: BarChart3, title: 'Usage tracking', text: 'See credits, history and monthly usage drawn from your own account data.' },
]
</script>

<template>
  <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
      <h1 class="text-4xl font-extrabold">Features</h1>
      <p class="mt-3">Built for businesses, ecommerce owners, marketers and creators.</p>
    </div>
    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="f in features" :key="f.title" class="card p-6">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary/10 text-primary"><component :is="f.icon" class="h-5 w-5" /></span>
        <h2 class="mt-4 text-lg font-semibold">{{ f.title }}</h2>
        <p class="mt-1.5 text-sm">{{ f.text }}</p>
      </article>
    </div>
  </section>
</template>
