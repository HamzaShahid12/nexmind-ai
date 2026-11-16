<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'

const auth = useAuthStore()
const toast = useToastStore()
const roles = ref([])
const permissions = ref([])
const loading = ref(true)
const error = ref('')
const saving = ref(null)
const isSuper = computed(() => (auth.user?.roles || []).includes('super_admin'))

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/roles')
    roles.value = data.data.map((r) => ({ ...r, selected: r.permissions.map((p) => p.name) }))
    permissions.value = data.permissions
  } catch (e) { error.value = parseError(e).message } finally { loading.value = false }
})

async function save(role) {
  saving.value = role.id
  try {
    await api.put(`/admin/roles/${role.id}`, { permissions: role.selected })
    toast.success(`${role.name} permissions saved.`)
  } catch (e) { toast.error(parseError(e).message) } finally { saving.value = null }
}
</script>

<template>
  <div class="mx-auto max-w-4xl">
    <h1 class="text-2xl font-bold">Roles &amp; Permissions</h1>
    <p class="mt-1 text-sm">Access to the admin panel is decided on the server by role. Only super administrators can edit permissions or assign roles.</p>
    <Skeleton v-if="loading" class="mt-6 h-40 w-full" />
    <EmptyState v-else-if="error" class="mt-6" title="Could not load roles" :message="error" />
    <div v-else class="mt-6 space-y-4">
      <section v-for="r in roles" :key="r.id" class="card p-5">
        <div class="flex items-center justify-between"><h2 class="font-semibold capitalize">{{ r.name.replace('_', ' ') }}</h2><span class="text-xs">{{ r.users_count }} user{{ r.users_count === 1 ? '' : 's' }}</span></div>
        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
          <label v-for="p in permissions" :key="p" class="flex items-center gap-1.5 text-sm"><input v-model="r.selected" type="checkbox" :value="p" :disabled="!isSuper || r.name === 'super_admin'" class="h-4 w-4 rounded border-line" />{{ p }}</label>
        </div>
        <div v-if="isSuper && r.name !== 'super_admin'" class="mt-4 flex justify-end"><button class="btn btn-primary" :disabled="saving === r.id" @click="save(r)">{{ saving === r.id ? 'Saving…' : 'Save permissions' }}</button></div>
      </section>
    </div>
  </div>
</template>
