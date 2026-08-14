import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { api, initializeCsrf, normalizeApiError, type ProblemError } from '@/services/api'

export type OrganizationalMembership = {
  id: string
  code: string
  name: string
  scope_mode: 'self' | 'subtree'
  is_primary: boolean
}

export type AuthUser = {
  id: string
  name: string
  identity_number: string | null
  account_type: 'student' | 'lecturer' | null
  is_active: boolean
  roles: string[]
  permissions: string[]
  organizational_units: OrganizationalMembership[]
}

export type RegistrationPayload = {
  name: string
  account_type: 'student' | 'lecturer'
  identity_number: string
  organizational_unit_id: string
  password: string
  password_confirmation: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const initialized = ref(false)
  const loading = ref(false)
  const error = ref<ProblemError | null>(null)
  const authenticated = computed(() => user.value !== null)

  async function fetchUser(): Promise<AuthUser | null> {
    try {
      const response = await api.get<{ data: AuthUser }>('/api/v1/me')
      user.value = response.data.data
      return user.value
    } catch (caught) {
      if (normalizeApiError(caught).status === 401) user.value = null
      else throw caught
      return null
    }
  }

  async function initialize(): Promise<void> {
    if (initialized.value) return
    try {
      await fetchUser()
    } finally {
      initialized.value = true
    }
  }

  async function login(identityNumber: string, password: string, remember: boolean): Promise<void> {
    loading.value = true
    error.value = null
    try {
      if (await fetchUser()) return
      await initializeCsrf()
      await api.post('/api/v1/auth/login', { identity_number: identityNumber, password, remember })
      await fetchUser()
    } catch (caught) {
      error.value = normalizeApiError(caught)
      throw caught
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function register(payload: RegistrationPayload): Promise<void> {
    loading.value = true
    error.value = null
    try {
      await initializeCsrf()
      await api.post('/api/v1/auth/register', payload)
      await api.post('/api/v1/auth/login', {
        identity_number: payload.identity_number,
        password: payload.password,
        remember: false,
      })
      await fetchUser()
    } catch (caught) {
      error.value = normalizeApiError(caught)
      throw caught
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function logout(): Promise<void> {
    loading.value = true
    try {
      await api.post('/api/v1/auth/logout')
    } finally {
      user.value = null
      loading.value = false
    }
  }

  function can(permission: string): boolean {
    return user.value?.permissions.includes(permission) ?? false
  }

  return {
    user,
    initialized,
    loading,
    error,
    authenticated,
    initialize,
    login,
    register,
    logout,
    can,
  }
})
