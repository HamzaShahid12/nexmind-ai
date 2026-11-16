// Field definitions for the single-shot generator tools. The backend validates the same fields again.
// Values here MUST match the allowed values in the backend GenerateRequest.
const tones = [['professional', 'Professional'], ['friendly', 'Friendly'], ['formal', 'Formal'], ['conversational', 'Conversational'], ['persuasive', 'Persuasive'], ['creative', 'Creative']]
const lengths = [['short', 'Short'], ['medium', 'Medium'], ['long', 'Long']]

export const toolForms = {
  'content-writer': {
    title: 'AI Content Writer', endpoint: 'content-writer', button: 'Generate content',
    description: 'Create articles, website copy, bios and marketing text.',
    fields: [
      { key: 'content_type', label: 'Content type', type: 'select', required: true, default: 'blog article', options: [['blog article', 'Blog article'], ['website content', 'Website content'], ['product-related article', 'Product-related article'], ['business description', 'Business description'], ['introduction', 'Introduction'], ['professional bio', 'Professional bio'], ['marketing copy', 'Marketing copy']] },
      { key: 'topic', label: 'Topic', type: 'text', required: true, max: 500, placeholder: 'What should the content be about?' },
      { key: 'keywords', label: 'Keywords', type: 'text', max: 300, placeholder: 'Comma-separated' },
      { key: 'audience', label: 'Target audience', type: 'text', max: 200 },
      { key: 'tone', label: 'Writing tone', type: 'select', required: true, tone: true, options: tones },
      { key: 'language', label: 'Language', type: 'text', max: 50, default: 'English', lang: true },
      { key: 'length', label: 'Desired length', type: 'select', required: true, default: 'medium', options: lengths },
      { key: 'instructions', label: 'Additional instructions', type: 'textarea', max: 1000, wide: true },
    ],
  },
  'product-description': {
    title: 'Product Description Generator', endpoint: 'product-description', button: 'Generate product copy',
    description: 'Titles, descriptions, features, benefits, meta tags and product tags.',
    fields: [
      { key: 'product_name', label: 'Product name', type: 'text', required: true, max: 200 },
      { key: 'category', label: 'Product category', type: 'text', max: 100 },
      { key: 'features', label: 'Product features', type: 'textarea', max: 2000, wide: true, placeholder: 'One per line' },
      { key: 'specifications', label: 'Product specifications', type: 'textarea', max: 2000, wide: true, placeholder: 'Size, material, colours… only facts you want used' },
      { key: 'audience', label: 'Target audience', type: 'text', max: 200 },
      { key: 'brand', label: 'Brand name', type: 'text', max: 100 },
      { key: 'keywords', label: 'Keywords', type: 'text', max: 300 },
      { key: 'tone', label: 'Writing tone', type: 'select', required: true, tone: true, options: tones },
    ],
  },
  'social-media': {
    title: 'Social Media Generator', endpoint: 'social-media', button: 'Generate posts',
    description: 'Captions, hashtags and calls to action. Posts are not published automatically; copy and post them yourself.',
    fields: [
      { key: 'platform', label: 'Platform', type: 'select', required: true, default: 'instagram', options: [['facebook', 'Facebook'], ['instagram', 'Instagram'], ['linkedin', 'LinkedIn'], ['x', 'X']] },
      { key: 'topic', label: 'Post topic', type: 'text', required: true, max: 500 },
      { key: 'business_name', label: 'Business or product name', type: 'text', max: 150 },
      { key: 'audience', label: 'Target audience', type: 'text', max: 200 },
      { key: 'tone', label: 'Tone', type: 'select', required: true, tone: true, options: tones },
      { key: 'keywords', label: 'Keywords', type: 'text', max: 300 },
      { key: 'variations', label: 'Number of variations', type: 'select', required: true, number: true, default: '3', options: [['1', '1'], ['2', '2'], ['3', '3'], ['4', '4'], ['5', '5']] },
    ],
  },
  'seo-assistant': {
    title: 'SEO Assistant', endpoint: 'seo', button: 'Get SEO suggestions',
    description: 'Meta tags, keywords and content improvements for a page.',
    disclaimer: 'These are AI-generated suggestions. They do not guarantee search rankings or traffic.',
    fields: [
      { key: 'page_title', label: 'Page title', type: 'text', required: true, max: 200 },
      { key: 'content', label: 'Website content', type: 'textarea', required: true, max: 20000, wide: true, rows: 8 },
      { key: 'main_keyword', label: 'Main keyword', type: 'text', required: true, max: 100 },
      { key: 'secondary_keywords', label: 'Secondary keywords', type: 'text', max: 300 },
      { key: 'audience', label: 'Target audience', type: 'text', max: 200 },
    ],
  },
  'email-generator': {
    title: 'Email Generator', endpoint: 'email', button: 'Generate email',
    description: 'Subject, body, greeting, closing and a follow-up version.',
    fields: [
      { key: 'purpose', label: 'Email purpose', type: 'text', required: true, max: 300 },
      { key: 'recipient_type', label: 'Recipient type', type: 'text', required: true, max: 100, placeholder: 'e.g. Customer, hiring manager' },
      { key: 'subject', label: 'Subject idea', type: 'text', max: 200 },
      { key: 'key_information', label: 'Key information', type: 'textarea', required: true, max: 3000, wide: true },
      { key: 'tone', label: 'Tone', type: 'select', required: true, tone: true, options: tones },
      { key: 'length', label: 'Desired length', type: 'select', required: true, default: 'medium', options: lengths },
    ],
  },
}

/** Build the initial form state, using the user's saved tone/language preferences. */
export function initialForm(config, user) {
  const form = {}
  for (const f of config.fields) {
    let value = f.default ?? ''
    if (f.tone && user?.tone) value = user.tone
    if (f.lang && user?.language) value = user.language
    // A select must always start on a valid option (e.g. tone when the user has no saved preference).
    if (f.type === 'select' && (value === '' || !f.options.some((o) => o[0] === value))) value = f.options[0][0]
    form[f.key] = value
  }
  return form
}

/** Client-side check for UX only. Returns {field: message}. */
export function validateForm(config, form) {
  const errors = {}
  for (const f of config.fields) {
    const v = String(form[f.key] ?? '').trim()
    if (f.required && !v) errors[f.key] = `${f.label} is required.`
    else if (f.max && v.length > f.max) errors[f.key] = `${f.label} must be at most ${f.max} characters.`
  }
  return errors
}

/** Build the request body: trims text, drops empties, converts numeric selects. */
export function buildPayload(config, form) {
  const body = {}
  for (const f of config.fields) {
    const v = String(form[f.key] ?? '').trim()
    if (v === '') continue
    body[f.key] = f.number ? Number(v) : v
  }
  return body
}
