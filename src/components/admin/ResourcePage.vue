<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next'
import api, { parseError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { get, formatDate, firstErrors } from '@/utils/format'
import Modal from '@/components/ui/Modal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import Pagination from '@/components/ui/Pagination.vue'

/**
 * Config-driven admin list/CRUD page. All authorization is enforced by the backend; this is presentation only.
 * config: { title, endpoint, listPath?, searchable, filters[], columns[], fields[], canCreate, canEdit, canDelete,
 *           multipart, rowActions[], idKey, note }
 *   column: { key, label, type?: 'date'|'datetime'|'bool'|'badge', render?(row) }
 *   field:  { key, label, type: text|textarea|number|select|checkbox|lines|multicheck|file, options?, optionsFrom?, required?, help? }
 *   rowAction: { label, danger?, confirm?, fields?, show?(row), run(row, values, api) }
 */
const props = defineProps({ config: { type: Object, required: true } })
const toast = useToastStore()
const c = props.config

const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const search = ref('')
const filterValues = reactive({})
const dynamicOptions = reactive({})
let timer

// create / edit modal
const formOpen = ref(false)
const editing = ref(null)
const form = reactive({})
const files = reactive({})
const formErrors = ref({})
const saving = ref(false)

// delete + row action
const deleting = ref(null)
const action = ref(null)
const actionRow = ref(null)
const actionForm = reactive({})
const actionErrors = ref({})
const actionBusy = ref(false)
const confirmingAction = ref(false)

const idOf = (row) => row[c.idKey || 'id']
const hasActions = computed(() => c.canEdit || c.canDelete || (c.rowActions || []).length)

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, search: search.value || undefined }
    for (const f of c.filters || []) if (filterValues[f.key]) params[f.key] = filterValues[f.key]
    const { data } = await api.get(c.endpoint, { params })
    const list = c.listPath ? get(data, c.listPath) : data
    rows.value = Array.isArray(list) ? list : list.data
    const m = Array.isArray(list) ? {} : (list.meta ?? list)
    meta.value = { current_page: m.current_page || 1, last_page: m.last_page || 1, total: m.total || 0 }
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
}

async function loadOptions(fields) {
  for (const f of fields || []) {
    if (f.optionsFrom && !dynamicOptions[f.key]) {
      try {
        const { data } = await api.get(f.optionsFrom.url)
        dynamicOptions[f.key] = (data.data ?? data).map((o) => [o[f.optionsFrom.value || 'id'], o[f.optionsFrom.label || 'name']])
      } catch { dynamicOptions[f.key] = [] }
    }
  }
}
const optionsFor = (f) => f.options || dynamicOptions[f.key] || []

function fillForm(target, fields, row) {
  for (const k of Object.keys(target)) delete target[k]
  for (const f of fields) {
    let v = row ? get(row, f.valueKey || f.key) : undefined
    if (f.type === 'lines') v = Array.isArray(v) ? v.join('\n') : ''
    else if (f.type === 'multicheck') v = row ? (f.valueFrom ? f.valueFrom(row) : v || []) : []
    else if (f.type === 'checkbox') v = row ? !!v : (f.default ?? false)
    else if (v == null) v = f.default ?? ''
    target[f.key] = v
  }
}

function openForm(row = null) {
  editing.value = row
  fillForm(form, c.fields, row)
  for (const k of Object.keys(files)) delete files[k]
  formErrors.value = {}
  loadOptions(c.fields)
  formOpen.value = true
}

function payload(fields, values) {
  const out = {}
  for (const f of fields) {
    let v = values[f.key]
    if (f.type === 'file') continue
    if (f.type === 'lines') v = String(v || '').split('\n').map((s) => s.trim()).filter(Boolean)
    else if (f.type === 'number') v = v === '' || v == null ? null : Number(v)
    else if (f.type === 'select' && f.number) v = v === '' ? null : Number(v)
    else if (f.type === 'text' || f.type === 'textarea') v = v === '' ? null : v
    out[f.key] = v
  }
  return out
}

async function save() {
  saving.value = true
  formErrors.value = {}
  try {
    const url = editing.value ? `${c.endpoint}/${idOf(editing.value)}` : c.endpoint
    const data = payload(c.fields, form)
    if (c.multipart) {
      const body = new FormData()
      if (editing.value) body.append('_method', 'PUT')
      for (const [k, v] of Object.entries(data)) {
        if (v === null || v === undefined) continue
        if (Array.isArray(v)) v.forEach((i) => body.append(`${k}[]`, i))
        else body.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : v)
      }
      for (const [k, f] of Object.entries(files)) if (f) body.append(k, f)
      await api.post(url, body)
    } else if (editing.value) await api.put(url, data)
    else await api.post(url, data)
    toast.success(editing.value ? 'Saved.' : 'Created.')
    formOpen.value = false
    await load(editing.value ? meta.value.current_page : 1)
  } catch (e) {
    const err = parseError(e)
    formErrors.value = firstErrors(err.errors)
    toast.error(err.message)
  } finally { saving.value = false }
}

