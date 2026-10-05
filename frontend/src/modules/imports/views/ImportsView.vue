<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { RefreshCw } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { apiErrorMessage } from '@/api/http'
import FileDropzone from '../components/FileDropzone.vue'
import ImportsTable from '../components/ImportsTable.vue'
import { useImportsStore } from '../stores/imports'

const store = useImportsStore()
const { imports, loading, uploading, error } = storeToRefs(store)

onMounted(() => store.fetchImports())

async function onSelect(file: File): Promise<void> {
  try {
    await store.upload(file)
    toast.success(`Plik ${file.name} został przyjęty do importu.`)
  } catch (e) {
    toast.error(apiErrorMessage(e, 'Nie udało się wysłać pliku.'))
  }
}
</script>

<template>
  <div class="space-y-6">
    <section aria-labelledby="upload-heading" class="space-y-3">
      <h2 id="upload-heading" class="text-lg font-semibold text-slate-800">Nowy import</h2>
      <FileDropzone :busy="uploading" @select="onSelect" />
    </section>

    <section aria-labelledby="history-heading" class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 id="history-heading" class="text-lg font-semibold text-slate-800">Historia importów</h2>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
          :disabled="loading"
          @click="store.fetchImports()"
        >
          <RefreshCw class="size-4" :class="loading && 'animate-spin'" aria-hidden="true" /> Odśwież
        </button>
      </div>

      <p v-if="error" class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
        {{ error }}
      </p>

      <ImportsTable :imports="imports" :loading="loading" />
    </section>
  </div>
</template>
