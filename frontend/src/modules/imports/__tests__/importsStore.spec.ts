import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import * as api from '@/api/imports'
import { useImportsStore } from '../stores/imports'
import { makeImport, page } from './fixtures'

vi.mock('@/api/imports', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/api/imports')>()
  return {
    ...actual,
    listImports: vi.fn<typeof actual.listImports>(),
    uploadImport: vi.fn<typeof actual.uploadImport>(),
  }
})

describe('imports store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(api.listImports).mockReset()
    vi.mocked(api.uploadImport).mockReset()
  })

  it('loads a page of imports', async () => {
    vi.mocked(api.listImports).mockResolvedValue(page([makeImport()], 2, 3))
    const store = useImportsStore()

    await store.fetchImports(2)

    expect(api.listImports).toHaveBeenCalledWith(2)
    expect(store.imports).toHaveLength(1)
    expect(store.page).toBe(2)
    expect(store.meta?.last_page).toBe(3)
    expect(store.loading).toBe(false)
  })

  it('exposes a readable error when loading fails', async () => {
    vi.mocked(api.listImports).mockRejectedValue(new Error('network'))
    const store = useImportsStore()

    await store.fetchImports()

    expect(store.error).toBe('Nie udało się pobrać listy importów.')
  })

  it('knows when an import is still being processed', async () => {
    const store = useImportsStore()
    vi.mocked(api.listImports).mockResolvedValue(
      page([makeImport(), makeImport({ id: 2, status: 'processing' })]),
    )

    await store.fetchImports()
    expect(store.hasUnfinished).toBe(true)

    vi.mocked(api.listImports).mockResolvedValue(
      page([makeImport(), makeImport({ id: 2, status: 'success' })]),
    )
    await store.refresh()
    expect(store.hasUnfinished).toBe(false)
  })

  it('keeps the list when a background refresh fails', async () => {
    vi.mocked(api.listImports).mockResolvedValueOnce(page([makeImport()]))
    const store = useImportsStore()
    await store.fetchImports()

    vi.mocked(api.listImports).mockRejectedValueOnce(new Error('network'))
    await store.refresh()

    expect(store.imports).toHaveLength(1)
  })

  it('uploads a file and shows the first page', async () => {
    const created = makeImport({ id: 5, status: 'pending' })
    vi.mocked(api.uploadImport).mockResolvedValue(created)
    vi.mocked(api.listImports).mockResolvedValue(page([created]))
    const store = useImportsStore()
    store.page = 3

    const result = await store.upload(new File(['x'], 'a.csv'))

    expect(result).toEqual(created)
    expect(api.listImports).toHaveBeenCalledWith(1)
    expect(store.imports[0]?.id).toBe(5)
    expect(store.uploading).toBe(false)
  })

  it('rethrows upload errors and resets the uploading flag', async () => {
    vi.mocked(api.uploadImport).mockRejectedValue(new Error('422'))
    const store = useImportsStore()

    await expect(store.upload(new File(['x'], 'a.csv'))).rejects.toThrow('422')
    expect(store.uploading).toBe(false)
  })
})
