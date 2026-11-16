<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Plus, Send, Copy, Pencil, Trash2, Check, X, MessageSquare } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useCopy } from '@/composables/useCopy'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const auth = useAuthStore()
const toast = useToastStore()
const { copy } = useCopy()

const conversations = ref([])
const activeId = ref(null)
const messages = ref([])
const input = ref('')
const sending = ref(false)
const loadingList = ref(true)
const renamingId = ref(null)
const renameValue = ref('')
const deleteTarget = ref(null)
const scroller = ref(null)

const scrollDown = () => nextTick(() => scroller.value && (scroller.value.scrollTop = scroller.value.scrollHeight))

async function loadList() {
  try {
    const { data } = await api.get('/conversations')
    conversations.value = data.data
  } catch (e) { toast.error(parseError(e).message) } finally { loadingList.value = false }
}

async function open(id) {
  activeId.value = id
  try {
    const { data } = await api.get(`/conversations/${id}`)
    messages.value = data.data.messages
    scrollDown()
  } catch (e) { toast.error(parseError(e).message) }
}

function newChat() {
  activeId.value = null
  messages.value = []
  input.value = ''
}

async function send() {
  const text = input.value.trim()
  if (!text || sending.value) return
  messages.value.push({ id: `tmp-${Date.now()}`, role: 'user', content: text })
  input.value = ''
  sending.value = true
  scrollDown()
  try {
    const { data } = await api.post('/ai/chat', { message: text, conversation_id: activeId.value })
    activeId.value = data.conversation.id
    messages.value.push({ id: `a-${Date.now()}`, role: 'assistant', content: data.message.content })
    if (auth.user) auth.user.credits_balance = data.credits_balance
    await loadList()
  } catch (e) {
    messages.value.pop()                // the message was not processed or charged
    input.value = text
    toast.error(parseError(e).message)
  } finally {
    sending.value = false
    scrollDown()
  }
}

function startRename(c) { renamingId.value = c.id; renameValue.value = c.title }
async function saveRename(c) {
  const title = renameValue.value.trim()
  if (!title) return (renamingId.value = null)
  try {
    await api.patch(`/conversations/${c.id}`, { title })
    c.title = title
  } catch (e) { toast.error(parseError(e).message) }
  renamingId.value = null
}

async function confirmRemove() {
  const c = deleteTarget.value
  try {
    await api.delete(`/conversations/${c.id}`)
    conversations.value = conversations.value.filter((x) => x.id !== c.id)
    if (activeId.value === c.id) newChat()
    toast.success('Conversation deleted.')
  } catch (e) { toast.error(parseError(e).message) }
  deleteTarget.value = null
}

onMounted(loadList)
</script>

<template>
  <div class="mx-auto grid h-[calc(100vh-8.5rem)] max-w-6xl gap-4 lg:grid-cols-[260px_1fr]">
    <aside class="card flex min-h-0 flex-col p-3">
      <button class="btn btn-primary w-full" @click="newChat"><Plus class="h-4 w-4" />New conversation</button>
      <div class="mt-3 min-h-0 flex-1 space-y-1 overflow-y-auto">
        <p v-if="!loadingList && !conversations.length" class="p-3 text-center text-xs">No conversations yet.</p>
        <div v-for="c in conversations" :key="c.id" class="group rounded-lg" :class="c.id === activeId ? 'bg-primary/10' : 'hover:bg-slate-100'">
          <div v-if="renamingId === c.id" class="flex items-center gap-1 p-1.5">
            <input v-model="renameValue" class="input !py-1" aria-label="Conversation title" @keyup.enter="saveRename(c)" @keyup.esc="renamingId = null" />
            <button class="p-1 text-emerald-600" aria-label="Save title" @click="saveRename(c)"><Check class="h-4 w-4" /></button>
            <button class="p-1" aria-label="Cancel" @click="renamingId = null"><X class="h-4 w-4" /></button>
          </div>
          <div v-else class="flex items-center gap-1 px-2 py-2">
            <button class="flex min-w-0 flex-1 items-center gap-2 text-left text-sm" @click="open(c.id)"><MessageSquare class="h-4 w-4 shrink-0" /><span class="truncate">{{ c.title }}</span></button>
            <button class="p-1 opacity-60 hover:opacity-100" aria-label="Rename" @click="startRename(c)"><Pencil class="h-3.5 w-3.5" /></button>
            <button class="p-1 opacity-60 hover:text-red-600 hover:opacity-100" aria-label="Delete" @click="deleteTarget = c"><Trash2 class="h-3.5 w-3.5" /></button>
          </div>
        </div>
      </div>
    </aside>

    <section class="card flex min-h-0 flex-col">
      <div ref="scroller" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4" aria-live="polite">
        <p v-if="!messages.length" class="grid h-full place-items-center text-center text-sm">Ask anything to start a conversation. Each reply costs credits.</p>
        <div v-for="m in messages" :key="m.id" class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
          <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm" :class="m.role === 'user' ? 'bg-primary text-white' : 'bg-slate-100 text-ink'">
            <p class="whitespace-pre-wrap">{{ m.content }}</p>
            <button v-if="m.role === 'assistant'" class="mt-1.5 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-primary" @click="copy(m.content, 'Response copied')"><Copy class="h-3 w-3" />Copy</button>
          </div>
        </div>
        <div v-if="sending" class="flex justify-start" aria-label="AI is typing">
          <div class="flex gap-1 rounded-2xl bg-slate-100 px-4 py-3"><span v-for="n in 3" :key="n" class="h-2 w-2 animate-bounce rounded-full bg-slate-400" :style="{ animationDelay: n * 0.15 + 's' }" /></div>
        </div>
      </div>
      <form class="flex items-end gap-2 border-t border-line p-3" @submit.prevent="send">
        <textarea v-model="input" rows="2" maxlength="4000" class="input resize-none" placeholder="Type your message…" aria-label="Message" @keydown.enter.exact.prevent="send" />
        <button class="btn btn-primary !px-4" :disabled="sending || !input.trim()" aria-label="Send message"><Send class="h-4 w-4" /></button>
      </form>
    </section>

    <ConfirmDialog :open="!!deleteTarget" title="Delete conversation?" message="All messages in this conversation will be permanently deleted." @cancel="deleteTarget = null" @confirm="confirmRemove" />
  </div>
</template>
