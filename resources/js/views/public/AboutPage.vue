<script setup>
const values = [
  ['Clarity', 'Tools should be simple enough to use without training.'],
  ['Honesty', 'We describe what the platform does, and what it does not.'],
  ['Security', 'Accounts, files and API keys are protected on the server.'],
  ['Usefulness', 'Every feature exists to save real working time.'],
]
const objectives = ['Give small teams practical AI tools in one place', 'Keep usage transparent through credits and history', 'Stay provider-flexible so models can improve over time']
</script>

<template>
  <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6">
    <h1 class="text-4xl font-extrabold">About NexMind AI</h1>
    <p class="mt-4 text-lg">NexMind AI is an all-in-one AI productivity platform for businesses, ecommerce owners, freelancers, marketers and content creators.</p>

    <div class="mt-10 grid gap-6 md:grid-cols-2">
      <div class="card p-6"><h2 class="text-xl font-bold">Mission</h2><p class="mt-2 text-sm">To make capable AI tools accessible and easy for everyday business work.</p></div>
      <div class="card p-6"><h2 class="text-xl font-bold">Vision</h2><p class="mt-2 text-sm">A single, trustworthy AI workspace where small teams can create faster and grow.</p></div>
    </div>

    <h2 class="mt-12 text-2xl font-bold">Core values</h2>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
      <div v-for="v in values" :key="v[0]" class="card p-5"><h3 class="font-semibold">{{ v[0] }}</h3><p class="mt-1 text-sm">{{ v[1] }}</p></div>
    </div>

    <h2 class="mt-12 text-2xl font-bold">Platform objectives</h2>
    <ul class="mt-4 list-disc space-y-2 pl-5 text-sm"><li v-for="o in objectives" :key="o">{{ o }}</li></ul>
  </section>
</template>
