<script setup lang="ts">
import { computed } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import type { ImportStatus } from '@/api/imports'

const props = defineProps<{ status: ImportStatus }>()

const variants: Record<ImportStatus, { label: string; classes: string }> = {
  pending: { label: 'Oczekuje', classes: 'bg-slate-100 text-slate-700 ring-slate-300' },
  processing: { label: 'Przetwarzanie', classes: 'bg-sky-50 text-sky-700 ring-sky-300' },
  success: { label: 'Sukces', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-300' },
  partial: { label: 'Częściowy', classes: 'bg-amber-50 text-amber-800 ring-amber-300' },
  failed: { label: 'Błąd', classes: 'bg-rose-50 text-rose-700 ring-rose-300' },
}

const variant = computed(() => variants[props.status])
const busy = computed(() => props.status === 'pending' || props.status === 'processing')
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
    :class="variant.classes"
    :data-status="status"
  >
    <LoaderCircle v-if="busy" class="size-3 animate-spin" aria-hidden="true" />
    {{ variant.label }}
  </span>
</template>
