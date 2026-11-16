import { formatDate } from '@/utils/format'

// Declarative configs for the ResourcePage-based admin modules. Endpoints are relative to /api/v1.
// The backend enforces authorization; nothing here grants access.

export const plansConfig = {
  title: 'Plans', singular: 'plan', endpoint: '/admin/plans', listPath: null, canCreate: true, canEdit: true, canDelete: true,
  note: 'Plans are configurable. Paid checkout is not implemented, so Pro and Business cannot be purchased yet.',
  columns: [
    { key: 'name', label: 'Name' }, { key: 'slug', label: 'Slug' }, { key: 'monthly_credits', label: 'Credits / month' },
    { key: 'is_active', label: 'Active', type: 'bool' }, { key: 'is_default', label: 'Default', type: 'bool' }, { key: 'users_count', label: 'Users' },
  ],
  fields: [
    { key: 'name', label: 'Name', type: 'text', required: true }, { key: 'slug', label: 'Slug', type: 'text', required: true, help: 'Letters, numbers, dashes.' },
    { key: 'description', label: 'Description', type: 'textarea', rows: 2 },
    { key: 'monthly_credits', label: 'Monthly credits', type: 'number', required: true, default: 0 },
    { key: 'price', label: 'Price (informational)', type: 'number', default: 0 },
    { key: 'sort_order', label: 'Sort order', type: 'number', default: 0 },
    { key: 'is_active', label: 'Plan is enabled', type: 'checkbox', default: true }, { key: 'is_default', label: 'Default plan for new users', type: 'checkbox' },
    { key: 'features', label: 'Features (one per line)', type: 'lines' },
    { key: 'tool_ids', label: 'Included tools (none selected = all tools)', type: 'multicheck', optionsFrom: { url: '/admin/ai-tools', value: 'id', label: 'name' }, valueFrom: (row) => (row.tools || []).map((t) => t.id) },
  ],
}

export const toolsConfig = {
  title: 'AI Tools', singular: 'tool', endpoint: '/admin/ai-tools', canEdit: true,
  note: 'Activate or deactivate tools, set credit costs and limits.',
  columns: [
    { key: 'name', label: 'Tool' }, { key: 'category', label: 'Category' }, { key: 'credit_cost', label: 'Credits' },
    { key: 'is_active', label: 'Active', type: 'bool' }, { key: 'daily_limit', label: 'Daily limit / user' }, { key: 'max_output_tokens', label: 'Max output tokens' },
  ],
  fields: [
    { key: 'name', label: 'Name', type: 'text', required: true }, { key: 'category', label: 'Category', type: 'text', required: true },
    { key: 'description', label: 'Description', type: 'textarea', rows: 2 },
    { key: 'credit_cost', label: 'Credit cost', type: 'number', required: true },
    { key: 'daily_limit', label: 'Daily limit per user (blank = unlimited)', type: 'number' },
    { key: 'max_output_tokens', label: 'Max output tokens', type: 'number', required: true },
    { key: 'temperature', label: 'Temperature (blank = model default)', type: 'number' },
    { key: 'is_active', label: 'Tool is active', type: 'checkbox' },
  ],
}

export const faqsConfig = {
  title: 'FAQs', singular: 'FAQ', endpoint: '/admin/faqs', canCreate: true, canEdit: true, canDelete: true,
  columns: [{ key: 'category', label: 'Category' }, { key: 'question', label: 'Question' }, { key: 'sort_order', label: 'Order' }, { key: 'is_published', label: 'Published', type: 'bool' }],
  fields: [
    { key: 'category', label: 'Category', type: 'text', required: true, default: 'General' }, { key: 'question', label: 'Question', type: 'text', required: true },
    { key: 'answer', label: 'Answer', type: 'textarea', required: true }, { key: 'sort_order', label: 'Sort order (lower first)', type: 'number', default: 0 },
    { key: 'is_published', label: 'Published', type: 'checkbox', default: true },
  ],
}

