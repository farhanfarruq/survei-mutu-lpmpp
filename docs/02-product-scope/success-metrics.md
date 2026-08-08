# Success Metrics and KPI Framework

Versi: **1.0 — 2026-08-07**  
Status target: **PROPOSED — memerlukan baseline pilot dan persetujuan owner**

## 1. Measurement principles

1. Produk dinilai dari keputusan/perbaikan yang dapat dibuktikan, bukan jumlah survei atau jumlah dashboard.
2. Response rate dilaporkan dengan denominator/disposition jelas dan tidak dianggap bukti otomatis bebas nonresponse bias.
3. KPI menampilkan `n`, period, scope, data source, limitation, dan owner.
4. Target tidak boleh mendorong penghilangan finding, pemaksaan respons, pembukaan cell kecil, atau penutupan action tanpa bukti.
5. Baseline dikumpulkan pada R1/R2; target produksi disahkan sebelum R3.

## 2. North Star dan guardrail

### North Star: Verified Improvement Loop Rate (VILR)

`VILR = jumlah campaign eligible yang seluruh finding prioritasnya memiliki action verified dan impact evaluation terjadwal dalam SLA / jumlah campaign eligible yang telah melewati SLA × 100%`

Campaign eligible adalah campaign dengan result released dan sedikitnya satu finding prioritas approved. Target awal: **≥70% pada R3** `[PROPOSED]`.

Guardrail:

- 100% released report menjalani completeness review agar finding tidak sengaja dihilangkan;
- uploaded evidence tidak dihitung verified;
- waived impact harus mempunyai approver, alasan, dan expiry serta dilaporkan terpisah;
- VILR tidak dihitung bila denominator <5; tampilkan count/status per campaign.

## 3. KPI catalog

### A. Governance dan metodologi

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-01 | Preflight Pass Rate = campaign published tanpa override/blocker ÷ published campaign | campaign/audit; bulanan | R2 | 100% | Product Owner | override critical tidak dibolehkan |
| SM-02 | Approved-Version Usage = campaign memakai version approved ÷ campaign opened | campaign/version; bulanan | R2 | 100% | Lead metodolog | approval creator sendiri tidak sah |
| SM-03 | Snapshot Completeness = campaign dengan instrument/scoring/privacy/threshold/analysis snapshot lengkap ÷ campaign | campaign; per release | R1 | 100% | LPMPP | field ada tetapi null/tidak approved dianggap gagal |
| SM-04 | Reproducible Analysis Rate = analysis golden/rerun match ÷ analysis run released | analysis lineage; per run | R1 | 100% | Lead analyst | perubahan input/version menghasilkan run baru |
| SM-05 | Method Eligibility Compliance = run memenuhi semua method precondition ÷ run | preflight/analysis; per run | R1 | 100% | Metodolog | metode tidak diaktifkan hanya untuk mencapai target |
| SM-06 | Unresolved Validation Flag Rate = item flag tanpa keputusan ÷ item flagged sebelum publish | review; per version | R1 | 0% | Reviewer lead | keputusan dapat retain/revise/remove dengan alasan |

### B. Population, response, dan data quality

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-07 | Frame Validity = row valid eligible/known disposition ÷ imported row | import report; campaign | R2 | ≥99% | Population owner | invalid dikarantina, bukan dihapus diam-diam |
| SM-08 | Delivery Success = delivered invitation ÷ valid invitation sent | provider log; campaign | R2 | ≥95% | Campaign owner/TIK | provider status dan bounce reason disimpan |
| SM-09 | Completion Rate = complete ÷ eligible invitation dengan disposition formula dipin | participation; campaign | R2 | baseline dulu; target per family | Campaign owner | tidak ada universal cutoff dan tidak memaksa partisipasi |
| SM-10 | Coverage Gap = maksimum selisih percentage point antara frame dan respondent pada atribut approved | frame/response aggregate; campaign | R2 | <15 pp atau mitigation | Analyst | hanya atribut legal dan cell aman |
| SM-11 | Item Missing Rate = missing non-N/A ÷ respondent eligible item | response; campaign/item | R2 | <10% per item; flag bila lebih | Metodolog | N/A dan refusal terpisah |
| SM-12 | Duplicate Submitted Response = reconciliation count response bisnis ganda | response; real-time/campaign | R1 | 0 | Technical owner | exactly-once, bukan dedupe pascahoc yang menghapus data sah |

