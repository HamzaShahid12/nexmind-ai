<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip)

// rows: [{ date: '2026-01-31', generations: 3 }]  (real data from the API)
const props = defineProps({ rows: { type: Array, default: () => [] }, label: { type: String, default: 'Generations' }, field: { type: String, default: 'generations' } })

const chartData = computed(() => ({
  labels: props.rows.map((r) => r.date.slice(5)),
  datasets: [{ label: props.label, data: props.rows.map((r) => r[props.field]), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 18 }],
}))
const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } } },
}
</script>

<template>
  <div class="h-56 w-full" role="img" :aria-label="`${label} per day`"><Bar :data="chartData" :options="options" /></div>
</template>
