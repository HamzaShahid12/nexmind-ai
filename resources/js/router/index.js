import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const PublicLayout = () => import('@/layouts/PublicLayout.vue')
const AuthLayout = () => import('@/layouts/AuthLayout.vue')
const DashboardLayout = () => import('@/layouts/DashboardLayout.vue')
const AdminLayout = () => import('@/layouts/AdminLayout.vue')

const page = (name) => () => import(`../views/public/${name}.vue`)
const auth = (name) => () => import(`../views/auth/${name}.vue`)
const dash = (name) => () => import(`../views/dashboard/${name}.vue`)
const adm = (name) => () => import(`../views/admin/${name}.vue`)

const DEFAULT_TITLE = 'NexMind AI – Your All-in-One AI Business Assistant'

const tool = (path, slug, title) => ({ path, name: slug, component: dash('ToolPage'), props: { slug }, meta: { title } })
const resource = (path, resource, title) => ({ path, name: `admin-${path}`, component: adm('AdminResource'), meta: { resource, title } })

export const routes = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '', name: 'home', component: page('HomePage'), meta: { title: DEFAULT_TITLE } },
      { path: 'features', name: 'features', component: page('FeaturesPage'), meta: { title: 'Features' } },
      { path: 'ai-tools', name: 'ai-tools', component: page('AiToolsPage'), meta: { title: 'AI Tools' } },
      { path: 'pricing', name: 'pricing', component: page('PricingPage'), meta: { title: 'Pricing' } },
      { path: 'about', name: 'about', component: page('AboutPage'), meta: { title: 'About' } },
      { path: 'contact', name: 'contact', component: page('ContactPage'), meta: { title: 'Contact' } },
      { path: 'blog', name: 'blog', component: page('BlogPage'), meta: { title: 'Blog' } },
      { path: 'blog/:slug', name: 'blog-post', component: page('BlogPostPage'), meta: { title: 'Blog' } },
      { path: 'faq', name: 'faq', component: page('FaqPage'), meta: { title: 'FAQ' } },
    ],
  },
  {
    path: '/',
    component: AuthLayout,
    children: [
      { path: 'login', name: 'login', component: auth('LoginPage'), meta: { title: 'Log in', guest: true } },
      { path: 'register', name: 'register', component: auth('RegisterPage'), meta: { title: 'Create account', guest: true } },
      { path: 'forgot-password', name: 'forgot-password', component: auth('ForgotPasswordPage'), meta: { title: 'Forgot password', guest: true } },
      { path: 'reset-password', name: 'reset-password', component: auth('ResetPasswordPage'), meta: { title: 'Reset password', guest: true } },
      { path: 'verify-email', name: 'verify-email', component: auth('VerifyEmailPage'), meta: { title: 'Verify email' } },
    ],
  },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { requiresAuth: true, noindex: true },
    children: [
      { path: '', name: 'dashboard', component: dash('OverviewPage'), meta: { title: 'Dashboard' } },
      { path: 'ai-chat', name: 'ai-chat', component: dash('ChatPage'), meta: { title: 'AI Chat' } },
      tool('content-writer', 'content-writer', 'AI Content Writer'),
      tool('product-description', 'product-description', 'Product Description Generator'),
      tool('social-media', 'social-media', 'Social Media Generator'),
      tool('seo-assistant', 'seo-assistant', 'SEO Assistant'),
      tool('email-generator', 'email-generator', 'Email Generator'),
      { path: 'document-summarizer', name: 'document-summarizer', component: dash('SummarizerPage'), meta: { title: 'Document Summarizer' } },
      { path: 'history', name: 'history', component: dash('HistoryPage'), meta: { title: 'My History' } },
      { path: 'saved-content', name: 'saved-content', component: dash('SavedContentPage'), meta: { title: 'Saved Content' } },
      { path: 'credits', name: 'credits', component: dash('CreditsPage'), meta: { title: 'Usage & Credits' } },
      { path: 'settings', name: 'settings', component: dash('SettingsPage'), meta: { title: 'Profile Settings' } },
      { path: 'support', name: 'support', component: dash('SupportPage'), meta: { title: 'Help & Support' } },
    ],
  },
  {
    path: '/admin',
    component: AdminLayout,
    redirect: '/admin/dashboard',
    // UX guard only. Every /api/v1/admin route is authorized again on the server.
    meta: { requiresAuth: true, requiresAdmin: true, noindex: true },
    children: [
      { path: 'dashboard', name: 'admin-dashboard', component: adm('AdminDashboardPage'), meta: { title: 'Admin Dashboard' } },
      resource('users', 'users', 'Users'),
      { path: 'roles', name: 'admin-roles', component: adm('AdminRolesPage'), meta: { title: 'Roles' } },
      resource('ai-tools', 'tools', 'AI Tools'),
      { path: 'ai-settings', name: 'admin-ai-settings', component: adm('AdminSettingsPage'), meta: { title: 'AI Settings', kind: 'ai' } },
      resource('plans', 'plans', 'Plans'),
      resource('credits', 'credits', 'Credits'),
      resource('generations', 'generations', 'Generations'),
      { path: 'usage', name: 'admin-usage', component: adm('AdminUsagePage'), meta: { title: 'Usage' } },
      resource('transactions', 'transactions', 'Transactions'),
      resource('blogs', 'blogs', 'Blog Posts'),
      resource('categories', 'categories', 'Categories'),
      resource('faqs', 'faqs', 'FAQs'),
      resource('contact-messages', 'contact', 'Contact Messages'),
      { path: 'settings', name: 'admin-settings', component: adm('AdminSettingsPage'), meta: { title: 'Settings', kind: 'general' } },
    ],
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: page('NotFoundPage'), meta: { title: 'Page not found' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (to, from, saved) => saved || { top: 0 },
})

router.beforeEach(async (to) => {
  const store = useAuthStore()
  if (!store.ready) await store.fetchUser()

  if (to.matched.some((r) => r.meta.requiresAuth) && !store.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.matched.some((r) => r.meta.requiresAdmin) && !store.isAdmin) {
    return { name: 'dashboard' }
  }
  if (to.meta.guest && store.isAuthenticated) return { name: 'dashboard' }
})

router.afterEach((to) => {
  const title = to.meta.title
  document.title = title && title !== DEFAULT_TITLE ? `${title} | NexMind AI` : DEFAULT_TITLE

  let robots = document.querySelector('meta[name="robots"]')
  if (!robots) {
    robots = document.createElement('meta')
    robots.name = 'robots'
    document.head.appendChild(robots)
  }
  // Private areas are never indexed.
  robots.content = to.matched.some((r) => r.meta.noindex) ? 'noindex, nofollow' : 'index, follow'
})

export default router
