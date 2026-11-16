<script setup>
import { X } from 'lucide-vue-next'

defineProps({ open: Boolean, title: String, wide: Boolean })
defineEmits(['close'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:items-center" role="dialog" aria-modal="true" @click.self="$emit('close')" @keydown.esc="$emit('close')">
    <div class="card my-8 w-full" :class="wide ? 'max-w-3xl' : 'max-w-lg'">
      <div class="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 class="text-lg font-bold">{{ title }}</h2>
        <button class="rounded-lg p-1 hover:bg-slate-100" aria-label="Close" @click="$emit('close')"><X class="h-5 w-5" /></button>
      </div>
      <div class="p-5"><slot /></div>
    </div>
  </div>
</template>
