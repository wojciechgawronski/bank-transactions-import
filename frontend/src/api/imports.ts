import { http } from './http'

export type ImportStatus = 'pending' | 'processing' | 'success' | 'partial' | 'failed'

export interface Import {
  id: number
  file_name: string
  total_records: number
  successful_records: number
  failed_records: number
  status: ImportStatus
  /** Laravel queue connection the import was processed on, e.g. database, rabbitmq. */
  queue_connection: string | null
  created_at: string
  updated_at: string
}

export interface ImportLog {
  id: number
  transaction_id: string | null
  error_message: string
  created_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

export const ACCEPTED_EXTENSIONS = ['csv', 'json', 'xml'] as const

/** Mirrors StoreImportRequest::MAX_SIZE_KB on the backend. */
export const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024

export async function listImports(page = 1, perPage = 20): Promise<Paginated<Import>> {
  const { data } = await http.get<Paginated<Import>>('/imports', {
    params: { page, per_page: perPage },
  })

  return data
}

export async function getImport(id: number): Promise<Import> {
  const { data } = await http.get<{ data: Import }>(`/imports/${id}`)

  return data.data
}

export async function listImportLogs(
  id: number,
  page = 1,
  perPage = 50,
): Promise<Paginated<ImportLog>> {
  const { data } = await http.get<Paginated<ImportLog>>(`/imports/${id}/logs`, {
    params: { page, per_page: perPage },
  })

  return data
}

export async function uploadImport(file: File): Promise<Import> {
  const body = new FormData()
  body.append('file', file)

  const { data } = await http.post<{ data: Import }>('/imports', body)

  return data.data
}

export function isFinished(status: ImportStatus): boolean {
  return status !== 'pending' && status !== 'processing'
}
