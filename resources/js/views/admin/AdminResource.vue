<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ResourcePage from '@/components/admin/ResourcePage.vue'
import { useAuthStore } from '@/stores/auth'
import * as cfg from './adminConfigs'

// Picks the config for the current admin route (route.meta.resource). :key forces a fresh page per module.
const route = useRoute()
const auth = useAuthStore()

const config = computed(() => {
  const isSuper = (auth.user?.roles || []).includes('super_admin')
  const map = {
    plans: cfg.plansConfig, tools: cfg.toolsConfig, faqs: cfg.faqsConfig, categories: cfg.categoriesConfig, blogs: cfg.blogsConfig,
    generations: cfg.generationsConfig, transactions: cfg.transactionsConfig, credits: cfg.creditsConfig, contact: cfg.contactConfig,
    users: cfg.usersConfig(isSuper),
  }
  return map[route.meta.resource]
})
</script>

<template>
  <ResourcePage v-if="config" :key="route.meta.resource" :config="config" />
</template>
