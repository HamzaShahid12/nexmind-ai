<script setup>
import { reactive, ref, onMounted } from 'vue'
import api, { parseError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { firstErrors, formatDate } from '@/utils/format'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const auth = useAuthStore()
const toast = useToastStore()

const profile = reactive({ name: '', email: '', language: 'English', tone: 'professional', notify_email: true })
const avatar = ref(null)
const profileErrors = ref({})
const savingProfile = ref(false)

const pw = reactive({ current_password: '', password: '', password_confirmation: '' })
const pwErrors = ref({})
const savingPw = ref(false)
const confirmDeletion = ref(false)

function fill() {
  const u = auth.user
  if (!u) return
  Object.assign(profile, { name: u.name, email: u.email, language: u.language, tone: u.tone, notify_email: !!u.notify_email })
}

async function saveProfile() {
  savingProfile.value = true
  profileErrors.value = {}
  try {
    // Multipart so the avatar can be uploaded; Laravel reads _method=PUT.
    const body = new FormData()
    body.append('_method', 'PUT')
    body.append('name', profile.name)
    body.append('email', profile.email)
    body.append('language', profile.language)
    body.append('tone', profile.tone)
    body.append('notify_email', profile.notify_email ? '1' : '0')
    if (avatar.value) body.append('avatar', avatar.value)
    const { data } = await api.post('/profile', body)
    auth.user = data.data
    avatar.value = null
    toast.success('Profile updated.')
  } catch (e) {
    const err = parseError(e)
    profileErrors.value = firstErrors(err.errors)
    toast.error(err.message)
  } finally { savingProfile.value = false }
}

async function changePassword() {
  savingPw.value = true
  pwErrors.value = {}
  try {
    await api.put('/profile/password', pw)
    Object.assign(pw, { current_password: '', password: '', password_confirmation: '' })
    toast.success('Password updated.')
  } catch (e) {
    const err = parseError(e)
    pwErrors.value = firstErrors(err.errors)
    toast.error(err.message)
  } finally { savingPw.value = false }
}

async function resendVerification() {
  try { await api.post('/email/verification-notification'); toast.success('Verification email sent.') } catch (e) { toast.error(parseError(e).message) }
}

async function requestDeletion() {
  try {
    await api.post('/profile/deletion-request')
    await auth.fetchUser()
    toast.success('Deletion request recorded.')
  } catch (e) { toast.error(parseError(e).message) }
  confirmDeletion.value = false
}

onMounted(fill)
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold">Profile Settings</h1>

    <form class="card space-y-4 p-5" @submit.prevent="saveProfile">
      <h2 class="font-semibold">Personal information &amp; preferences</h2>
      <div class="flex items-center gap-4">
        <img v-if="auth.user?.avatar_url" :src="auth.user.avatar_url" alt="Your profile picture" class="h-16 w-16 rounded-full object-cover" />
        <div v-else class="grid h-16 w-16 place-items-center rounded-full bg-primary/10 text-xl font-bold text-primary">{{ auth.user?.name?.[0]?.toUpperCase() }}</div>
        <div><label for="avatar" class="label">Profile picture</label><input id="avatar" type="file" accept="image/png,image/jpeg,image/webp" class="text-sm" @change="avatar = $event.target.files[0]" /><p v-if="profileErrors.avatar" class="text-xs text-red-600">{{ profileErrors.avatar }}</p></div>
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div><label for="name" class="label">Name</label><input id="name" v-model="profile.name" class="input" /><p v-if="profileErrors.name" class="mt-1 text-xs text-red-600">{{ profileErrors.name }}</p></div>
        <div><label for="email" class="label">Email</label><input id="email" v-model="profile.email" type="email" class="input" /><p v-if="profileErrors.email" class="mt-1 text-xs text-red-600">{{ profileErrors.email }}</p></div>
        <div><label for="language" class="label">Default language</label><input id="language" v-model="profile.language" class="input" /></div>
        <div><label for="tone" class="label">Default writing tone</label>
          <select id="tone" v-model="profile.tone" class="input"><option v-for="t in ['professional', 'friendly', 'formal', 'conversational', 'persuasive', 'creative']" :key="t" :value="t">{{ t }}</option></select></div>
      </div>
      <label class="flex items-center gap-2 text-sm"><input v-model="profile.notify_email" type="checkbox" class="h-4 w-4 rounded border-line" /> Send me email notifications</label>
      <div class="flex justify-end"><button class="btn btn-primary" :disabled="savingProfile">{{ savingProfile ? 'Saving…' : 'Save changes' }}</button></div>
    </form>

    <form class="card space-y-4 p-5" @submit.prevent="changePassword">
      <h2 class="font-semibold">Security</h2>
      <p class="text-sm">Email status:
        <span v-if="auth.user?.email_verified_at" class="font-medium text-emerald-600">Verified</span>
        <span v-else class="font-medium text-amber-600">Not verified</span>
        <button v-if="!auth.user?.email_verified_at" type="button" class="ml-2 text-primary underline" @click="resendVerification">Resend email</button>
      </p>
      <div class="grid gap-4 sm:grid-cols-3">
        <div><label for="cp" class="label">Current password</label><input id="cp" v-model="pw.current_password" type="password" autocomplete="current-password" class="input" /><p v-if="pwErrors.current_password" class="mt-1 text-xs text-red-600">{{ pwErrors.current_password }}</p></div>
        <div><label for="np" class="label">New password</label><input id="np" v-model="pw.password" type="password" autocomplete="new-password" class="input" /><p v-if="pwErrors.password" class="mt-1 text-xs text-red-600">{{ pwErrors.password }}</p></div>
        <div><label for="cnp" class="label">Confirm password</label><input id="cnp" v-model="pw.password_confirmation" type="password" autocomplete="new-password" class="input" /></div>
      </div>
      <div class="flex justify-end"><button class="btn btn-primary" :disabled="savingPw">{{ savingPw ? 'Updating…' : 'Change password' }}</button></div>
    </form>

    <section class="card space-y-3 p-5">
      <h2 class="font-semibold">Account</h2>
      <dl class="grid gap-3 text-sm sm:grid-cols-3">
        <div><dt>Plan</dt><dd class="font-semibold text-ink">{{ auth.user?.plan?.name ?? '—' }}</dd></div>
        <div><dt>Credit balance</dt><dd class="font-semibold text-ink">{{ auth.user?.credits_balance }}</dd></div>
        <div><dt>Member since</dt><dd class="font-semibold text-ink">{{ formatDate(auth.user?.created_at) }}</dd></div>
      </dl>
      <div class="border-t border-line pt-4">
        <p v-if="auth.user?.deletion_requested_at" class="text-sm text-amber-700">Deletion requested on {{ formatDate(auth.user.deletion_requested_at) }}. An administrator will review your request.</p>
        <template v-else>
          <p class="text-sm">Need to close your account? Send a deletion request and an administrator will review it.</p>
          <button class="btn btn-danger mt-3" @click="confirmDeletion = true">Request account deletion</button>
        </template>
      </div>
    </section>

    <ConfirmDialog :open="confirmDeletion" title="Request account deletion?" message="An administrator will review your request. Your account is not deleted automatically." confirm-label="Send request" @cancel="confirmDeletion = false" @confirm="requestDeletion" />
  </div>
</template>
