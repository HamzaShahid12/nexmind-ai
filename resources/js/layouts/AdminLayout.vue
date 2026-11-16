<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LayoutDashboard, Users, ShieldCheck, Wrench, SlidersHorizontal, CreditCard, Coins, Sparkles, BarChart3, Receipt, Newspaper, Tags, HelpCircle, Inbox, Settings, LogOut, ArrowLeft, Menu } from 'lucide-vue-next'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const open = ref(false)

const nav = [
  ['/admin/dashboard', 'Dashboard', LayoutDashboard], ['/admin/users', 'Users', Users], ['/admin/roles', 'Roles', ShieldCheck],
  ['/admin/ai-tools', 'AI Tools', Wrench], ['/admin/ai-settings', 'AI Settings', SlidersHorizontal], ['/admin/plans', 'Plans', CreditCard],
  ['/admin/credits', 'Credits', Coins], ['/admin/generations', 'Generations', Sparkles], ['/admin/usage', 'Usage', BarChart3],
  ['/admin/transactions', 'Transactions', Receipt], ['/admin/blogs', 'Blog Posts', Newspaper], ['/admin/categories', 'Categories', Tags],
  ['/admin/faqs', 'FAQs', HelpCircle], ['/admin/contact-messages', 'Contact Messages', Inbox], ['/admin/settings', 'Settings', Settings],
]

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-screen bg-surface">
    <div v-if="open" class="fixed inset-0 z-40 bg-ink/40 lg:hidden" @click="open = false" />
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-ink text-slate-300 transition lg:static lg:translate-x-0" :class="open ? 'translate-x-0' : '-translate-x-full'" aria-label="Admin navigation">
      <div class="flex h-16 items-center px-5"><span class="font-display text-lg font-extrabold text-white">NexMind <span class="text-indigo-300">Admin</span></span></div>
      <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-3">
        <RouterLink v-for="n in nav" :key="n[0]" :to="n[0]" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-white/10" active-class="!bg-white/15 !text-white" @click="open = false"><component :is="n[2]" class="h-5 w-5 shrink-0" />{{ n[1] }}</RouterLink>
      </nav>
      <div class="space-y-1 border-t border-white/10 p-3">
        <RouterLink to="/dashboard" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-white/10"><ArrowLeft class="h-5 w-5" />Back to app</RouterLink>
        <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-white/10" @click="logout"><LogOut class="h-5 w-5" />Logout</button>
      </div>
    </aside>
    <div class="flex min-w-0 flex-1 flex-col">
      <header class="flex h-16 items-center justify-between border-b border-line bg-white px-4 sm:px-6">
        <button class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Open menu" @click="open = true"><Menu class="h-6 w-6" /></button>
        <div class="ml-auto text-sm font-medium text-ink">{{ auth.user?.name }}</div>
      </header>
      <main class="flex-1 p-4 sm:p-6"><RouterView :key="$route.fullPath" /></main>
    </div>
  </div>
</template>
