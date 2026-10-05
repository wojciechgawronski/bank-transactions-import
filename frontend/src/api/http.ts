import axios, { isAxiosError } from 'axios'

/**
 * Single axios instance for the backend. In dev Vite proxies /api to Laravel,
 * in production the same domain serves both, so a relative base URL is enough.
 */
export const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

interface LaravelError {
  message?: string
  errors?: Record<string, string[]>
}

/**
 * Human-readable message from an API error: the first validation error when
 * there is one (422), otherwise Laravel's message or a generic fallback.
 */
export function apiErrorMessage(
  error: unknown,
  fallback = 'Coś poszło nie tak. Spróbuj ponownie.',
): string {
  if (!isAxiosError<LaravelError>(error)) {
    return fallback
  }

  const data = error.response?.data
  const firstValidationError = Object.values(data?.errors ?? {})[0]?.[0]

  return firstValidationError ?? data?.message ?? fallback
}
