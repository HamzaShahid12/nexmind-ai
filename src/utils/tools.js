import { MessageSquare, PenLine, ShoppingBag, Share2, SearchCheck, Mail, FileText } from 'lucide-vue-next'

// Static catalog used for marketing pages. The dashboard reads live tool
// settings (active flag, credit cost) from the backend.
export const tools = [
  { slug: 'ai-chat', name: 'AI Chat Assistant', category: 'Productivity', icon: MessageSquare, path: '/dashboard/ai-chat', description: 'Have a conversation with an AI assistant and pick up any chat where you left off.' },
  { slug: 'content-writer', name: 'AI Content Writer', category: 'Writing', icon: PenLine, path: '/dashboard/content-writer', description: 'Blog articles, website copy, bios and marketing text in your tone and language.' },
  { slug: 'product-description', name: 'Product Description Generator', category: 'Ecommerce', icon: ShoppingBag, path: '/dashboard/product-description', description: 'Titles, descriptions, features, meta tags and product tags for your store.' },
  { slug: 'social-media', name: 'Social Media Generator', category: 'Marketing', icon: Share2, path: '/dashboard/social-media', description: 'Captions, hashtags and calls to action for Facebook, Instagram, LinkedIn and X.' },
  { slug: 'seo-assistant', name: 'SEO Assistant', category: 'SEO', icon: SearchCheck, path: '/dashboard/seo-assistant', description: 'Meta tags, keyword ideas and content suggestions. Suggestions only, no ranking guarantees.' },
  { slug: 'email-generator', name: 'Email Generator', category: 'Writing', icon: Mail, path: '/dashboard/email-generator', description: 'Subject lines, email bodies and follow-ups for any purpose.' },
  { slug: 'document-summarizer', name: 'Document Summarizer', category: 'Productivity', icon: FileText, path: '/dashboard/document-summarizer', description: 'Upload a PDF, TXT or DOCX, get key points and ask questions about it.' },
]

export const categories = ['Writing', 'Ecommerce', 'Marketing', 'SEO', 'Productivity']
