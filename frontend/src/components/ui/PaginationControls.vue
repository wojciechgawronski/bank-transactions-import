<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{ page: number; lastPage: number; total: number }>()
const emit = defineEmits<{ change: [page: number] }>()

function go(page: number): void {
  if (page >= 1 && page <= props.lastPage && page !== props.page) {
    emit('change', page)
  }
}
</script>

<template>
  <nav
    v-if="lastPage > 1"
    class="flex items-center justify-between text-sm text-slate-600"
    aria-label="Paginacja"
  >
    <span>Strona {{ page }} z {{ lastPage }} · {{ total }} pozycji</span>
    <div class="flex gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="page <= 1"
        @click="go(page - 1)"
      >
        <ChevronLeft class="size-4" aria-hidden="true" /> Poprzednia
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="page >= lastPage"
        @click="go(page + 1)"
      >
        Następna <ChevronRight class="size-4" aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>
