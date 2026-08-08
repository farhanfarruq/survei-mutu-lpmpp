import { describe, expect, it } from 'vitest'

import { navigationFor } from '@/navigation'
import type { AuthUser } from '@/stores/auth'

const user = (permissions: string[]): AuthUser => ({
  id: '0198ea92-83ae-7c93-a53d-3c1ee8d29b13',
  name: 'Pengguna Fiktif',
  email: 'pengguna@example.test',
  is_active: true,
  roles: ['respondent'],
  permissions,
  organizational_units: [],
})

describe('navigationFor', () => {
  it('hides protected navigation without permission', () => {
    expect(navigationFor(user([])).map(({ label }) => label)).toEqual(['Beranda', 'Survei Saya', 'Riwayat Partisipasi'])
  })

  it('shows only navigation allowed by effective permissions', () => {
    expect(navigationFor(user(['system.status.view', 'admin.panel.access'])).map(({ label }) => label)).toEqual([
      'Beranda',
      'Survei Saya',
      'Riwayat Partisipasi',
      'Status Sistem',
      'Panel Administrasi',
    ])
  })

  it('shows the executive dashboard only with report read permission', () => {
    expect(navigationFor(user(['report.read'])).map(({ label }) => label)).toContain('Dashboard Eksekutif')
    expect(navigationFor(user([])).map(({ label }) => label)).not.toContain('Dashboard Eksekutif')
  })

  it('shows Phase 13 workspaces only with their effective permission', () => {
    const labels = navigationFor(user(['ai.read', 'finding.read', 'notification.read'])).map(({ label }) => label)
    expect(labels).toEqual(expect.arrayContaining(['Analisis AI', 'Tindak Lanjut', 'Notifikasi']))
    expect(navigationFor(user([])).map(({ label }) => label)).not.toContain('Analisis AI')
  })
})
