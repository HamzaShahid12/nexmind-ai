<script setup>
defineProps({ class: { type: String, default: 'h-4 w-full' } })
</script>

<template>
  <div class="animate-pulse rounded-lg bg-slate-200" :class="$props.class" aria-hidden="true" />
</template>
