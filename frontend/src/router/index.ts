import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'

import AppShell from '@/layouts/AppShell.vue'
import { analyticsRoles, canAccessVue, destinationAfterLogin } from '@/navigation'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { title: 'Masuk', guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { title: 'Buat Akun', guestOnly: true },
    },
    {
      path: '/app',
      component: AppShell,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'app-dashboard',
          component: () => import('@/views/FoundationDashboardView.vue'),
          meta: { title: 'Beranda', roles: ['respondent'] },
        },
        {
          path: 'surveys',
          name: 'eligible-surveys',
          component: () => import('@/views/EligibleSurveysView.vue'),
          meta: { title: 'Survei Saya', roles: ['respondent'] },
        },
        {
          path: 'surveys/:id',
          name: 'eligible-survey-detail',
          component: () => import('@/views/SurveyDetailView.vue'),
          meta: { title: 'Detail Survei', roles: ['respondent'] },
        },
        {
          path: 'response-history',
          name: 'response-history',
          component: () => import('@/views/ResponseHistoryView.vue'),
          meta: { title: 'Riwayat Partisipasi', roles: ['respondent'] },
        },
        {
          path: 'analytics',
          name: 'executive-dashboard',
          component: () => import('@/views/ExecutiveDashboardView.vue'),
          meta: {
            title: 'Dashboard Hasil Survei',
            permission: 'report.read',
            roles: analyticsRoles,
          },
        },
        {
          path: 'ai',
          name: 'ai-workspace',
          component: () => import('@/views/AiWorkspaceView.vue'),
          meta: { title: 'Analisis AI', permission: 'ai.read', roles: analyticsRoles },
        },
        {
          path: 'notifications',
          name: 'notifications',
          component: () => import('@/views/NotificationsView.vue'),
          meta: {
            title: 'Notifikasi',
            permission: 'notification.read',
            roles: ['respondent', ...analyticsRoles],
          },
        },
        {
          path: 'follow-up',
          name: 'follow-up-workspace',
          component: () => import('@/views/FollowUpView.vue'),
          meta: { title: 'Tindak Lanjut', permission: 'finding.read', roles: analyticsRoles },
        },
        {
          path: 'follow-ups/actions/:id',
          name: 'follow-up-action',
          component: () => import('@/views/FollowUpView.vue'),
          meta: { title: 'Detail Tindak Lanjut', permission: 'action.read', roles: analyticsRoles },
        },
      ],
    },
    {
      path: '/invitations/:token',
      name: 'external-invitation',
      component: () => import('@/views/ExternalInvitationView.vue'),
      meta: { title: 'Undangan Survei' },
    },
    {
      path: '/respond/responses/:id',
      name: 'live-response-form',
      component: () => import('@/views/ResponseFormView.vue'),
      meta: { title: 'Pengisian Survei' },
    },
    {
      path: '/forbidden',
      name: 'forbidden',
      component: () => import('@/views/ForbiddenView.vue'),
      meta: { title: 'Akses Ditolak' },
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth || to.meta.guestOnly) await auth.initialize()

  if (to.meta.requiresAuth && !auth.authenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.authenticated) {
    const destination = destinationAfterLogin(auth.user)
    if (destination.external) {
      window.location.assign(destination.to)
      return false
    }

    return destination.to
  }

  if (to.meta.requiresAuth && !canAccessVue(auth.user)) {
    const destination = destinationAfterLogin(auth.user)
    if (destination.external) window.location.assign(destination.to)
    return destination.external ? false : destination.to
  }

  const roles = Array.isArray(to.meta.roles)
    ? to.meta.roles.filter((role): role is string => typeof role === 'string')
    : []

  if (roles.length > 0 && !roles.some((role) => auth.user?.roles.includes(role))) {
    return { name: 'forbidden' }
  }

  const permission = typeof to.meta.permission === 'string' ? to.meta.permission : null

  if (permission && !auth.can(permission)) {
    return { name: 'forbidden' }
  }
})

router.afterEach((to) => {
  document.title = `${String(to.meta.title ?? 'Aplikasi')} · SIMUTU`
  void nextTick(() => document.querySelector<HTMLElement>('h1')?.focus())
})

export default router
