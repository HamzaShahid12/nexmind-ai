<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { LayoutDashboard, MessageSquare, PenLine, ShoppingBag, Share2, SearchCheck, Mail, FileText, History, Bookmark, Coins, Settings, LifeBuoy, LogOut, PanelLeftClose, PanelLeftOpen, Menu } from 'lucide-vue-next'
import BrandLogo from '@/components/ui/BrandLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const collapsed = ref(false)
const mobileOpen = ref(false)

const nav = [
  { to: '/dashboard', label: 'Overview', icon: LayoutDashboard },
  { to: '/dashboard/ai-chat', label: 'AI Chat', icon: MessageSquare },
  { to: '/dashboard/content-writer', label: 'AI Content Writer', icon: PenLine },
  { to: '/dashboard/product-description', label: 'Product Description', icon: ShoppingBag },
  { to: '/dashboard/social-media', label: 'Social Media Generator', icon: Share2 },
  { to: '/dashboard/seo-assistant', label: 'SEO Assistant', icon: SearchCheck },
  { to: '/dashboard/email-generator', label: 'Email Generator', icon: Mail },
  { to: '/dashboard/document-summarizer', label: 'Document Summarizer', icon: FileText },
  { to: '/dashboard/history', label: 'My History', icon: History },
  { to: '/dashboard/saved-content', label: 'Saved Content', icon: Bookmark },
  { to: '/dashboard/credits', label: 'Usage & Credits', icon: Coins },
  { to: '/dashboard/settings', label: 'Profile Settings', icon: Settings },
  { to: '/dashboard/support', label: 'Help & Support', icon: LifeBuoy },
]

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-screen bg-surface">
    <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-ink/40 lg:hidden" @click="mobileOpen = false" />
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-line bg-white transition-all lg:static lg:translate-x-0"
      :class="[collapsed ? 'lg:w-[72px]' : 'lg:w-64', mobileOpen ? 'w-64 translate-x-0' : '-translate-x-full w-64']"
      aria-label="Dashboard navigation"
    >
      <div class="flex h-16 items-center justify-between px-4">
        <BrandLogo v-if="!collapsed" />
        <button class="hidden rounded-lg p-2 hover:bg-slate-100 lg:block" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="collapsed = !collapsed">
          <PanelLeftOpen v-if="collapsed" class="h-5 w-5" /><PanelLeftClose v-else class="h-5 w-5" />
        </button>
      </div>
      <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-3">
        <RouterLink
          v-for="n in nav" :key="n.to" :to="n.to" :title="n.label"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-slate-100"
          exact-active-class="!bg-primary/10 !text-primary"
          @click="mobileOpen = false"
        >
          <component :is="n.icon" class="h-5 w-5 shrink-0" />
          <span v-if="!collapsed || mobileOpen" class="truncate">{{ n.label }}</span>
        </RouterLink>
      </nav>
      <div class="border-t border-line p-3">
        <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-red-50 hover:text-red-600" @click="logout">
          <LogOut class="h-5 w-5 shrink-0" /><span v-if="!collapsed || mobileOpen">Logout</span>
        </button>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="flex h-16 items-center justify-between border-b border-line bg-white px-4 sm:px-6">
        <button class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Open menu" @click="mobileOpen = true"><Menu class="h-6 w-6" /></button>
        <div class="ml-auto flex items-center gap-4 text-sm">
          <RouterLink v-if="auth.isAdmin" to="/admin" class="font-medium text-primary hover:underline">Admin panel</RouterLink>
          <RouterLink to="/dashboard/credits" class="rounded-full bg-primary/10 px-3 py-1 font-semibold text-primary" title="Remaining credits">{{ auth.user?.credits_balance ?? 0 }} credits</RouterLink>
          <span class="hidden font-medium text-ink sm:inline">{{ auth.user?.name }}</span>
        </div>
      </header>
      <main class="flex-1 p-4 sm:p-6"><RouterView :key="$route.fullPath" /></main>
    </div>
  </div>
</template>
