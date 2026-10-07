import type { Import, Paginated } from '@/api/imports'

export function makeImport(overrides: Partial<Import> = {}): Import {
  return {
    id: 1,
    file_name: 'transactions.csv',
    total_records: 10,
    successful_records: 8,
    failed_records: 2,
    status: 'partial',
    queue_connection: 'database',
    created_at: '2025-10-14T08:30:00.000000Z',
    updated_at: '2025-10-14T08:30:05.000000Z',
    ...overrides,
  }
}

export function page(data: Import[], current = 1, last = 1): Paginated<Import> {
  return {
    data,
    meta: { current_page: current, last_page: last, per_page: 20, total: data.length },
  }
}
