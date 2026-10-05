import { useIntervalFn } from '@vueuse/core'
import { watch } from 'vue'
import { useImportsStore } from '../stores/imports'

export const POLLING_INTERVAL_MS = 2500

/**
 * Refreshes the imports list every few seconds, but only while the current
 * page has an import that is still pending or processing.
 */
export function useImportPolling(intervalMs = POLLING_INTERVAL_MS) {
  const store = useImportsStore()
  let inFlight = false

  const { pause, resume, isActive } = useIntervalFn(
    async () => {
      if (inFlight) {
        return
      }
      inFlight = true
      try {
        await store.refresh()
      } finally {
        inFlight = false
      }
    },
    intervalMs,
    { immediate: false },
  )

  watch(
    () => store.hasUnfinished,
    (unfinished) => (unfinished ? resume() : pause()),
    { immediate: true },
  )

  return { isActive }
}
