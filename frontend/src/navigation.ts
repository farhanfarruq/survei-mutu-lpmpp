import type { AuthUser } from '@/stores/auth'

export type NavigationItem = {
  label: string
  to: string
  permission?: string
  external?: boolean
}

const foundationNavigation: NavigationItem[] = [
  { label: 'Beranda', to: '/app' },
  { label: 'Survei Saya', to: '/app/surveys' },
  { label: 'Riwayat Partisipasi', to: '/app/response-history' },
  { label: 'Dashboard Eksekutif', to: '/app/analytics', permission: 'report.read' },
  { label: 'Analisis AI', to: '/app/ai', permission: 'ai.read' },
  { label: 'Tindak Lanjut', to: '/app/follow-up', permission: 'finding.read' },
  { label: 'Notifikasi', to: '/app/notifications', permission: 'notification.read' },
  { label: 'Status Sistem', to: '/app/system', permission: 'system.status.view' },
  { label: 'Panel Administrasi', to: `${import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'}/admin`, permission: 'admin.panel.access', external: true },
]

export function navigationFor(user: AuthUser | null): NavigationItem[] {
  if (!user) return []

  return foundationNavigation.filter((item) => !item.permission || user.permissions.includes(item.permission))
}
