<script setup lang="ts">
import { computed } from 'vue'
import type { Import } from '@/api/imports'

const props = defineProps<{ item: Import }>()

const cards = computed(() => [
  { label: 'Wszystkie rekordy', value: props.item.total_records, classes: 'text-slate-800' },
  { label: 'Poprawne', value: props.item.successful_records, classes: 'text-emerald-700' },
  { label: 'Błędne', value: props.item.failed_records, classes: 'text-rose-700' },
])
</script>

<template>
  <dl class="grid grid-cols-3 gap-3">
    <div
      v-for="card in cards"
      :key="card.label"
      class="rounded-xl border border-slate-200 bg-white px-4 py-3"
    >
      <dt class="text-xs font-medium text-slate-500">{{ card.label }}</dt>
      <dd class="mt-1 text-2xl font-semibold tabular-nums" :class="card.classes">
        {{ card.value }}
      </dd>
    </div>
  </dl>
</template>
