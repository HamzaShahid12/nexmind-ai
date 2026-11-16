<script setup>
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4" aria-live="polite">
    <div
      v-for="t in toast.items"
      :key="t.id"
      role="status"
      class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg"
      :class="t.type === 'error' ? 'border-red-200' : 'border-emerald-200'"
    >
      <AlertCircle v-if="t.type === 'error'" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
      <CheckCircle2 v-else class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
      <p class="flex-1 text-sm text-ink">{{ t.message }}</p>
      <button class="text-slate-400 hover:text-ink" aria-label="Dismiss" @click="toast.remove(t.id)"><X class="h-4 w-4" /></button>
    </div>
  </div>
</template>
