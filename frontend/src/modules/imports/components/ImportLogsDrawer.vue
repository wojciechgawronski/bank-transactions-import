<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogOverlay,
  DialogPortal,
  DialogRoot,
  DialogTitle,
} from 'reka-ui'
import { CircleCheck, LoaderCircle, X } from 'lucide-vue-next'
import { isFinished } from '@/api/imports'
import PaginationControls from '@/components/ui/PaginationControls.vue'
import { formatDateTime } from '@/lib/format'
import { useImportDetails } from '../composables/useImportDetails'
import { useImportsStore } from '../stores/imports'
import StatCards from './StatCards.vue'
import StatusBadge from './StatusBadge.vue'

/** Route param from /imports/:id, so the drawer can be opened from a shared link. */
const props = defineProps<{ id: string }>()

const router = useRouter()
const store = useImportsStore()
const importId = computed(() => Number(props.id))
const { details, logs, meta, loading, error, load } = useImportDetails(importId)

// The list is polled; reload the drawer when it reports a new status for this import.
watch(
  () => store.imports.find((item) => item.id === importId.value)?.status,
  (status, previous) => {
    if (status && previous && status !== previous) {
      load(meta.value?.current_page ?? 1)
    }
  },
)

function close(): void {
  router.push({ name: 'imports' })
}
</script>

<template>
  <DialogRoot :open="true" @update:open="(open) => !open && close()">
    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-40 bg-slate-900/30" />
      <DialogContent
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-2xl flex-col bg-slate-50 shadow-xl focus:outline-none"
      >
        <header
          class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"
        >
          <div class="min-w-0 space-y-1">
            <DialogTitle class="truncate text-lg font-semibold text-slate-800">
              {{ details?.file_name ?? `Import #${id}` }}
            </DialogTitle>
            <DialogDescription class="flex items-center gap-2 text-sm text-slate-500">
              <template v-if="details">
                <StatusBadge :status="details.status" />
                <span>{{ formatDateTime(details.created_at) }}</span>
              </template>
              <span v-else>Szczegóły importu i błędy walidacji</span>
            </DialogDescription>
          </div>
          <DialogClose
            class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            aria-label="Zamknij"
          >
            <X class="size-5" aria-hidden="true" />
          </DialogClose>
        </header>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
          <p
            v-if="error"
            class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700"
            role="alert"
          >
            {{ error }}
          </p>

          <LoaderCircle
            v-if="loading && !details"
            class="mx-auto size-8 animate-spin text-sky-600"
            aria-label="Ładowanie"
          />

          <template v-if="details">
            <StatCards :item="details" />

            <p
              v-if="!isFinished(details.status)"
              class="rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-700"
            >
              Import jest w trakcie przetwarzania. Wyniki pojawią się tu automatycznie.
            </p>

            <section v-else aria-labelledby="logs-heading" class="space-y-3">
              <h3 id="logs-heading" class="font-semibold text-slate-800">Błędy walidacji</h3>

              <div
                v-if="logs.length === 0 && !loading"
                class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-6 text-sm text-slate-600"
              >
                <CircleCheck class="size-5 text-emerald-600" aria-hidden="true" />
                Wszystkie rekordy zostały zaimportowane poprawnie.
              </div>

              <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                  <thead
                    class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                  >
                    <tr>
                      <th scope="col" class="px-4 py-3">Transaction ID</th>
                      <th scope="col" class="px-4 py-3">Błąd</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100" :class="loading && 'opacity-50'">
                    <tr v-for="log in logs" :key="log.id" data-testid="log-row">
                      <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700">
                        {{ log.transaction_id ?? '—' }}
                      </td>
                      <td class="px-4 py-3 text-slate-700">{{ log.error_message }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <PaginationControls
                v-if="meta"
                :page="meta.current_page"
                :last-page="meta.last_page"
                :total="meta.total"
                @change="load"
              />
            </section>
          </template>
        </div>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
