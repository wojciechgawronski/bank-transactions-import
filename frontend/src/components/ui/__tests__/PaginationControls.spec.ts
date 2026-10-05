import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import PaginationControls from '../PaginationControls.vue'

function buttons(wrapper: ReturnType<typeof mount>) {
  const [previous, next] = wrapper.findAll('button')
  return { previous: previous!, next: next! }
}

describe('PaginationControls', () => {
  it('is hidden when everything fits on one page', () => {
    const wrapper = mount(PaginationControls, { props: { page: 1, lastPage: 1, total: 5 } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })

  it('shows the current page and total', () => {
    const wrapper = mount(PaginationControls, { props: { page: 2, lastPage: 3, total: 45 } })

    expect(wrapper.text()).toContain('Strona 2 z 3 · 45 pozycji')
  })

  it('emits the previous and next page', async () => {
    const wrapper = mount(PaginationControls, { props: { page: 2, lastPage: 3, total: 45 } })

    await buttons(wrapper).previous.trigger('click')
    await buttons(wrapper).next.trigger('click')

    expect(wrapper.emitted('change')).toEqual([[1], [3]])
  })

  it('disables buttons at the edges', () => {
    const first = buttons(mount(PaginationControls, { props: { page: 1, lastPage: 3, total: 45 } }))
    const last = buttons(mount(PaginationControls, { props: { page: 3, lastPage: 3, total: 45 } }))

    expect(first.previous.attributes('disabled')).toBeDefined()
    expect(first.next.attributes('disabled')).toBeUndefined()
    expect(last.next.attributes('disabled')).toBeDefined()
  })
})