async function confirmDelete() {
  try {
    await api.delete(`${c.endpoint}/${idOf(deleting.value)}`)
    toast.success('Deleted.')
    await load(meta.value.current_page)
  } catch (e) { toast.error(parseError(e).message) }
  deleting.value = null
}

function startAction(a, row) {
  action.value = a
  actionRow.value = row
  actionErrors.value = {}
  if (a.fields) { fillForm(actionForm, a.fields, a.prefill ? a.prefill(row) : row); loadOptions(a.fields) }
  else confirmingAction.value = true
}

async function runAction() {
  actionBusy.value = true
  actionErrors.value = {}
  try {
    await action.value.run(actionRow.value, action.value.fields ? payload(action.value.fields, actionForm) : {}, api)
    toast.success(action.value.success || 'Done.')
    action.value = null
    confirmingAction.value = false
    await load(meta.value.current_page)
  } catch (e) {
    const err = parseError(e)
    actionErrors.value = firstErrors(err.errors)
    toast.error(err.message)
    confirmingAction.value = false
  } finally { actionBusy.value = false }
}

const cell = (row, col) => {
  if (col.render) return col.render(row)
  const v = get(row, col.key)
  if (col.type === 'date') return formatDate(v)
  if (col.type === 'datetime') return formatDate(v, true)
  if (col.type === 'bool') return v ? 'Yes' : 'No'
  return v ?? '—'
}

