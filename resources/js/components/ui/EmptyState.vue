<script setup>
defineProps({ title: String, message: String })
</script>

<template>
  <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line bg-white px-6 py-12 text-center">
    <slot name="icon" />
    <h3 class="mt-3 text-base font-semibold">{{ title }}</h3>
    <p v-if="message" class="mt-1 max-w-sm text-sm">{{ message }}</p>
    <div class="mt-4"><slot /></div>
  </div>
</template>
