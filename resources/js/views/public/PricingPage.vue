<script setup>
import PricingCards from '@/components/public/PricingCards.vue'
</script>

<template>
  <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
      <h1 class="text-4xl font-extrabold">Pricing</h1>
      <p class="mt-3">The Free plan is available now. Pro and Business are shown for planning and cannot be purchased until payment processing is added.</p>
    </div>
    <div class="mt-10"><PricingCards /></div>
  </section>
</template>