watch(search, () => { clearTimeout(timer); timer = setTimeout(() => load(1), 350) })
watch(filterValues, () => load(1))
onMounted(() => { load(); loadOptions(c.fields) })
defineExpose({ reload: load })
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-2xl font-bold">{{ c.title }}</h1><p v-if="c.note" class="mt-1 text-sm">{{ c.note }}</p></div>
      <button v-if="c.canCreate" class="btn btn-primary" @click="openForm()"><Plus class="h-4 w-4" />{{ c.createLabel || 'Create' }}</button>
    </div>

    <div v-if="c.searchable || (c.filters || []).length" class="mt-5 flex flex-wrap gap-3">
      <div v-if="c.searchable" class="relative min-w-60 flex-1"><Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" /><input v-model="search" type="search" class="input pl-10" placeholder="Search…" aria-label="Search" /></div>
      <select v-for="f in c.filters || []" :key="f.key" v-model="filterValues[f.key]" class="input !w-44" :aria-label="f.label"><option value="">{{ f.label }}</option><option v-for="o in f.options" :key="o[0]" :value="o[0]">{{ o[1] }}</option></select>
    </div>

    <div v-if="loading" class="mt-6 space-y-2"><Skeleton v-for="n in 5" :key="n" class="h-10 w-full" /></div>
    <EmptyState v-else-if="error" class="mt-6" title="Could not load data" :message="error"><button class="btn btn-secondary" @click="load()">Try again</button></EmptyState>
    <EmptyState v-else-if="!rows.length" class="mt-6" title="Nothing here yet" message="No records match your filters." />
    <div v-else class="card mt-6 overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-line bg-slate-50 text-xs uppercase text-slate-500"><tr><th v-for="col in c.columns" :key="col.key + col.label" class="px-4 py-3">{{ col.label }}</th><th v-if="hasActions" class="px-4 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
          <tr v-for="row in rows" :key="idOf(row)">
            <td v-for="col in c.columns" :key="col.key + col.label" class="max-w-xs truncate px-4 py-3" :class="col.type === 'badge' && 'capitalize'">{{ cell(row, col) }}</td>
            <td v-if="hasActions" class="whitespace-nowrap px-4 py-3 text-right">
              <button v-for="a in (c.rowActions || []).filter((x) => !x.show || x.show(row))" :key="a.label" class="mr-1 rounded-lg px-2 py-1 text-xs font-medium hover:bg-slate-100" :class="a.danger ? 'text-red-600' : 'text-primary'" @click="startAction(a, row)">{{ a.label }}</button>
              <button v-if="c.canEdit" class="rounded-lg p-1.5 hover:bg-slate-100" aria-label="Edit" @click="openForm(row)"><Pencil class="h-4 w-4" /></button>
              <button v-if="c.canDelete" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50" aria-label="Delete" @click="deleting = row"><Trash2 class="h-4 w-4" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" @change="load" />

    <!-- create / edit -->
    <Modal :open="formOpen" :title="editing ? `Edit ${c.singular || 'item'}` : `New ${c.singular || 'item'}`" wide @close="formOpen = false">
      <form class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="save">
        <div v-for="f in c.fields" :key="f.key" :class="['textarea', 'lines', 'multicheck', 'file'].includes(f.type) && 'sm:col-span-2'">
          <label v-if="f.type !== 'checkbox'" :for="'f-' + f.key" class="label">{{ f.label }}<span v-if="f.required" class="text-red-500"> *</span></label>
          <select v-if="f.type === 'select'" :id="'f-' + f.key" v-model="form[f.key]" class="input"><option v-if="!f.required" value="">—</option><option v-for="o in optionsFor(f)" :key="o[0]" :value="o[0]">{{ o[1] }}</option></select>
          <textarea v-else-if="f.type === 'textarea' || f.type === 'lines'" :id="'f-' + f.key" v-model="form[f.key]" :rows="f.rows || 4" class="input" />
          <label v-else-if="f.type === 'checkbox'" class="flex items-center gap-2 pt-6 text-sm"><input v-model="form[f.key]" type="checkbox" class="h-4 w-4 rounded border-line" />{{ f.label }}</label>
          <div v-else-if="f.type === 'multicheck'" class="flex flex-wrap gap-3"><label v-for="o in optionsFor(f)" :key="o[0]" class="flex items-center gap-1.5 text-sm"><input v-model="form[f.key]" type="checkbox" :value="o[0]" class="h-4 w-4 rounded border-line" />{{ o[1] }}</label></div>
          <input v-else-if="f.type === 'file'" :id="'f-' + f.key" type="file" accept="image/png,image/jpeg,image/webp" class="text-sm" @change="files[f.key] = $event.target.files[0]" />
          <input v-else :id="'f-' + f.key" v-model="form[f.key]" :type="f.type === 'number' ? 'number' : 'text'" class="input" />
          <p v-if="f.help" class="mt-1 text-xs">{{ f.help }}</p>
          <p v-if="formErrors[f.key]" class="mt-1 text-xs text-red-600">{{ formErrors[f.key] }}</p>
        </div>
        <div class="flex justify-end gap-3 sm:col-span-2"><button type="button" class="btn btn-secondary" @click="formOpen = false">Cancel</button><button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button></div>
      </form>
    </Modal>

    <!-- row action with a form -->
    <Modal :open="!!action && !!action.fields" :title="action?.label" @close="action = null">
      <form v-if="action?.fields" class="space-y-4" novalidate @submit.prevent="runAction">
        <p v-if="action.help" class="text-sm">{{ action.help }}</p>
        <div v-for="f in action.fields" :key="f.key">
          <label v-if="f.type !== 'checkbox'" :for="'a-' + f.key" class="label">{{ f.label }}</label>
          <textarea v-if="f.type === 'textarea'" :id="'a-' + f.key" v-model="actionForm[f.key]" rows="5" class="input" />
          <select v-else-if="f.type === 'select'" :id="'a-' + f.key" v-model="actionForm[f.key]" class="input"><option v-for="o in optionsFor(f)" :key="o[0]" :value="o[0]">{{ o[1] }}</option></select>
          <div v-else-if="f.type === 'multicheck'" class="flex flex-wrap gap-3"><label v-for="o in optionsFor(f)" :key="o[0]" class="flex items-center gap-1.5 text-sm"><input v-model="actionForm[f.key]" type="checkbox" :value="o[0]" class="h-4 w-4 rounded border-line" />{{ o[1] }}</label></div>
          <input v-else :id="'a-' + f.key" v-model="actionForm[f.key]" :type="f.type === 'number' ? 'number' : 'text'" class="input" />
          <p v-if="actionErrors[f.key]" class="mt-1 text-xs text-red-600">{{ actionErrors[f.key] }}</p>
        </div>
        <div class="flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="action = null">Cancel</button><button class="btn btn-primary" :disabled="actionBusy">{{ actionBusy ? 'Working…' : action.submitLabel || 'Confirm' }}</button></div>
      </form>
    </Modal>

    <ConfirmDialog :open="!!deleting" :title="`Delete ${c.singular || 'item'}?`" @cancel="deleting = null" @confirm="confirmDelete" />
    <ConfirmDialog :open="confirmingAction" :title="action?.label + '?'" :message="action?.confirm || 'Please confirm this action.'" :confirm-label="action?.label || 'Confirm'" :loading="actionBusy" @cancel="confirmingAction = false; action = null" @confirm="runAction" />
  </div>
</template>
