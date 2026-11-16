<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

defineProps({ page: { type: Number, default: 1 }, lastPage: { type: Number, default: 1 }, total: { type: Number, default: 0 } })
defineEmits(['change'])
</script>

<template>
  <nav v-if="lastPage > 1" class="mt-4 flex items-center justify-between text-sm" aria-label="Pagination">
    <span>Page {{ page }} of {{ lastPage }} <span v-if="total">· {{ total }} items</span></span>
    <div class="flex gap-2">
      <button class="btn btn-secondary !px-3" :disabled="page <= 1" aria-label="Previous page" @click="$emit('change', page - 1)"><ChevronLeft class="h-4 w-4" /></button>
      <button class="btn btn-secondary !px-3" :disabled="page >= lastPage" aria-label="Next page" @click="$emit('change', page + 1)"><ChevronRight class="h-4 w-4" /></button>
    </div>
  </nav>
</template>
