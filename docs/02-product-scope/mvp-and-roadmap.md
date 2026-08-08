# MVP, Backlog, and Release Roadmap

Versi: **1.0 — 2026-08-07**  
Prioritization: **MoSCoW berbasis outcome, risk, dan dependency**

## 1. Definisi MVP

MVP adalah kemampuan minimum untuk menjalankan **satu campaign survei layanan akademik mahasiswa secara terkendali** dari instrument approved hingga satu action plan diverifikasi. MVP bukan seluruh platform akhir dan tidak dinilai berhasil hanya karena formulir dapat diisi.

### Kriteria prioritas

- **Must:** tanpa capability ini vertical slice tidak selesai, hasil tidak dapat dipercaya, atau data nyata tidak aman.
- **Should:** nilai tinggi tetapi ada workaround manual yang aman untuk pilot terbatas.
- **Could:** berguna setelah core terbukti; tidak boleh mengganggu Must.
- **Won't now:** out of MVP secara sadar; hanya masuk ulang melalui outcome, owner, data/risk, dan trade-off yang disetujui.

## 2. MoSCoW backlog

### Must have — MVP

| ID | Capability | Modul | Alasan prioritas | Exit evidence |
|---|---|---|---|---|
| MVP-01 | admin authentication, MFA, role, dan unit/campaign scope | M01 | data dan aksi admin tidak aman tanpa kontrol ini | access-control test dan role review |
| MVP-02 | privacy/classification/threshold policy baseline | M10 | campaign nyata tidak boleh dimulai tanpa rule data/release | approval policy dan preflight fixture |
| MVP-03 | template dan immutable instrument version | M02 | hasil harus terkait naskah/skala/scoring tertentu | version/hash test |
| MVP-04 | item, scale, N/A, required, dan branching dasar | M02 | diperlukan untuk kuesioner relevan dan scoring | render fixture dan branching test |
| MVP-05 | review, return, dan approval instrument noncreator | M03 | mencegah self-approval dan instrumen belum layak | review/approval record |
| MVP-06 | campaign period/owner/snapshot/preflight | M04 | mengikat instrument, population, privacy, scoring, dan owner | preflight tanpa blocker |
| MVP-07 | validated CSV population, eligibility, duplicate/disposition | M04 | sumber API belum perlu, tetapi frame harus dapat diaudit | import reconciliation report |
| MVP-08 | secure invitation token terpisah dari response content | M04/M06 | menjaga akses dan privacy mode | token lifecycle/linkage test |
| MVP-09 | email invitation dan reminder terbatas | M06 | dibutuhkan untuk menjangkau pilot population | delivery/retry/dedup evidence |
| MVP-10 | notice, consent, responsive accessible survey | M05 | trust, voluntariness, dan access setara | usability/accessibility test |
| MVP-11 | autosave, review, validation, exactly-once submit, receipt | M05 | mencegah response hilang/ganda | interruption/replay E2E test |
| MVP-12 | satu scoring method approved untuk pilot | M07 | menghindari semua metode aktif tanpa kebutuhan | golden numeric vectors pass |
| MVP-13 | denominator, missing/N/A, distribution, coverage/response metric | M07 | skor tanpa context menyesatkan | analysis output review |
| MVP-14 | immutable analysis run dan lineage | M07 | hasil harus reproducible | rerun/input-hash evidence |
| MVP-15 | aggregate dashboard/report dengan `n`, period, version, limitation | M08 | pimpinan membutuhkan keputusan, bukan raw table | report acceptance review |
| MVP-16 | minimum-cell dan complementary suppression lintas layar/export | M08/M10 | privacy control tidak boleh berbeda antar channel | golden parity test |
| MVP-17 | approved PDF dan tabular aggregate export | M08 | kebutuhan rapat/arsip; raw export tidak diperlukan | scoped/suppressed file inspection |
| MVP-18 | report release review nonrequester | M08 | interpretation/privacy perlu gate sebelum konsumsi | approval trail |
| MVP-19 | finding terhubung result, severity, owner, due date | M09 | feedback loop dimulai dari evidence yang jelas | finding completeness check |
| MVP-20 | action plan, PIC, target, milestone, evidence | M09 | hasil harus menjadi tindakan operasional | accepted action plan |
| MVP-21 | independent verification dan status needs-rework | M09 | uploaded tidak sama dengan verified | separation-of-duties test |
| MVP-22 | impact plan atau approved waiver sebelum closure | M09 | verified belum otomatis effective | closure-gate evidence |
| MVP-23 | audit event untuk aksi kritis | M10 | approval/change/export harus dapat direkonstruksi | audit coverage test |
| MVP-24 | backup, restore rehearsal, monitoring, dan runbook minimum | M10 | pilot data nyata tidak boleh bergantung pada recovery asumsi | restore/alert drill |
| MVP-25 | communication-back summary kepada stakeholder | M08/M09 | loop belum lengkap tanpa memberi tahu tindak lanjut | approved communication artifact |

