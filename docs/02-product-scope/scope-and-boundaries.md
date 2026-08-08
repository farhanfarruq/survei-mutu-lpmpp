# Scope and Boundaries

Versi: **1.0 — 2026-08-07**  
Status: **baseline Phase 02; menggunakan fakta/rekomendasi Phase 01 dan memisahkan asumsi**

## 1. Product boundary

SIMUTU PT mengelola lifecycle:

`purpose & population → instrument version → review/approval → campaign → response → scoring/quality → aggregate report → finding → action → evidence → verification → impact`

Sistem berada **di dalam** program mutu perguruan tinggi tetapi tidak menggantikan rapat keputusan, SPMI, AMI, akreditasi, SIAKAD, LMS, sistem SDM, pengaduan, atau document management institusi.

## 2. Organizational and data scope

### Hierarchy konseptual

| Level | Contoh | Fungsi scope | Belum dikonfirmasi |
|---|---|---|---|
| Institution | perguruan tinggi | policy, taxonomy, institution report | bentuk/nama resmi dan multi-campus |
| Organizational unit | fakultas, sekolah, biro, lembaga | owner, campaign, report hierarchy | hierarchy dan inheritance |
| Academic unit | UPPS/program studi/jenjang | population/segment/action owner | relasi UPPS–prodi dan LAM |
| Service unit | akademik, perpustakaan, TI, kemahasiswaan | objek layanan dan PIC | daftar unit/layanan resmi |
| Campaign | survei periode tertentu | snapshot privacy, population, instrument | cadence dan owner |
| Assignment | review, analysis, action, verification | akses time-bound pada object | RACI dan delegation |

Scope organisasi membatasi **siapa dapat melihat/mengubah object**. Privacy dan minimum-cell rule tetap berlaku setelah hierarchy filter; user hierarchy tinggi tidak otomatis mendapat raw response.

### Actor–data-scope baseline

| Actor | Create/execute scope | Read scope | Explicitly excluded |
|---|---|---|---|
| Responden | draft/submit pada invitation/campaign sendiri | notice dan draft sendiri sebelum submit | result, participant lain, perubahan submitted response |
| Admin LPMPP | template/campaign/population/finding pada unit assigned | metadata, aggregate, operational status unit | secret, policy approval, raw response default, self-approval |
| Reviewer/metodolog | rating/review/approval assignment | version/evidence/report assigned | edit instrument, population identity, response raw |
| Analyst | scoring/analysis campaign assigned | de-identified/aggregate; raw hanya exception | identity-contact participant, release sendiri |
| Pimpinan/unit owner | keputusan/follow-up pada hierarchy | released aggregate dan action status | raw response/comment dan cell tersuppress |
| PIC | action/evidence assigned | source finding minimum dan action sendiri | action unit lain, verification sendiri |
| Verifikator | verification assigned | finding/target/evidence terkait | edit evidence PIC, self-owned action |
| Super Admin/TIK | account, role, unit, config, operation | system metadata/audit sesuai purpose | raw response otomatis, approval kebijakan sendiri |
| Privacy/hukum/auditor | policy/case/assurance sesuai assignment | field minimum untuk case/audit | penggunaan di luar purpose atau permanent broad access |

## 3. In scope

### MVP core

1. role dan organizational data scope dasar;
2. taxonomy family dan template/instrument version;
3. review/approval instrumen dengan audit minimum;
4. campaign, period, population import, eligibility, dan secure invitation;
5. privacy notice serta mode campaign yang disahkan;
6. respondent flow responsive/accessible, branching, autosave, review, submit;
7. undangan/reminder email dasar dan monitoring delivery/participation agregat;
8. scoring untuk metode pilot yang dipilih, denominator, missing/N/A, quality flag;
9. dashboard/report agregat dengan minimum-cell suppression;
10. export report approved, bukan raw export rutin;
11. finding, action plan, PIC, due date, evidence, verification, dan impact plan;
12. audit event pada login admin, approval, publish, analysis, export, permission, dan closure;
13. backup/recovery serta operational monitoring minimum untuk pilot.

### Scope metodologi

- Produk dapat mendukung beberapa metode, tetapi MVP hanya mengaktifkan metode yang dipilih untuk pilot dan telah mempunyai scoring rule approved.
- SKM hanya dilabelkan untuk unit/konteks yang telah dikonfirmasi sesuai pedoman.
- Satisfaction/perception tidak dipresentasikan sebagai learning outcome, compliance, atau mutu menyeluruh tanpa triangulasi.

## 4. Out of scope

| Out of scope | Alasan |
|---|---|
| Sistem SPMI/AMI end-to-end | domain lebih luas daripada survey feedback; mencegah scope explosion |
| Sistem akreditasi atau auto-submit BAN-PT/LAM | instrumen/kewenangan berubah dan membutuhkan product domain terpisah |
| Generic form builder untuk semua kebutuhan kampus | mengurangi fokus governance survei mutu |
| Kanal pengaduan/whistleblowing/case management | memerlukan identitas, SLA, perlindungan, dan response workflow berbeda |
| Penilaian individual otomatis dosen/pegawai/mahasiswa | risiko fairness, purpose, dan privacy; bukan outcome produk |
| Keputusan individual berbasis AI | human accountability dan evidence gate wajib |
| AI summarization/sentiment/recommendation pada MVP | governance/provider/evaluasi belum disahkan |
| Mobile native application | responsive web cukup untuk validasi MVP |
| Benchmark lintas institusi/marketplace instrumen | standardisasi, consent, comparability, dan governance belum tersedia |
| Advanced psychometric suite lengkap | analisis khusus dapat dilakukan di alat statistik dengan evidence upload pada tahap awal |
| Real-time raw-response monitoring pimpinan | meningkatkan disclosure dan reactivity bias; aggregate release lebih aman |
| Raw response self-service export | exception workflow diperlukan; bukan kemampuan rutin |
| SMS/WhatsApp/push multi-channel | dependency provider/biaya/consent belum disetujui |
| Public open-data portal | threshold, disclosure, dan release governance belum matang |

