import { describe, expect, it } from 'vitest'

import { canAccessVue, destinationAfterLogin, navigationFor } from '@/navigation'
import type { AuthUser } from '@/stores/auth'

const user = (permissions: string[], roles = ['respondent']): AuthUser => ({
  id: '0198ea92-83ae-7c93-a53d-3c1ee8d29b13',
  name: 'Pengguna Fiktif',
  identity_number: '20260001',
  account_type: 'student',
  is_active: true,
  roles,
  permissions,
  organizational_units: [],
})

describe('navigationFor', () => {
  it('hides protected navigation without permission', () => {
    expect(navigationFor(user([])).map(({ label }) => label)).toEqual([
      'Beranda',
      'Survei Saya',
      'Riwayat Partisipasi',
    ])
  })

  it('keeps respondent navigation separate from administration', () => {
    expect(navigationFor(user(['admin.panel.access'])).map(({ label }) => label)).toEqual([
      'Beranda',
      'Survei Saya',
      'Riwayat Partisipasi',
    ])
  })

  it('shows leader read-only navigation and the Filament shortcut', () => {
    const labels = navigationFor(
      user(['report.read', 'ai.read', 'finding.read', 'notification.read'], ['leader']),
    ).map(({ label }) => label)
    expect(labels).toEqual([
      'Dashboard Hasil Survei',
      'Analisis AI',
      'Tindak Lanjut',
      'Notifikasi',
      'Panel Administrasi',
    ])
    expect(labels).not.toContain('Survei Saya')
  })

  it.each(['admin_lpmpp', 'super_admin'])('shows the survey dashboard for %s', (role) => {
    expect(navigationFor(user(['report.read'], [role])).map(({ label }) => label)).toEqual([
      'Dashboard Hasil Survei',
      'Panel Administrasi',
    ])
  })

  it.each(['admin_lpmpp', 'super_admin'])('shows AI and follow-up tools allowed for %s', (role) => {
    expect(
      navigationFor(
        user(['report.read', 'ai.read', 'finding.read', 'notification.read'], [role]),
      ).map(({ label }) => label),
    ).toEqual([
      'Dashboard Hasil Survei',
      'Analisis AI',
      'Tindak Lanjut',
      'Notifikasi',
      'Panel Administrasi',
    ])
  })
})

describe('destinationAfterLogin', () => {
  it.each(['admin_lpmpp', 'super_admin', 'leader'])(
    'opens the survey dashboard by default for %s',
    (role) => {
      expect(destinationAfterLogin(user(['report.read'], [role]))).toEqual({
        external: false,
        to: '/app/analytics',
      })
    },
  )

  it('keeps respondents in Vue and opens the leader dashboard by default', () => {
    expect(destinationAfterLogin(user([]))).toEqual({ external: false, to: '/app' })
    expect(destinationAfterLogin(user(['report.read'], ['leader']))).toEqual({
      external: false,
      to: '/app/analytics',
    })
    expect(destinationAfterLogin(user(['report.read'], ['leader']), '/app/notifications')).toEqual({
      external: false,
      to: '/app/notifications',
    })
  })

  it('allows Vue for every application role', () => {
    expect(canAccessVue(user([], ['respondent']))).toBe(true)
    expect(canAccessVue(user([], ['leader']))).toBe(true)
    expect(canAccessVue(user([], ['admin_lpmpp']))).toBe(true)
    expect(canAccessVue(user([], ['super_admin']))).toBe(true)
  })
})
