import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

describe('ConfirmDialog', () => {
  it('renders nothing when closed', () => {
    expect(mount(ConfirmDialog, { props: { open: false } }).html()).toBe('<!--v-if-->')
  })
  it('emits confirm and cancel', async () => {
    const w = mount(ConfirmDialog, { props: { open: true, title: 'Delete?' } })
    expect(w.text()).toContain('Delete?')
    const buttons = w.findAll('button')
    await buttons[0].trigger('click')
    await buttons[1].trigger('click')
    expect(w.emitted('cancel')).toBeTruthy()
    expect(w.emitted('confirm')).toBeTruthy()
  })
})

describe('EmptyState', () => {
  it('shows title and message', () => {
    const w = mount(EmptyState, { props: { title: 'Nothing here', message: 'Add something' } })
    expect(w.text()).toContain('Nothing here')
    expect(w.text()).toContain('Add something')
  })
})
