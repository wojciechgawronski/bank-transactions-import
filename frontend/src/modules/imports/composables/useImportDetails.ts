import { ref, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { apiErrorMessage } from '@/api/http'
import {
  getImport,
  listImportLogs,
  type Import,
  type ImportLog,
  type PaginationMeta,
} from '@/api/imports'

/**
 * Details of one import with a page of its error logs. Reloads when the id changes;
 * a response for a previous id is ignored, so fast switching cannot show stale data.
 */
export function useImportDetails(importId: MaybeRefOrGetter<number>) {
  const details = ref<Import | null>(null)
  const logs = ref<ImportLog[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  let latestRequest = 0

  async function load(page = 1): Promise<void> {
    const request = ++latestRequest
    const id = toValue(importId)
    loading.value = true
    error.value = null

    try {
      const [item, logPage] = await Promise.all([getImport(id), listImportLogs(id, page)])
      if (request !== latestRequest) {
        return
      }
      details.value = item
      logs.value = logPage.data
      meta.value = logPage.meta
    } catch (e) {
      if (request === latestRequest) {
        error.value = apiErrorMessage(e, 'Nie udało się pobrać szczegółów importu.')
      }
    } finally {
      if (request === latestRequest) {
        loading.value = false
      }
    }
  }

  watch(
    () => toValue(importId),
    () => load(1),
    { immediate: true },
  )

  return { details, logs, meta, loading, error, load }
}
