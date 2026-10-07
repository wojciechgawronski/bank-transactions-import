<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Inbox } from 'lucide-vue-next'
import type { Import } from '@/api/imports'
import { formatDateTime, formatQueueConnection } from '@/lib/format'
import StatusBadge from './StatusBadge.vue'

defineProps<{ imports: Import[]; loading?: boolean }>()
</script>

<template>
  <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead
        class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
      >
        <tr>
          <th scope="col" class="px-4 py-3">Plik</th>
          <th scope="col" class="px-4 py-3 text-right">Rekordy</th>
          <th scope="col" class="px-4 py-3 text-right">Poprawne</th>
          <th scope="col" class="px-4 py-3 text-right">Błędne</th>
          <th scope="col" class="px-4 py-3">Status</th>
          <th scope="col" class="px-4 py-3">Kolejka</th>
          <th scope="col" class="px-4 py-3">Data importu</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100" :class="loading && 'opacity-50'">
        <tr v-for="item in imports" :key="item.id" data-testid="import-row">
          <td class="max-w-xs truncate px-4 py-3 font-medium" :title="item.file_name">
            <RouterLink
              :to="{ name: 'import-details', params: { id: item.id } }"
              class="text-sky-700 hover:underline"
            >
              {{ item.file_name }}
            </RouterLink>
          </td>
          <td class="px-4 py-3 text-right tabular-nums">{{ item.total_records }}</td>
          <td class="px-4 py-3 text-right tabular-nums text-emerald-700">
            {{ item.successful_records }}
          </td>
          <td class="px-4 py-3 text-right tabular-nums text-rose-700">{{ item.failed_records }}</td>
          <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
          <td class="whitespace-nowrap px-4 py-3 text-slate-600">
            {{ formatQueueConnection(item.queue_connection) }}
          </td>
          <td class="whitespace-nowrap px-4 py-3 text-slate-500">
            {{ formatDateTime(item.created_at) }}
          </td>
        </tr>
        <tr v-if="!loading && imports.length === 0">
          <td colspan="7" class="px-4 py-12 text-center text-slate-500">
            <Inbox class="mx-auto mb-2 size-8 text-slate-300" aria-hidden="true" />
            Brak importów. Wgraj pierwszy plik powyżej.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