export const categoriesConfig = {
  title: 'Blog Categories', singular: 'category', endpoint: '/admin/categories', canCreate: true, canEdit: true, canDelete: true,
  columns: [{ key: 'name', label: 'Name' }, { key: 'slug', label: 'Slug' }, { key: 'posts_count', label: 'Posts' }],
  fields: [{ key: 'name', label: 'Name', type: 'text', required: true }],
}

export const blogsConfig = {
  title: 'Blog Posts', singular: 'article', endpoint: '/admin/blogs', searchable: true, canCreate: true, canEdit: true, canDelete: true, multipart: true, createLabel: 'New article',
  note: 'Content is shown as plain text paragraphs (separate paragraphs with a blank line).',
  filters: [{ key: 'status', label: 'All statuses', options: [['draft', 'Draft'], ['published', 'Published']] }],
  columns: [
    { key: 'title', label: 'Title' }, { key: 'category.name', label: 'Category' }, { key: 'status', label: 'Status', type: 'badge' },
    { key: 'published_at', label: 'Published', type: 'date' },
  ],
  fields: [
    { key: 'title', label: 'Title', type: 'text', required: true },
    { key: 'slug', label: 'URL slug (leave blank to keep/generate)', type: 'text' },
    { key: 'blog_category_id', label: 'Category', type: 'select', number: true, optionsFrom: { url: '/admin/categories', value: 'id', label: 'name' } },
    { key: 'status', label: 'Status', type: 'select', required: true, default: 'draft', options: [['draft', 'Draft'], ['published', 'Published']] },
    { key: 'excerpt', label: 'Excerpt', type: 'textarea', rows: 2 },
    { key: 'content', label: 'Content', type: 'textarea', required: true, rows: 10 },
    { key: 'meta_title', label: 'SEO meta title', type: 'text' }, { key: 'meta_description', label: 'SEO meta description', type: 'textarea', rows: 2 },
    { key: 'image', label: 'Featured image (JPG, PNG or WebP, max 3 MB)', type: 'file' },
  ],
  rowActions: [
    { label: 'Publish', show: (r) => r.status !== 'published', run: (r, _v, api) => api.post(`/admin/blogs/${r.id}/publish`), success: 'Published.', confirm: 'Make this article public?' },
    { label: 'Unpublish', show: (r) => r.status === 'published', run: (r, _v, api) => api.post(`/admin/blogs/${r.id}/unpublish`), success: 'Unpublished.', confirm: 'Hide this article from the public blog?' },
  ],
}

export const generationsConfig = {
  title: 'Generations', endpoint: '/admin/generations', searchable: true,
  columns: [
    { key: 'title', label: 'Title' }, { key: 'tool.name', label: 'Tool' }, { key: 'user.email', label: 'User' },
    { key: 'credits_used', label: 'Credits' }, { key: 'created_at', label: 'Date', type: 'datetime' },
  ],
}

export const transactionsConfig = {
  title: 'Credit Transactions', endpoint: '/admin/transactions',
  filters: [{ key: 'type', label: 'All types', options: [['signup', 'Signup'], ['renewal', 'Renewal'], ['usage', 'Usage'], ['admin_add', 'Admin add'], ['admin_deduct', 'Admin deduct'], ['expiry', 'Expiry']] }],
  columns: [
    { key: 'created_at', label: 'Date', type: 'datetime' }, { key: 'user.email', label: 'User' }, { key: 'type', label: 'Type' },
    { key: 'amount', label: 'Amount' }, { key: 'balance_after', label: 'Balance after' }, { key: 'description', label: 'Description' }, { key: 'admin.name', label: 'By admin' },
  ],
}

const adjustFields = [
  { key: 'amount', label: 'Amount (positive adds, negative deducts)', type: 'number' },
  { key: 'reason', label: 'Reason (kept in the audit trail)', type: 'text' },
]

