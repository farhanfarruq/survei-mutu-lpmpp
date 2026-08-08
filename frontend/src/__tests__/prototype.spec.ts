import { describe, expect, it } from 'vitest'

import { filterSurveys, missingRequiredAnswers } from '@/prototype'

describe('prototype survey rules', () => {
  it('blocks final submit until every required answer exists', () => {
    expect(missingRequiredAnswers({ 'LA-01': 4 })).toEqual(['LA-02', 'LA-03'])
    expect(missingRequiredAnswers({ 'LA-01': 4, 'LA-02': 3, 'LA-03': 4 })).toEqual([])
  })

  it('filters fixture surveys without changing the source data', () => {
    expect(filterSurveys('perpustakaan', 'Semua')).toHaveLength(1)
    expect(filterSurveys('', 'Aktif')).toHaveLength(2)
    expect(filterSurveys('tidak ada', 'Semua')).toEqual([])
  })
})
