import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { effectScope, nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { POLLING_INTERVAL_MS, useImportPolling } from '../composables/useImportPolling'
import { useImportsStore } from '../stores/imports'
import { makeImport } from './fixtures'

describe('useImportPolling', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  function setup() {
    const store = useImportsStore()
    const refresh = vi.spyOn(store, 'refresh').mockResolvedValue()
    const scope = effectScope()
    const polling = scope.run(() => useImportPolling())!
    return { store, refresh, polling, scope }
  }

  it('does not poll when every import is finished', async () => {
    const { store, refresh, polling } = setup()
    store.imports = [makeImport({ status: 'success' })]
    await nextTick()

    vi.advanceTimersByTime(POLLING_INTERVAL_MS * 3)

    expect(polling.isActive.value).toBe(false)
    expect(refresh).not.toHaveBeenCalled()
  })

  it('polls while an import is pending and stops once it finishes', async () => {
    const { store, refresh, polling } = setup()
    store.imports = [makeImport({ status: 'pending' })]
    await nextTick()

    await vi.advanceTimersByTimeAsync(POLLING_INTERVAL_MS * 2)
    expect(refresh).toHaveBeenCalledTimes(2)

    store.imports = [makeImport({ status: 'success' })]
    await nextTick()
    await vi.advanceTimersByTimeAsync(POLLING_INTERVAL_MS * 2)

    expect(polling.isActive.value).toBe(false)
    expect(refresh).toHaveBeenCalledTimes(2)
  })

  it('stops polling when the component is unmounted', async () => {
    const { store, refresh, scope } = setup()
    store.imports = [makeImport({ status: 'processing' })]
    await nextTick()

    scope.stop()
    await vi.advanceTimersByTimeAsync(POLLING_INTERVAL_MS * 2)

    expect(refresh).not.toHaveBeenCalled()
  })
})
