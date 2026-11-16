<script setup>
import { HelpCircle, Mail, Coins } from 'lucide-vue-next'
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold">Help &amp; Support</h1>
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
      <RouterLink to="/faq" class="card p-5 transition hover:shadow-md"><HelpCircle class="h-6 w-6 text-primary" /><h2 class="mt-3 font-semibold">FAQ</h2><p class="mt-1 text-sm">Answers to common questions.</p></RouterLink>
      <RouterLink to="/contact" class="card p-5 transition hover:shadow-md"><Mail class="h-6 w-6 text-primary" /><h2 class="mt-3 font-semibold">Contact us</h2><p class="mt-1 text-sm">Send a message to the team.</p></RouterLink>
      <RouterLink to="/dashboard/credits" class="card p-5 transition hover:shadow-md"><Coins class="h-6 w-6 text-primary" /><h2 class="mt-3 font-semibold">Credits</h2><p class="mt-1 text-sm">See balance, costs and history.</p></RouterLink>
    </div>
    <section class="card mt-6 space-y-2 p-5 text-sm">
      <h2 class="font-semibold">Good to know</h2>
      <p>If an AI request fails, you are not charged.</p>
      <p>SEO output is a set of suggestions and does not guarantee rankings.</p>
      <p>Generated social posts are not published automatically. Copy them and post them yourself.</p>
      <p>Uploaded documents are deleted right after text extraction; the extracted text is removed after the retention period.</p>
    </section>
  </div>
</template>
