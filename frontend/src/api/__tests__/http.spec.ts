import { describe, expect, it } from 'vitest'
import { AxiosError, AxiosHeaders, type AxiosResponse } from 'axios'
import { apiErrorMessage } from '../http'

function axiosError(status: number, data: unknown): AxiosError {
  const response = {
    status,
    data,
    headers: {},
    config: { headers: new AxiosHeaders() },
    statusText: '',
  }
  return new AxiosError(
    'Request failed',
    'ERR_BAD_RESPONSE',
    undefined,
    undefined,
    response as AxiosResponse,
  )
}

describe('apiErrorMessage', () => {
  it('returns the first validation error', () => {
    const error = axiosError(422, {
      message: 'The given data was invalid.',
      errors: {
        file: ['The file field must have one of the following extensions: csv, json, xml.'],
      },
    })

    expect(apiErrorMessage(error)).toBe(
      'The file field must have one of the following extensions: csv, json, xml.',
    )
  })

  it('falls back to the Laravel message', () => {
    expect(apiErrorMessage(axiosError(404, { message: 'Not found.' }))).toBe('Not found.')
  })

  it('uses the fallback for non-API errors', () => {
    expect(apiErrorMessage(new Error('boom'), 'Fallback')).toBe('Fallback')
  })
})
