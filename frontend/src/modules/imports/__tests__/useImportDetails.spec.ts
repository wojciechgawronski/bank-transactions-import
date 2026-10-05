import { beforeEach, describe, expect, it, vi } from 'vitest'
import { effectScope, nextTick, ref } from 'vue'
import { flushPromises } from '@vue/test-utils'
import * as api from '@/api/imports'
import { useImportDetails } from '../composables/useImportDetails'
import { makeImport } from './fixtures'

vi.mock('@/api/imports', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/api/imports')>()
  return {
    ...actual,
    getImport: vi.fn<typeof actual.getImport>(),
    listImportLogs: vi.fn<typeof actual.listImportLogs>(),
  }
})

const log = { id: 1, transaction_id: 'TX-1', error_message: 'Record 1: bad IBAN', created_at: '' }
const meta = { current_page: 1, last_page: 2, per_page: 50, total: 60 }

function run(id: ReturnType<typeof ref<number>>) {
  return effectScope().run(() => useImportDetails(() => id.value ?? 0))!
}

describe('useImportDetails', () => {
  beforeEach(() => {
    vi.mocked(api.getImport).mockReset()
    vi.mocked(api.listImportLogs).mockReset()
  })

  it('loads the import and the first page of logs', async () => {
    vi.mocked(api.getImport).mockResolvedValue(makeImport({ id: 3 }))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [log], meta })

    const details = run(ref(3))
    await flushPromises()

    expect(api.listImportLogs).toHaveBeenCalledWith(3, 1)
    expect(details.details.value?.id).toBe(3)
    expect(details.logs.value).toEqual([log])
    expect(details.meta.value?.last_page).toBe(2)
    expect(details.loading.value).toBe(false)
  })

  it('loads another page of logs', async () => {
    vi.mocked(api.getImport).mockResolvedValue(makeImport({ id: 3 }))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [log], meta })
    const details = run(ref(3))
    await flushPromises()

    await details.load(2)

    expect(api.listImportLogs).toHaveBeenLastCalledWith(3, 2)
  })

  it('reports a readable error', async () => {
    vi.mocked(api.getImport).mockRejectedValue(new Error('network'))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [], meta })

    const details = run(ref(3))
    await flushPromises()

    expect(details.error.value).toBe('Nie udało się pobrać szczegółów importu.')
  })

  it('ignores a slow response for an import that is no longer shown', async () => {
    let resolveFirst!: (value: api.Import) => void
    vi.mocked(api.getImport)
      .mockImplementationOnce(() => new Promise((resolve) => (resolveFirst = resolve)))
      .mockResolvedValueOnce(makeImport({ id: 2, file_name: 'second.csv' }))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [], meta })
    const id = ref(1)
    const details = run(id)

    id.value = 2
    await nextTick()
    await flushPromises()
    resolveFirst(makeImport({ id: 1, file_name: 'first.csv' }))
    await flushPromises()

    expect(details.details.value?.file_name).toBe('second.csv')
  })
})