## 5. Constraints

| ID | Constraint | Implikasi produk |
|---|---|---|
| C-01 | Permendiktisaintek 39/2025 dan instrumen BAN-PT/LAM dapat berubah | regulatory/source register dan effective version diperlukan |
| C-02 | UU PDP dan kebijakan institusi membatasi collection, linkage, access, export, retention | privacy mode dan data minimization ditetapkan sebelum campaign |
| C-03 | Anonymous dan personal status/history tidak selalu kompatibel | participation tracking dipisah atau produk menyebut confidential |
| C-04 | Metode survei mempunyai konstruk/rumus berbeda | tidak ada universal “quality score” tanpa protocol |
| C-05 | Response rate tinggi tidak membuktikan bebas nonresponse bias | coverage, denominator, limitation tetap dilaporkan |
| C-06 | Unit kecil meningkatkan disclosure dan instability | minimum-cell dan complementary suppression wajib |
| C-07 | Current-state institusi, volume, data source, dan RACI belum diwawancarai lengkap | target/scope diberi status proposed dan release gate |
| C-08 | Runtime tersedia melalui Docker Compose; aplikasi bisnis belum dibangun | desain tidak boleh mengasumsikan feature sudah ada |
| C-09 | MVP harus dapat dioperasikan tim terbatas | automation dipilih hanya jika mengurangi risiko/kerja inti |
| C-10 | Aksesibilitas bukan add-on | respondent happy/error path harus keyboard/screen-reader usable |

## 6. Dependencies

| ID | Dependency | Owner | Dibutuhkan untuk | Fallback pilot | Status |
|---|---|---|---|---|---|
| D-01 | sponsor, Product Owner, dan RACI | pimpinan/LPMPP | semua approval/prioritas | tidak ada untuk data nyata | Open |
| D-02 | organization hierarchy dan unit owner | LPMPP/SDM/TIK | data scope/report/action | mapping manual approved | Open |
| D-03 | population source berkualitas | SIAKAD/SDM/alumni owner | invitation/coverage | CSV bervalidasi | Open |
| D-04 | identity/SSO/MFA | TIK/keamanan | user internal/admin | local admin account untuk pilot terbatas | Open |
| D-05 | email sender/provider dan domain | TIK/Humas | invitation/reminder | Mailpit hanya development; manual pilot link approved | Open |
| D-06 | kebijakan privacy, retention, threshold | PDP/hukum/data owner | campaign/report/export | tidak ada; campaign data nyata diblokir | Open |
| D-07 | instrument/metode pilot approved | metodolog/LPMPP | template/scoring | gunakan draft hanya pada data sintetis | Open |
| D-08 | action owner dan verifikator | unit/LPMPP | closing feedback loop | pilot tidak lulus outcome gate tanpa owner | Open |
| D-09 | operational hosting, backup, recovery, monitoring | TIK | controlled release | environment development tidak dianggap production | Open |
| D-10 | regulatory/APS/LAM inventory | PIC akreditasi | evidence mapping | fitur akreditasi tetap out-of-scope | Open |

## 7. Assumptions

| ID | Assumption | Dampak bila salah | Validasi |
|---|---|---|---|
| A-01 | Satu institusi menjadi tenant awal. | membutuhkan multi-tenant isolation dan governance tambahan | konfirmasi sponsor/TIK |
| A-02 | Pilot memakai survei mahasiswa atas layanan akademik. | template, population, KPI, dan owner berubah | konfirmasi LPMPP/unit |
| A-03 | Responsive web cukup bagi seluruh actor MVP. | mobile native/offline mungkin diperlukan | usability/context interview |
| A-04 | Email dan secure link cukup untuk respondent pilot. | channel/delivery flow berubah | population preference/provider test |
| A-05 | LPMPP menjadi Product Owner operasional. | backlog/approval harus dialihkan | keputusan pimpinan |
| A-06 | Reviewer, PIC, dan verifikator tersedia sebagai peran terpisah. | workflow SoD perlu alternatif approved | RACI workshop |
| A-07 | Basic CSV import cukup sebelum integrasi API. | effort manual/quality dapat tidak layak | sample frame rehearsal |
| A-08 | Detached anonymous-content mode dapat dibangun untuk pilot. | notice dan data model berubah ke confidential | privacy architecture review |
| A-09 | Baseline/target KPI dapat diukur dari pilot. | success review tertunda | instrumentation/measurement rehearsal |
| A-10 | Bahasa Indonesia adalah bahasa MVP; terminology institusi dapat disepakati. | multilingual/content governance bertambah | respondent/stakeholder review |

## 8. Boundary rules

- Hanya data yang diperlukan untuk purpose campaign yang masuk platform.
- Participant identity/contact tidak masuk response content store.
- User dengan hierarchy luas tetap menerima aggregate tersuppress, bukan bypass privacy.
- Action plan menautkan finding/result, bukan raw response respondent.
- Export mengikuti permission, scope, classification, threshold, approval, dan expiry yang sama dengan layar.
- Integrasi eksternal dianggap tidak tepercaya sampai authenticated, authorized, validated, rate-limited, dan audited.

## 9. Scope change control

Usulan scope baru harus menyebut problem/outcome, actor, data class, dependency, risiko, KPI, effort, serta item yang dikeluarkan sebagai trade-off. Fitur yang hanya “menarik secara teknis” tidak masuk roadmap tanpa outcome terukur dan owner.