### C. Respondent experience dan accessibility

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-13 | Respondent Task Success = peserta usability menyelesaikan notice→submit tanpa bantuan kritis ÷ peserta | usability test; release | R1 | ≥90% | Product Owner | sampel beragam dan isu kualitatif tetap dicatat |
| SM-14 | Median Completion Time = median durasi submitted response yang diprivacy-minimize | response aggregate; campaign | R2 | ≤10 menit untuk template pilot | Product Owner | bukan alasan memotong item wajib metodologis |
| SM-15 | Started-to-Submit Drop-off = started nonexpired − submitted ÷ started | aggregate; campaign | R2 | <15% atau root-cause review | Product Owner | withdrawal/technical failure dibedakan |
| SM-16 | Accessibility Blocker Count = known WCAG 2.2 AA blocker pada critical respondent flow | audit; release | R1 | 0 saat release | Accessibility owner | automated scan saja tidak cukup |
| SM-17 | Save/Submit Recovery Success = scenario interruption pulih tanpa kehilangan/ganda ÷ scenario | E2E/game-day; release | R1 | 100% | Technical owner | mencakup mobile network interruption |

### D. Reporting, privacy, dan security

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-18 | Time to Approved Report = waktu campaign close sampai report released | workflow; campaign | R2 | ≤5 hari kerja | LPMPP + reviewer | tidak melewati validation/suppression gate |
| SM-19 | Suppression Parity = channel output yang sama dengan golden policy ÷ channel diuji | test/control; release | R1 | 100% | Privacy Officer | screen/API/export seluruhnya diuji |
| SM-20 | Detached-Linkage Failure = jumlah field/query yang dapat menghubungkan participation ke content pada mode detached | architecture/privacy test; release | R1 | 0 | Privacy + Security | klaim anonymous dicabut bila test gagal |
| SM-21 | Unauthorized Data Access Incident = confirmed incident lintas scope/raw disclosure | incident log; real-time | 0 | 0 | Security owner | attempted access juga dimonitor terpisah |
| SM-22 | Critical Audit Coverage = aksi kritis dengan audit event lengkap ÷ aksi kritis | audit reconciliation; mingguan | R1 | 100% | Auditor | event tanpa actor/object/result dianggap tidak lengkap |
| SM-23 | Unapproved Raw Export = raw export tanpa purpose+approval+expiry | export audit; real-time | 0 | 0 | Data owner/Privacy | aggregate export tidak dicampur denominator |

### E. PPEPP dan outcome

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-24 | Finding Ownership SLA = finding prioritas punya PIC/due date ≤10 hari kerja ÷ finding prioritas | action register; mingguan | R2 | ≥90% | Head LPMPP | tidak menurunkan severity agar lolos SLA |
| SM-25 | On-time Action Rate = action completed/submitted by due date ÷ action due | action register; bulanan | R2 | ≥80% | Unit owner | submitted dipisah dari verified |
| SM-26 | Verification Rate = action verified ÷ action submitted for verification | verification; bulanan | R2 | ≥80% | Verifier lead | needs-rework/rejected dilaporkan |
| SM-27 | VILR = formula §2 | action/impact; triwulan | R2/R3 | ≥70% pada R3 | Sponsor + LPMPP | completeness review dan denominator minimum |
| SM-28 | Impact Effectiveness = action dengan conclusion effective ÷ action yang impact-nya telah due | impact evaluation; semester | setelah cycle 2 | baseline dulu; target per indicator | Unit owner/Pimpinan | ineffective tidak dianggap kegagalan sistem jika evidence valid |
| SM-29 | Communication-back Rate = eligible campaign dengan summary tindak lanjut dikomunikasikan ÷ eligible campaign | release/action; campaign | R2 | 100% | LPMPP/Humas | tanpa raw response atau janji berlebihan |

