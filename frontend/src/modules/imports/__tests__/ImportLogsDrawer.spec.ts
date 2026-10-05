import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import * as api from '@/api/imports'
import ImportLogsDrawer from '../components/ImportLogsDrawer.vue'
import { makeImport } from './fixtures'
import { createTestRouter } from './router'

vi.mock('@/api/imports', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/api/imports')>()
  return {
    ...actual,
    getImport: vi.fn<typeof actual.getImport>(),
    listImportLogs: vi.fn<typeof actual.listImportLogs>(),
  }
})

const meta = { current_page: 1, last_page: 1, per_page: 50, total: 2 }

async function mountDrawer() {
  const router = createTestRouter()
  await router.push('/imports/5')
  const wrapper = mount(ImportLogsDrawer, {
    props: { id: '5' },
    attachTo: document.body,
    global: { plugins: [router] },
  })
  await flushPromises()

  return { wrapper, router }
}

describe('ImportLogsDrawer', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
  })

  it('shows import stats and the transaction id with error message for each log', async () => {
    vi.mocked(api.getImport).mockResolvedValue(
      makeImport({
        id: 5,
        file_name: 'bank.csv',
        total_records: 3,
        successful_records: 1,
        failed_records: 2,
      }),
    )
    vi.mocked(api.listImportLogs).mockResolvedValue({
      data: [
        {
          id: 1,
          transaction_id: 'TX-1',
          error_message: 'Record 1: invalid IBAN checksum',
          created_at: '',
        },
        {
          id: 2,
          transaction_id: null,
          error_message: 'Record 2: transaction id is required',
          created_at: '',
        },
      ],
      meta,
    })

    const { wrapper } = await mountDrawer()

    expect(document.body.textContent).toContain('bank.csv')
    const rows = Array.from(document.querySelectorAll('[data-testid="log-row"]')).map((row) =>
      Array.from(row.querySelectorAll('td')).map((cell) => cell.textContent?.trim()),
    )
    expect(rows).toEqual([
      ['TX-1', 'Record 1: invalid IBAN checksum'],
      ['—', 'Record 2: transaction id is required'],
    ])
    wrapper.unmount()
  })

  it('confirms when every record was imported', async () => {
    vi.mocked(api.getImport).mockResolvedValue(
      makeImport({ id: 5, status: 'success', failed_records: 0 }),
    )
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [], meta: { ...meta, total: 0 } })

    const { wrapper } = await mountDrawer()

    expect(document.body.textContent).toContain(
      'Wszystkie rekordy zostały zaimportowane poprawnie.',
    )
    wrapper.unmount()
  })

  it('tells the user when the import is still being processed', async () => {
    vi.mocked(api.getImport).mockResolvedValue(makeImport({ id: 5, status: 'processing' }))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [], meta: { ...meta, total: 0 } })

    const { wrapper } = await mountDrawer()

    expect(document.body.textContent).toContain('Import jest w trakcie przetwarzania.')
    wrapper.unmount()
  })

  it('goes back to the list when closed', async () => {
    vi.mocked(api.getImport).mockResolvedValue(makeImport({ id: 5 }))
    vi.mocked(api.listImportLogs).mockResolvedValue({ data: [], meta })

    const { wrapper, router } = await mountDrawer()
    document.querySelector<HTMLButtonElement>('button[aria-label="Zamknij"]')?.click()
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('imports')
    wrapper.unmount()
  })
})