### Should have — pilot/controlled release

| ID | Capability | Alasan dapat ditunda/di-workaround |
|---|---|---|
| SH-01 | compare instrument version | diff manual reviewer masih aman untuk satu template |
| SH-02 | structured validation evidence/CVI workflow | attachment + reviewer record cukup untuk pilot |
| SH-03 | API sync population | validated CSV cukup pada volume awal |
| SH-04 | recurring campaign cloning | manual new campaign menghindari automation prematur |
| SH-05 | coverage/nonresponse comparison by approved frame attribute | dapat dianalisis offline pada extract agregat approved |
| SH-06 | scheduled report | on-demand approved export cukup |
| SH-07 | root-cause analysis workflow | dapat didokumentasikan pada action plan/evidence |
| SH-08 | retention automation dan legal-hold UI lengkap | approved operational procedure/job cukup sementara |
| SH-09 | delegated/time-bound access UI | Super Admin dapat mengelola grant secara terkontrol pada pilot |
| SH-10 | external respondent support beyond secure email | pilot mahasiswa internal tidak membutuhkannya |

### Could have — post-validation

| ID | Capability | Trigger masuk prioritas |
|---|---|---|
| CO-01 | multilingual instrument | target population/bahasa teridentifikasi dan translation governance tersedia |
| CO-02 | additional notification provider/channel | email delivery/coverage terbukti tidak memadai |
| CO-03 | public summary portal | release/privacy governance matang dan kebutuhan publik terkonfirmasi |
| CO-04 | advanced visual comparison/trend | sedikitnya dua periode comparable tersedia |
| CO-05 | integration ke SPMI/document system | duplicate entry terukur dan system owner menyediakan interface |
| CO-06 | specialized statistical workspace | analyst menunjukkan kebutuhan berulang yang tidak aman/efisien secara offline |
| CO-07 | survey family library tambahan | owner, instrument, method, population, dan action workflow family siap |

### Won't have now

| ID | Capability | Alasan tidak masuk MVP |
|---|---|---|
| WN-01 | AI sentiment, summary, atau recommendation | governance/provider/evaluation belum disetujui; bukan kebutuhan vertical slice |
| WN-02 | keputusan/rating individual otomatis | risiko fairness/privacy dan di luar tujuan |
| WN-03 | generic all-purpose form builder | mengaburkan governance survei mutu |
| WN-04 | full SPMI/AMI/accreditation management | product domain berbeda dan jauh lebih luas |
| WN-05 | raw response self-service export | disclosure risk; exception terkontrol saja |
| WN-06 | native mobile/offline app | responsive web harus divalidasi dahulu |
| WN-07 | cross-institution benchmark | comparability/data-sharing governance belum ada |
| WN-08 | advanced psychometric suite lengkap | alat statistik yang ada cukup sebelum demand berulang terbukti |
| WN-09 | public open-data API | privacy/release/value belum terkonfirmasi |
| WN-10 | SMS/WhatsApp/push serentak | provider, biaya, consent, dan necessity belum dibuktikan |