export const creditsConfig = {
  title: 'Credits', endpoint: '/admin/credits', searchable: true, idKey: 'id',
  note: 'Balances can never go below zero. Every adjustment is recorded in the transaction history and audit log.',
  columns: [{ key: 'name', label: 'User' }, { key: 'email', label: 'Email' }, { key: 'plan.name', label: 'Plan' }, { key: 'credits_balance', label: 'Balance' }, { key: 'credits_reset_at', label: 'Next renewal', type: 'date' }],
  rowActions: [{ label: 'Adjust credits', fields: adjustFields, prefill: () => ({ amount: '', reason: '' }), submitLabel: 'Apply', success: 'Credits adjusted.', run: (r, v, api) => api.post(`/admin/users/${r.id}/credits`, v) }],
}

export const usersConfig = (isSuper) => ({
  title: 'Users', singular: 'user', endpoint: '/admin/users', searchable: true, canEdit: true, canDelete: true,
  filters: [
    { key: 'status', label: 'Any status', options: [['active', 'Active'], ['suspended', 'Suspended']] },
    { key: 'role', label: 'Any role', options: [['user', 'User'], ['admin', 'Admin'], ['super_admin', 'Super admin']] },
  ],
  columns: [
    { key: 'name', label: 'Name' }, { key: 'email', label: 'Email' }, { key: 'plan.name', label: 'Plan' },
    { key: 'roles', label: 'Roles', render: (r) => (r.roles || []).join(', ') }, { key: 'status', label: 'Status', type: 'badge' },
    { key: 'credits_balance', label: 'Credits' }, { key: 'created_at', label: 'Joined', type: 'date' },
  ],
  fields: [
    { key: 'name', label: 'Name', type: 'text', required: true }, { key: 'email', label: 'Email', type: 'text', required: true },
    { key: 'plan_id', label: 'Plan', type: 'select', number: true, optionsFrom: { url: '/admin/plans', value: 'id', label: 'name' }, valueKey: 'plan.id' },
  ],
  rowActions: [
    { label: 'Suspend', danger: true, show: (r) => r.status === 'active', confirm: 'The user will be signed out and blocked until reactivated.', success: 'User suspended.', run: (r, _v, api) => api.post(`/admin/users/${r.id}/suspend`) },
    { label: 'Activate', show: (r) => r.status !== 'active', confirm: 'Allow this user to sign in again?', success: 'User activated.', run: (r, _v, api) => api.post(`/admin/users/${r.id}/activate`) },
    { label: 'Credits', fields: adjustFields, prefill: () => ({ amount: '', reason: '' }), submitLabel: 'Apply', success: 'Credits adjusted.', run: (r, v, api) => api.post(`/admin/users/${r.id}/credits`, v) },
    ...(isSuper ? [{ label: 'Roles', fields: [{ key: 'roles', label: 'Roles', type: 'multicheck', options: [['user', 'User'], ['admin', 'Admin'], ['super_admin', 'Super admin']] }], prefill: (r) => ({ roles: r.roles || [] }), submitLabel: 'Save roles', success: 'Roles updated.', run: (r, v, api) => api.put(`/admin/users/${r.id}/roles`, v) }] : []),
  ],
})

export const contactConfig = {
  title: 'Contact Messages', singular: 'message', endpoint: '/admin/contact-messages', canDelete: true,
  filters: [{ key: 'unread', label: 'All messages', options: [['1', 'Unread only']] }],
  columns: [
    { key: 'is_read', label: 'Read', type: 'bool' }, { key: 'name', label: 'From' }, { key: 'email', label: 'Email' }, { key: 'subject', label: 'Subject' },
    { key: 'message', label: 'Message' }, { key: 'created_at', label: 'Received', type: 'datetime' }, { key: 'replied_at', label: 'Replied', render: (r) => (r.replied_at ? formatDate(r.replied_at) : '—') },
  ],
  rowActions: [
    { label: 'Mark read', show: (r) => !r.is_read, run: (r, _v, api) => api.patch(`/admin/contact-messages/${r.id}`, { is_read: true }), confirm: 'Mark this message as read?', success: 'Marked as read.' },
    { label: 'Reply', fields: [{ key: 'body', label: 'Your reply (sent by email to the sender)', type: 'textarea' }], prefill: () => ({ body: '' }), submitLabel: 'Send reply', success: 'Reply sent.', run: (r, v, api) => api.post(`/admin/contact-messages/${r.id}/reply`, v) },
  ],
}
