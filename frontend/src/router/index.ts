import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'

import AppShell from '@/layouts/AppShell.vue'
import { useAuthStore } from '@/stores/auth'

const prototypeRoutes = [
  { path: '/respondent', name: 'respondent', title: 'Beranda Responden' },
  { path: '/surveys', name: 'surveys', title: 'Survei Saya' },
  { path: '/surveys/:id', name: 'survey-detail', title: 'Detail Survei' },
  { path: '/responses/:id', name: 'response-form', title: 'Pengisian Survei' },
  { path: '/admin', name: 'admin', title: 'Ikhtisar Admin LPMPP' },
  { path: '/builder', name: 'builder', title: 'Builder Instrumen' },
  { path: '/monitoring', name: 'monitoring', title: 'Monitoring Respons' },
  { path: '/results', name: 'results', title: 'Hasil Survei' },
  { path: '/leadership', name: 'leadership', title: 'Dashboard Pimpinan' },
  { path: '/ai-analysis', name: 'ai-analysis', title: 'Analisis AI' },
  { path: '/ai-config', name: 'ai-config', title: 'Konfigurasi AI' },
  { path: '/follow-up', name: 'follow-up', title: 'Tindak Lanjut' },
  { path: '/reports', name: 'reports', title: 'Laporan' },
].map(({ path, name, title }) => ({ path, name, component: () => import('@/views/PrototypeView.vue'), meta: { title } }))

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { title: 'Masuk', guestOnly: true } },
    {
      path: '/app',
      component: AppShell,
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'app-dashboard', component: () => import('@/views/FoundationDashboardView.vue'), meta: { title: 'Beranda' } },
        { path: 'surveys', name: 'eligible-surveys', component: () => import('@/views/EligibleSurveysView.vue'), meta: { title: 'Survei Saya' } },
        { path: 'surveys/:id', name: 'eligible-survey-detail', component: () => import('@/views/SurveyDetailView.vue'), meta: { title: 'Detail Survei' } },
        { path: 'response-history', name: 'response-history', component: () => import('@/views/ResponseHistoryView.vue'), meta: { title: 'Riwayat Partisipasi' } },
        { path: 'analytics', name: 'executive-dashboard', component: () => import('@/views/ExecutiveDashboardView.vue'), meta: { title: 'Dashboard Eksekutif', permission: 'report.read' } },
        { path: 'ai', name: 'ai-workspace', component: () => import('@/views/AiWorkspaceView.vue'), meta: { title: 'AI Analysis', permission: 'ai.read' } },
        { path: 'notifications', name: 'notifications', component: () => import('@/views/NotificationsView.vue'), meta: { title: 'Notifikasi', permission: 'notification.read' } },
        { path: 'follow-up', name: 'follow-up-workspace', component: () => import('@/views/FollowUpView.vue'), meta: { title: 'Tindak Lanjut', permission: 'finding.read' } },
        { path: 'follow-ups/actions/:id', name: 'follow-up-action', component: () => import('@/views/FollowUpView.vue'), meta: { title: 'Detail Tindak Lanjut', permission: 'action.read' } },
        {
          path: 'system',
          name: 'system-status',
          component: () => import('@/views/SystemStatusView.vue'),
          meta: { title: 'Status Sistem', permission: 'system.status.view' },
        },
      ],
    },
    { path: '/invitations/:token', name: 'external-invitation', component: () => import('@/views/ExternalInvitationView.vue'), meta: { title: 'Undangan Survei' } },
    { path: '/respond/responses/:id', name: 'live-response-form', component: () => import('@/views/ResponseFormView.vue'), meta: { title: 'Pengisian Survei' } },
    { path: '/forbidden', name: 'forbidden', component: () => import('@/views/ForbiddenView.vue'), meta: { title: 'Akses Ditolak' } },
    ...prototypeRoutes,
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
    return { name: 'app-dashboard' }
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