## 3. Release roadmap berbasis gate

Tanggal kalender baru ditetapkan setelah owner, kapasitas tim, dan dependency dikonfirmasi.

| Release | Tujuan | Scope | Entry gate | Exit gate |
|---|---|---|---|---|
| R0 — Scope confirmation | membuat MVP buildable | keputusan PD-02–PD-09, pilot family/unit, baseline metric, RACI, privacy | Phase 01/02 reviewed | owner menandatangani scope, data, target, risk acceptance |
| R1 — Internal alpha | membuktikan vertical slice dengan data sintetis | MVP-01–25 pada satu instrument/campaign | R0 complete | critical test pass, no real PII, usability/accessibility rehearsal, restore drill |
| R2 — Controlled pilot | menguji usefulness/trust pada satu unit/population terbatas | satu campaign nyata, email, aggregate report, satu finding/action | privacy/security/ethics release gate; support/runbook siap | metric pilot tersedia, no unresolved critical incident, action verified/impact plan dibuat |
| R3 — Institutional MVP | operasional terbatas yang repeatable | beberapa unit yang approved, standardized onboarding, support/SLA | pilot review menerima benefit dan capacity | dua cycle selesai, KPI/guardrail acceptable, ownership/retention sustainable |
| R4 — Post-MVP | mengurangi workaround bernilai tinggi | Should backlog yang lolos evidence-based prioritization | R3 outcome review | capability memenuhi target dan tidak melemahkan control |
| R5 — Strategic horizon | perluasan family/integration/advanced analysis | Could/long-term backlog terpilih | demand, governance, data, budget matang | ditetapkan per initiative; bukan komitmen Phase 02 |

## 4. Post-MVP backlog themes

1. integration population/SSO dan data quality automation;
2. library survey family serta validation evidence lebih terstruktur;
3. longitudinal comparison dengan compatibility/coverage guard;
4. scheduled reporting dan communication-back portal terbatas;
5. root-cause/resource/portfolio management untuk PPEPP;
6. subject-right, legal hold, retention, dan audit automation;
7. additional channels berdasarkan delivery/coverage evidence;
8. additional approved methods dan analysis workflow.

## 5. Long-term options—not commitments

- governed AI-assisted qualitative coding setelah DPIA, contract, benchmark, threshold, dan human review;
- privacy-preserving benchmark lintas institusi;
- advanced psychometrics dan longitudinal measurement invariance;
- multilingual/cross-cultural instrument governance;
- integration dua arah dengan SPMI/AMI/evidence repository;
- native/offline experience hanya jika connectivity/access study membuktikan kebutuhan.

## 6. MVP success/failure decision

MVP dilanjutkan bila controlled pilot menunjukkan:

- core task dapat diselesaikan actor target;
- analysis/report reproducible dan dipercaya reviewer;
- privacy/access/suppression control tidak gagal;
- sedikitnya finding prioritas mencapai verified action dan mempunyai impact plan;
- beban operasi dapat ditanggung owner;
- stakeholder menilai manfaat melebihi workflow manual yang diganti.

MVP dihentikan/didesain ulang bila risk critical tidak dapat dimitigasi, owner/data source tidak tersedia, pengguna tetap memerlukan shadow process utama, atau angka dashboard tidak mengubah keputusan/tindak lanjut.

## 7. Catatan urutan phase

Phase 04 telah didokumentasikan lebih dahulu atas instruksi terpisah. Label `MUST/SHOULD` di Phase 04 perlu direkonsiliasi terhadap MoSCoW Phase 02 pada perubahan yang secara eksplisit mengizinkan pembaruan Phase 04; file Phase 04 tidak diubah dalam pekerjaan ini.
