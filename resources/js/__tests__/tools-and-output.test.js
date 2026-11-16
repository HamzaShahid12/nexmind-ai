import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { toolForms, initialForm, validateForm, buildPayload } from '@/utils/toolForms'
import { toPlainText, get, labelize } from '@/utils/format'
import OutputView from '@/components/tools/OutputView.vue'

describe('tool form helpers', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('defines the five generator tools with required fields', () => {
    expect(Object.keys(toolForms).sort()).toEqual(['content-writer', 'email-generator', 'product-description', 'seo-assistant', 'social-media'])
  })

  it('uses the user preferences for default tone and language', () => {
    const form = initialForm(toolForms['content-writer'], { tone: 'friendly', language: 'French' })
    expect(form.tone).toBe('friendly')
    expect(form.language).toBe('French')
  })

  it('reports missing required fields and over-long values', () => {
    const cfg = toolForms['email-generator']
    const errors = validateForm(cfg, { ...initialForm(cfg, null), purpose: '', key_information: 'x'.repeat(3001) })
    expect(errors.purpose).toBeTruthy()
    expect(errors.recipient_type).toBeTruthy()
    expect(errors.key_information).toMatch(/at most 3000/)
  })

  it('accepts a complete form', () => {
    const cfg = toolForms['email-generator']
    const form = { ...initialForm(cfg, null), purpose: 'Intro', recipient_type: 'Client', key_information: 'We do X' }
    expect(validateForm(cfg, form)).toEqual({})
  })

  it('never sends credit or cost fields and converts numeric selects', () => {
    const cfg = toolForms['social-media']
    const body = buildPayload(cfg, { ...initialForm(cfg, null), topic: 'Launch', variations: '4', credit_cost: 0 })
    expect(body.variations).toBe(4)
    expect(body).not.toHaveProperty('credit_cost')
    expect(Object.keys(body)).not.toContain('keywords')   // empty values are dropped
  })

  it('the SEO tool carries a no-guaranteed-rankings disclaimer', () => {
    expect(toolForms['seo-assistant'].disclaimer).toMatch(/do not guarantee/i)
  })
})

describe('format helpers', () => {
  it('flattens structured output to readable text', () => {
    const text = toPlainText({ seo_title: 'Title', tags: ['a', 'b'], search_intent: { type: 'informational' } })
    expect(text).toContain('Seo title: Title')
    expect(text).toContain('- a')
    expect(text).toContain('Type: informational')
  })
  it('reads dotted paths safely', () => {
    expect(get({ tool: { name: 'X' } }, 'tool.name')).toBe('X')
    expect(get({}, 'tool.name')).toBeUndefined()
  })
  it('labelizes keys', () => expect(labelize('meta_description')).toBe('Meta description'))
})

describe('OutputView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('navigator', { clipboard: { writeText: vi.fn().mockResolvedValue() } })
  })

  it('renders each section of structured output with its own copy button', () => {
    const w = mount(OutputView, { props: { data: { short_description: 'Short', key_features: ['One', 'Two'] } } })
    expect(w.text()).toContain('Short description')
    expect(w.findAll('li').map((l) => l.text())).toEqual(['One', 'Two'])
    expect(w.findAll('button')).toHaveLength(2)
  })

  it('renders model output as text, never as HTML', () => {
    const w = mount(OutputView, { props: { data: { body: '<img src=x onerror=alert(1)><script>alert(1)</script>' } } })
    expect(w.find('img').exists()).toBe(false)
    expect(w.find('script').exists()).toBe(false)
    expect(w.text()).toContain('<img src=x')
  })

  it('renders plain text output', () => {
    const w = mount(OutputView, { props: { data: 'Hello world' } })
    expect(w.text()).toBe('Hello world')
  })

  it('renders arrays of objects (social media variations)', () => {
    const w = mount(OutputView, { props: { data: { variations: [{ caption: 'First' }, { caption: 'Second' }] } } })
    expect(w.text()).toContain('First')
    expect(w.text()).toContain('Second')
  })
})
