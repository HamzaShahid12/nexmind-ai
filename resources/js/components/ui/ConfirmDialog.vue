<script setup>
defineProps({
  open: Boolean,
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: 'This action cannot be undone.' },
  confirmLabel: { type: String, default: 'Delete' },
  loading: Boolean,
})
defineEmits(['confirm', 'cancel'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-[90] flex items-center justify-center bg-ink/40 p-4" role="dialog" aria-modal="true" @click.self="$emit('cancel')">
    <div class="card w-full max-w-md p-6">
      <h3 class="text-lg font-bold">{{ title }}</h3>
      <p class="mt-2 text-sm">{{ message }}</p>
      <div class="mt-6 flex justify-end gap-3">
        <button class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
        <button class="btn btn-danger" :disabled="loading" @click="$emit('confirm')">{{ loading ? 'Working…' : confirmLabel }}</button>
      </div>
    </div>
  </div>
</template>
