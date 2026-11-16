<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Menu, X } from 'lucide-vue-next'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const open = ref(false)
watch(() => route.fullPath, () => (open.value = false))

const links = [
  { to: '/', label: 'Home' },
  { to: '/ai-tools', label: 'AI Tools' },
  { to: '/features', label: 'Features' },
  { to: '/pricing', label: 'Pricing' },
  { to: '/about', label: 'About' },
  { to: '/blog', label: 'Blog' },
  { to: '/contact', label: 'Contact' },
]
const footerGroups = [
  { title: 'Product', items: [['AI Tools', '/ai-tools'], ['Features', '/features'], ['Pricing', '/pricing']] },
  { title: 'Company', items: [['About', '/about'], ['Blog', '/blog'], ['Contact', '/contact']] },
  { title: 'Support', items: [['FAQ', '/faq'], ['Log in', '/login'], ['Create account', '/register']] },
]
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <header class="sticky top-0 z-40 border-b border-line bg-white/85 backdrop-blur">
      <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6" aria-label="Main">
        <RouterLink to="/" aria-label="NexMind AI home"><BrandLogo /></RouterLink>
        <ul class="hidden items-center gap-1 lg:flex">
          <li v-for="l in links" :key="l.to">
            <RouterLink :to="l.to" class="rounded-lg px-3 py-2 text-sm font-medium text-body hover:bg-slate-100 hover:text-ink" active-class="!text-primary" exact-active-class="!text-primary">{{ l.label }}</RouterLink>
          </li>
        </ul>
        <div class="hidden items-center gap-2 lg:flex">
          <template v-if="auth.isAuthenticated">
            <RouterLink to="/dashboard" class="btn btn-primary">Go to dashboard</RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/login" class="btn btn-secondary">Login</RouterLink>
            <RouterLink to="/register" class="btn btn-primary">Get Started Free</RouterLink>
          </template>
        </div>
        <button class="rounded-lg p-2 lg:hidden" :aria-expanded="open" aria-label="Toggle menu" @click="open = !open">
          <X v-if="open" class="h-6 w-6" /><Menu v-else class="h-6 w-6" />
        </button>
      </nav>
      <div v-if="open" class="border-t border-line bg-white px-4 pb-4 pt-2 lg:hidden">
        <RouterLink v-for="l in links" :key="l.to" :to="l.to" class="block rounded-lg px-3 py-2.5 text-sm font-medium hover:bg-slate-100">{{ l.label }}</RouterLink>
        <div class="mt-3 grid gap-2">
          <RouterLink v-if="auth.isAuthenticated" to="/dashboard" class="btn btn-primary">Go to dashboard</RouterLink>
          <template v-else>
            <RouterLink to="/login" class="btn btn-secondary">Login</RouterLink>
            <RouterLink to="/register" class="btn btn-primary">Get Started Free</RouterLink>
          </template>
        </div>
      </div>
    </header>

    <main class="flex-1"><RouterView /></main>

    <footer class="border-t border-line bg-white">
      <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-4">
        <div>
          <BrandLogo />
          <p class="mt-3 max-w-xs text-sm">Work Smarter. Create Faster. Grow with AI.</p>
        </div>
        <div v-for="g in footerGroups" :key="g.title">
          <h2 class="text-sm font-semibold text-ink">{{ g.title }}</h2>
          <ul class="mt-3 space-y-2 text-sm">
            <li v-for="i in g.items" :key="i[1]"><RouterLink :to="i[1]" class="hover:text-primary">{{ i[0] }}</RouterLink></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-line py-5 text-center text-xs">© {{ new Date().getFullYear() }} NexMind AI. All rights reserved.</div>
    </footer>
  </div>
</template>
