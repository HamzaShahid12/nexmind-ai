<script setup>
import BrandLogo from '@/components/ui/BrandLogo.vue'
</script>

<template>
  <main class="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[1.05fr_0.95fr]">
    <aside class="relative hidden min-h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
      <div class="pointer-events-none absolute -right-36 -top-32 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-40 -left-28 h-96 w-96 rounded-full bg-indigo-400/20 blur-3xl"></div>

      <RouterLink to="/" class="relative inline-flex w-fit items-center gap-3" aria-label="NexMind AI home">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 ring-1 ring-white/15">
          <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 19V5l14 14V5" />
          </svg>
        </span>
        <span class="font-display text-xl font-extrabold tracking-tight">NexMind <span class="text-blue-300">AI</span></span>
      </RouterLink>

      <div class="relative max-w-xl py-16">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-slate-300">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
             Your AI-powered business workspace
        </span>
        <h1 style="color: #60A5FA !important;"
           class="mt-7 font-display text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
             Make room for your next big idea.
        </h1>
        <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">
          Create, plan, and grow with thoughtful AI tools designed to help your business move forward.
        </p>
        <div class="mt-10 flex flex-wrap gap-3 text-sm text-slate-200">
          <span class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5">Create faster</span>
          <span class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5">Stay organized</span>
          <span class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5">Grow with confidence</span>
        </div>
      </div>

      <p class="relative text-xs text-slate-400">© {{ new Date().getFullYear() }} NexMind AI. All rights reserved.</p>
    </aside>

    <section class="flex min-h-screen flex-col items-center justify-center px-5 py-8 sm:px-8 lg:px-10">
      <RouterLink to="/" class="mb-8 lg:hidden" aria-label="NexMind AI home"><BrandLogo /></RouterLink>
      <div class="card w-full max-w-md rounded-3xl border-white p-7 shadow-xl shadow-slate-200/60 sm:p-9">
        <RouterView />
      </div>
      <RouterLink to="/" class="mt-7 text-sm font-medium text-slate-500 transition hover:text-primary">← Back to website</RouterLink>
      <p class="mt-8 text-xs text-slate-400 lg:hidden">© {{ new Date().getFullYear() }} NexMind AI</p>
    </section>
  </main>
</template>
