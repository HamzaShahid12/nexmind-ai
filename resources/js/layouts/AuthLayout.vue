<script setup>
import BrandLogo from '@/components/ui/BrandLogo.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-white to-surface px-4 py-10">
    <RouterLink to="/" class="mb-8" aria-label="NexMind AI home"><BrandLogo /></RouterLink>
    <div class="card w-full max-w-md p-7 sm:p-8"><RouterView /></div>
    <RouterLink to="/" class="mt-6 text-sm hover:text-primary">← Back to website</RouterLink>
  </div>
</template>
