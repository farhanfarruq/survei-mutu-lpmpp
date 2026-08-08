export type DemoRole = 'respondent' | 'admin' | 'leader'

export type Survey = {
  id: string
  title: string
  period: string
  status: 'Aktif' | 'Selesai' | 'Akan datang'
  progress: number
  estimate: string
  identityMode: 'Rahasia' | 'Anonim'
}

export type Question = {
  code: string
  category: string
  indicator: string
  text: string
  required: boolean
}

export const surveys: Survey[] = [
  {
    id: 'academic-service-2026',
    title: 'Kepuasan Mahasiswa terhadap Layanan Akademik',
    period: 'Semester Genap 2025/2026',
    status: 'Aktif',
    progress: 40,
    estimate: '6–8 menit',
    identityMode: 'Rahasia',
  },
  {
    id: 'digital-library-2026',
    title: 'Evaluasi Layanan Perpustakaan Digital',
    period: 'Agustus 2026',
    status: 'Aktif',
    progress: 0,
    estimate: '4 menit',
    identityMode: 'Anonim',
  },
  {
    id: 'student-affairs-2025',
    title: 'Kepuasan Layanan Kemahasiswaan',
    period: 'Semester Ganjil 2025/2026',
    status: 'Selesai',
    progress: 100,
    estimate: '5 menit',
    identityMode: 'Rahasia',
  },
]

export const questions: Question[] = [
  {
    code: 'LA-01',
    category: 'Keandalan',
    indicator: 'Ketepatan informasi',
    text: 'Informasi jadwal perkuliahan tersedia secara tepat waktu.',
    required: true,
  },
  {
    code: 'LA-02',
    category: 'Daya tanggap',
    indicator: 'Kecepatan layanan',
    text: 'Permintaan layanan akademik ditanggapi dalam waktu yang wajar.',
    required: true,
  },
  {
    code: 'LA-03',
    category: 'Kejelasan',
    indicator: 'Prosedur layanan',
    text: 'Prosedur pengajuan layanan akademik mudah dipahami.',
    required: true,
  },
]

export const categoryScores = [
  { label: 'Keandalan', score: 84 },
  { label: 'Daya tanggap', score: 77 },
  { label: 'Jaminan', score: 86 },
  { label: 'Empati', score: 81 },
  { label: 'Bukti fisik', score: 83 },
]

export const unitMetrics = {
  university: { label: 'Seluruh unit dalam scope', score: '82,4', rate: '65,0%', priority: '3', actions: '12/18' },
  engineering: { label: 'Fakultas Teknik', score: '84,1', rate: '71,2%', priority: '2', actions: '5/6' },
  economics: { label: 'Fakultas Ekonomi', score: '79,8', rate: '61,7%', priority: '4', actions: '3/5' },
} as const

export const navByRole = {
  respondent: [
    { label: 'Beranda', to: '/respondent' },
    { label: 'Survei Saya', to: '/surveys' },
  ],
  admin: [
    { label: 'Ikhtisar', to: '/admin' },
    { label: 'Builder', to: '/builder' },
    { label: 'Monitoring', to: '/monitoring' },
    { label: 'Hasil', to: '/results' },
    { label: 'Analisis AI', to: '/ai-analysis' },
    { label: 'Tindak lanjut', to: '/follow-up' },
    { label: 'Laporan', to: '/reports' },
    { label: 'Konfigurasi AI', to: '/ai-config' },
  ],
  leader: [
    { label: 'Dashboard Pimpinan', to: '/leadership' },
    { label: 'Laporan', to: '/reports' },
  ],
} as const

export function missingRequiredAnswers(answers: Record<string, number>): string[] {
  return questions.filter((question) => question.required && !answers[question.code]).map((question) => question.code)
}

export function filterSurveys(query: string, status: string): Survey[] {
  const normalized = query.trim().toLocaleLowerCase('id-ID')
  return surveys.filter(
    (survey) =>
      (status === 'Semua' || survey.status === status) &&
      (!normalized || `${survey.title} ${survey.period}`.toLocaleLowerCase('id-ID').includes(normalized)),
  )
}