### F. Operations dan sustainable adoption

| ID | KPI dan formula | Source/cadence | Baseline | Target awal | Owner | Guardrail |
|---|---|---|---|---|---|---|
| SM-30 | Availability = successful synthetic checks ÷ total checks, excluding approved maintenance | monitoring; bulanan | R1 | ≥99,5% `[P]` | TIK | definisi maintenance dipin |
| SM-31 | P95 Respondent Page/API = percentile response time pada beban nominal | APM/load test; release | R1 | ≤2 detik `[P]` | Technical owner | error rate <1% juga wajib |
| SM-32 | Restore Drill Success = restore sampai integrity/application check lulus ÷ drill | recovery drill; triwulan | R1 | 100%; RPO≤15m/RTO≤4h `[P]` | TIK operations | backup job success tanpa restore bukan bukti |
| SM-33 | Admin Critical Task Success = user menyelesaikan create→publish→report→finding tanpa blocker ÷ task | usability/UAT; release | R1 | ≥90% | Product Owner | waktu dan workaround dicatat |
| SM-34 | Shadow Process Reduction = process step/spreadsheet manual yang dihentikan ÷ baseline step | process review; release | R0 | ≥30% pada R3 `[P]` | LPMPP | kontrol manual yang masih perlu tidak dihitung waste |
| SM-35 | Monthly Active Operational Adoption = unit approved yang menjalankan task inti ÷ unit onboarded | usage aggregate; bulanan | R2 | ≥80% pada active period `[P]` | Product Owner | login/view saja bukan aktivitas bermakna |

## 4. Metric hierarchy

| Level | Pertanyaan | Metric contoh |
|---|---|---|
| North Star | apakah feedback loop prioritas diverifikasi dan akan diukur dampaknya? | SM-27 VILR |
| Outcome | apakah tindakan dimiliki, tepat waktu, diverifikasi, berdampak? | SM-24–29 |
| Product value | apakah user menyelesaikan pekerjaan lebih konsisten/cepat? | SM-18, SM-33–35 |
| Data/method trust | apakah instrument, data, dan analysis dapat dipertanggungjawabkan? | SM-01–12 |
| Guardrail | apakah privacy/security/accessibility/operations tetap aman? | SM-16, SM-19–23, SM-30–32 |

## 5. Anti-metrics

Jangan memakai sebagai success metric tunggal:

- jumlah survey/campaign dibuat;
- jumlah pertanyaan;
- jumlah email/reminder;
- response rate tanpa denominator/coverage/nonresponse;
- mean satisfaction tanpa distribution/limitation;
- jumlah action “completed” tanpa verification/impact;
- jumlah AI summary atau sentiment label;
- jumlah dashboard view tanpa keputusan/tindakan.

## 6. Measurement readiness checklist

- [ ] definisi numerator/denominator/disposition disahkan;
- [ ] event/source dapat direkonsiliasi tanpa mengumpulkan identifier berlebih;
- [ ] owner dan cadence ditetapkan;
- [ ] baseline pilot tersedia;
- [ ] target serta window disetujui;
- [ ] segmentasi lolos minimum cell/privacy rule;
- [ ] guardrail dan gaming risk didokumentasikan;
- [ ] metric version dipin dan perubahan tidak diterapkan retroaktif;
- [ ] dashboard dapat membedakan zero/no data/not calculated/error/suppressed;
- [ ] review metric menghasilkan keputusan lanjut/revise/retire.

## 7. Success decision per release

| Release | Metric gate minimum |
|---|---|
| R1 Alpha | SM-01–06, SM-12, SM-16–22, SM-31–33 lulus pada synthetic/UAT |
| R2 Pilot | baseline SM-07–18 dan SM-24–29 tersedia; no critical incident; satu action mencapai verification |
| R3 MVP | VILR dapat dihitung atau status per campaign transparan; target approved; operasi/adopsi sustainable |

Tidak tercapainya target memicu diagnosis—bukan manipulasi denominator atau otomatis menambah fitur.
