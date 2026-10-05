import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { MAX_FILE_SIZE_BYTES } from '@/api/imports'
import FileDropzone from '../components/FileDropzone.vue'
import ImportsTable from '../components/ImportsTable.vue'
import StatusBadge from '../components/StatusBadge.vue'
import { makeImport } from './fixtures'

function selectFile(wrapper: ReturnType<typeof mount>, file: File): Promise<void> {
  const input = wrapper.get<HTMLInputElement>('[data-testid="file-input"]')
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  return input.trigger('change')
}

describe('StatusBadge', () => {
  it.each([
    ['pending', 'Oczekuje'],
    ['processing', 'Przetwarzanie'],
    ['success', 'Sukces'],
    ['partial', 'Częściowy'],
    ['failed', 'Błąd'],
  ] as const)('shows %s as %s', (status, label) => {
    expect(mount(StatusBadge, { props: { status } }).text()).toBe(label)
  })
})

describe('FileDropzone', () => {
  it('emits a supported file', async () => {
    const wrapper = mount(FileDropzone)
    const file = new File(['a'], 'export.JSON')

    await selectFile(wrapper, file)

    expect(wrapper.emitted('select')).toEqual([[file]])
    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('rejects an unsupported format', async () => {
    const wrapper = mount(FileDropzone)

    await selectFile(wrapper, new File(['a'], 'report.pdf'))

    expect(wrapper.emitted('select')).toBeUndefined()
    expect(wrapper.get('[role="alert"]').text()).toContain('CSV, JSON i XML')
  })

  it('rejects a file over the size limit', async () => {
    const wrapper = mount(FileDropzone)
    const file = new File(['a'], 'big.csv')
    Object.defineProperty(file, 'size', { value: MAX_FILE_SIZE_BYTES + 1 })

    await selectFile(wrapper, file)

    expect(wrapper.emitted('select')).toBeUndefined()
    expect(wrapper.get('[role="alert"]').text()).toContain('10 MB')
  })

  it('accepts a dropped file', async () => {
    const wrapper = mount(FileDropzone)
    const file = new File(['a'], 'data.xml')

    await wrapper.get('label').trigger('drop', { dataTransfer: { files: [file] } })

    expect(wrapper.emitted('select')).toEqual([[file]])
  })

  it('ignores files while busy', async () => {
    const wrapper = mount(FileDropzone, { props: { busy: true } })

    await selectFile(wrapper, new File(['a'], 'data.csv'))

    expect(wrapper.emitted('select')).toBeUndefined()
  })
})

describe('ImportsTable', () => {
  it('shows file name, record counts and status for each import', () => {
    const wrapper = mount(ImportsTable, {
      props: {
        imports: [makeImport(), makeImport({ id: 2, file_name: 'bank.xml', status: 'success' })],
      },
    })

    const rows = wrapper.findAll('[data-testid="import-row"]')
    expect(rows).toHaveLength(2)
    expect(rows[0]?.text()).toContain('transactions.csv')
    expect(rows[0]?.findAll('td').map((cell) => cell.text())).toEqual(
      expect.arrayContaining(['10', '8', '2', 'Częściowy']),
    )
    expect(rows[1]?.text()).toContain('Sukces')
  })

  it('shows an empty state', () => {
    expect(mount(ImportsTable, { props: { imports: [] } }).text()).toContain('Brak importów')
  })
})
