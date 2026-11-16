<script setup>
import { computed } from 'vue'
import { Copy } from 'lucide-vue-next'
import { useCopy } from '@/composables/useCopy'
import { labelize, toPlainText } from '@/utils/format'

// Renders any generation output. Everything is shown as TEXT (never v-html), so model output cannot inject markup.
const props = defineProps({ data: { type: [String, Object, Array], default: '' } })
const { copy } = useCopy()

const isObject = (v) => v !== null && typeof v === 'object' && !Array.isArray(v)
const sections = computed(() => (isObject(props.data) ? Object.entries(props.data) : []))
const isList = (v) => Array.isArray(v)
const isScalarList = (v) => Array.isArray(v) && v.every((i) => typeof i !== 'object')
</script>

<template>
  <div class="space-y-4">
    <!-- Plain text output -->
    <div v-if="typeof data === 'string'" class="rounded-xl border border-line bg-white p-4">
      <p class="whitespace-pre-wrap text-sm leading-relaxed text-ink">{{ data }}</p>
    </div>

    <!-- Structured output: one card per section with its own copy button -->
    <template v-else>
      <section v-for="[key, value] in sections" :key="key" class="rounded-xl border border-line bg-white p-4">
        <div class="mb-2 flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold text-ink">{{ labelize(key) }}</h3>
          <button class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-primary hover:bg-primary/10" :aria-label="`Copy ${labelize(key)}`" @click="copy(toPlainText(value), `${labelize(key)} copied`)">
            <Copy class="h-3.5 w-3.5" /> Copy
          </button>
        </div>

        <p v-if="!isList(value) && !isObject(value)" class="whitespace-pre-wrap text-sm text-ink">{{ value }}</p>

        <ul v-else-if="isScalarList(value)" class="list-disc space-y-1 pl-5 text-sm text-ink">
          <li v-for="(item, i) in value" :key="i">{{ item }}</li>
        </ul>

        <div v-else-if="isList(value)" class="space-y-3">
          <div v-for="(item, i) in value" :key="i" class="rounded-lg bg-slate-50 p-3">
            <OutputView :data="item" />
          </div>
        </div>

        <dl v-else class="space-y-2 text-sm">
          <div v-for="(v, k) in value" :key="k">
            <dt class="font-medium text-ink">{{ labelize(k) }}</dt>
            <dd class="whitespace-pre-wrap">{{ typeof v === 'object' ? toPlainText(v) : v }}</dd>
          </div>
        </dl>
      </section>
    </template>
  </div>
</template>
