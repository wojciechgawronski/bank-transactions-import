import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiErrorMessage } from '@/api/http'
import {
  listImports,
  uploadImport,
  type Import,
  type Paginated,
  type PaginationMeta,
} from '@/api/imports'

export const useImportsStore = defineStore('imports', () => {
  const imports = ref<Import[]>([])
  const meta = ref<PaginationMeta | null>(null)
  const page = ref(1)
  const loading = ref(false)
  const uploading = ref(false)
  const error = ref<string | null>(null)

  function apply(result: Paginated<Import>): void {
    imports.value = result.data
    meta.value = result.meta
    page.value = result.meta.current_page
  }

  async function fetchImports(targetPage = page.value): Promise<void> {
    loading.value = true
    error.value = null
    try {
      apply(await listImports(targetPage))
    } catch (e) {
      error.value = apiErrorMessage(e, 'Nie udało się pobrać listy importów.')
    } finally {
      loading.value = false
    }
  }

  /** Uploads a file and shows the first page, where the new import appears. Throws on API errors. */
  async function upload(file: File): Promise<Import> {
    uploading.value = true
    try {
      const created = await uploadImport(file)
      await fetchImports(1)
      return created
    } finally {
      uploading.value = false
    }
  }

  return { imports, meta, page, loading, uploading, error, fetchImports, upload }
})
