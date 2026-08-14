import type { AuthUser } from '@/stores/auth'
import { apiBaseUrl } from '@/services/api'

export type NavigationItem = {
  label: string
  to: string
  permission?: string
  roles?: string[]
  external?: boolean
}

export type LoginDestination = {
  to: string
  external: boolean
}

const backendUrl = apiBaseUrl

const foundationNavigation: NavigationItem[] = [
  { label: 'Beranda', to: '/app', roles: ['respondent'] },
  { label: 'Survei Saya', to: '/app/surveys', roles: ['respondent'] },
  { label: 'Riwayat Partisipasi', to: '/app/response-history', roles: ['respondent'] },
  { label: 'Dashboard Eksekutif', to: '/app/analytics', permission: 'report.read', roles: ['leader'] },
  { label: 'Analisis AI', to: '/app/ai', permission: 'ai.read', roles: ['leader'] },
  { label: 'Tindak Lanjut', to: '/app/follow-up', permission: 'finding.read', roles: ['leader'] },
  { label: 'Notifikasi', to: '/app/notifications', permission: 'notification.read', roles: ['respondent', 'leader'] },
  { label: 'Panel Administrasi', to: `${backendUrl}/admin`, roles: ['leader'], external: true },
]

export function navigationFor(user: AuthUser | null): NavigationItem[] {
  if (!user) return []

  return foundationNavigation.filter((item) =>
    (!item.roles || item.roles.some((role) => user.roles.includes(role)))
    && (!item.permission || user.permissions.includes(item.permission)),
  )
}

export function canAccessVue(user: AuthUser | null): boolean {
  return user?.roles.some((role) => role === 'respondent' || role === 'leader') ?? false
}

export function destinationAfterLogin(user: AuthUser | null, requestedRedirect?: unknown): LoginDestination {
  if (user?.roles.some((role) => role === 'super_admin' || role === 'admin_lpmpp')) {
    return { to: `${backendUrl}/admin`, external: true }
  }

  if (typeof requestedRedirect === 'string' && requestedRedirect.startsWith('/app')) {
    return { to: requestedRedirect, external: false }
  }

  if (user?.roles.includes('leader')) return { to: '/app/analytics', external: false }
  if (user?.roles.includes('respondent')) return { to: '/app', external: false }

  return { to: '/forbidden', external: false }
}
