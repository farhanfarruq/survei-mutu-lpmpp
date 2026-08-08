# Product Brief — Platform Survei dan Umpan Balik Mutu Perguruan Tinggi

Versi: **1.0 — 2026-08-07**  
Status: **baseline Phase 02; keputusan bertanda konfirmasi belum disahkan**  
Dasar: [research-report.md](../01-discovery/research-report.md), [regulatory-basis.md](../01-discovery/regulatory-basis.md), dan [comparable-systems.md](../01-discovery/comparable-systems.md)

## 1. Product vision

> Menjadi platform institusional yang membantu perguruan tinggi mengelola umpan balik stakeholder secara sahih, aman, berversi, dan dapat ditindaklanjuti—dari penyusunan instrumen sampai bukti perbaikan dan evaluasi dampak dalam siklus PPEPP.

Platform ini adalah **sumber bukti dan workflow pendukung**. Platform bukan SPMI itu sendiri, bukan sistem akreditasi BAN-PT/LAM, bukan pengganti keputusan metodolog/pimpinan, dan bukan kanal penanganan kasus individual.

## 2. Problem statement

Perguruan tinggi membutuhkan bukti persepsi stakeholder untuk evaluasi, pengendalian, dan peningkatan mutu. Praktik yang tidak terkelola menimbulkan lima masalah:

1. instrumen, skala, rumus, populasi, dan versi tidak selalu dapat ditelusuri;
2. metode kepuasan/kualitas/prioritas mudah dicampur sehingga skor tidak mempunyai arti yang konsisten;
3. undangan, denominator, missing, response rate, coverage, dan nonresponse tidak terdokumentasi memadai;
4. klaim anonim dapat bertentangan dengan tracking, atribut kelompok, open text, atau akses administrator;
5. hasil berhenti pada dashboard/laporan tanpa finding, PIC, target, evidence, verifikasi, komunikasi balik, dan pengukuran dampak.

Akibatnya, pimpinan berisiko mengambil keputusan dari angka yang tidak comparable, responden kehilangan kepercayaan, LPMPP sulit membuktikan feedback loop, dan institusi menghadapi risiko privasi serta audit.

## 3. Target pengguna dan jobs-to-be-done

| Aktor | Job utama | Nilai yang diharapkan |
|---|---|---|
| Responden internal/eksternal | memberi umpan balik yang relevan dan aman | pengalaman sederhana, accessible, privacy notice jujur |
| Admin LPMPP | mengelola instrumen, campaign, populasi, monitoring, dan finding | proses standar, berversi, tidak bergantung spreadsheet tersebar |
| Reviewer/metodolog | memeriksa isi, metode, scoring, dan laporan | evidence review serta approval dapat ditelusuri |
| Analyst | menghasilkan scoring/analisis reproducible | snapshot, denominator, quality flag, dan limitation jelas |
| Pimpinan/unit owner | memahami prioritas dan status perbaikan | agregat yang aman serta keputusan yang dapat ditindaklanjuti |
| PIC tindakan | melaksanakan action plan | target, tenggat, milestone, dan evidence jelas |
| Verifikator/LPMPP | memastikan tindakan benar-benar dilaksanakan | separation of duties dan verification record |
| Super Admin/TIK | menjaga akses, konfigurasi, dan operasi | kontrol scoped, audit, backup, dan recovery |
| Privacy/hukum/auditor | memastikan penggunaan data sesuai tujuan | classification, retention, release control, dan audit evidence |

## 4. Product objectives, benefit, outcome, dan KPI ringkas

| Objective | Manfaat | Outcome yang dicari | KPI utama |
|---|---|---|---|
| O-01 Standardisasi siklus survei | instrumen/campaign konsisten | campaign memakai versi approved dan snapshot lengkap | `% campaign published yang lolos preflight` |
| O-02 Meningkatkan keandalan hasil | keputusan tidak memakai angka tanpa konteks | scoring reproducible, denominator/quality flag terlihat | `% analysis run lulus golden test dan lineage lengkap` |
| O-03 Melindungi responden | kepercayaan dan kepatuhan meningkat | privacy mode jujur, akses scoped, cell kecil tersuppress | jumlah incident/privacy control failure |
| O-04 Menutup feedback loop | hasil berujung perbaikan | finding prioritas mempunyai action, PIC, evidence, verification | Verified Improvement Loop Rate |
| O-05 Meningkatkan akuntabilitas | audit dan rapat tinjauan mendapat bukti | approval, export, perubahan, dan tindak lanjut terlacak | `% aksi kritis dengan audit event lengkap` |
| O-06 Membuat adopsi berkelanjutan | unit dapat menjalankan program tanpa shadow process | campaign selesai tepat waktu dan user dapat menyelesaikan tugas | task success, campaign completion, user adoption |

Definisi, baseline, target, owner, cadence, dan guardrail KPI tersedia di [success-metrics.md](success-metrics.md). Target belum dianggap komitmen sebelum baseline pilot dan persetujuan owner.

## 5. Stakeholder map

| Kelompok | Influence | Interest | Engagement | Keputusan/kontribusi |
|---|:---:|:---:|---|---|
| Sponsor/pimpinan perguruan tinggi | Tinggi | Tinggi | Manage closely | mandat, risk appetite, outcome, pendanaan, release |
| LPMPP/penjaminan mutu | Tinggi | Tinggi | Manage closely | product owner, metodologi, workflow PPEPP, governance |
| TIK/keamanan/data platform | Tinggi | Tinggi | Manage closely | identity, integration, operasi, backup, security |
| Fungsi PDP/hukum/etik | Tinggi | Tinggi | Manage closely | lawful basis, privacy mode, retention, AI/export exception |
| Fakultas/UPPS/prodi/unit layanan | Tinggi | Tinggi | Manage closely | scope, population, action owner, evidence |
| Reviewer/metodolog/auditor mutu | Sedang | Tinggi | Involve | validasi, approval, verification, assurance |
| Pimpinan unit | Tinggi | Sedang | Keep satisfied | penggunaan hasil, prioritas, resource tindakan |
| Responden mahasiswa/dosen/tendik/alumni/mitra/pengguna lulusan | Rendah individual, tinggi kolektif | Tinggi | Consult/inform | feedback, usability, trust, communication-back |
| PIC dan verifikator tindak lanjut | Sedang | Tinggi | Involve | action plan, evidence, verification, impact |
| PIC akreditasi/BAN-PT/LAM liaison | Sedang | Sedang | Consult | evidence mapping berversi; bukan owner semua survei |
| Regulator/BAN-PT/LAM | Tinggi eksternal | Tidak langsung | Monitor obligations | constraint regulasi/instrumen; bukan user operasional default |
| Publik | Rendah | Rendah–sedang | Inform selectively | hanya hasil yang approved dan aman |

## 6. Product principles

1. **Purpose before questionnaire:** setiap campaign dimulai dari keputusan dan populasi, bukan dari daftar pertanyaan.
2. **Version everything that changes meaning:** instrument, scoring, privacy, threshold, dan analysis plan dipin.
3. **Method is not a feature toggle:** metode hanya tersedia bila konstruk dan data memenuhi prasyarat.
4. **Anonymous must be technically true:** bila linkage masih mungkin, gunakan istilah confidential.
5. **Aggregate by default:** raw response adalah akses terbatas, bukan default dashboard.
6. **No score without denominator and limitation:** `n`, missing, coverage, dan comparability menyertai hasil.
7. **Uploaded is not verified; verified is not effective:** PPEPP membedakan pelaksanaan, verifikasi, dan dampak.
8. **Human accountability remains:** sistem mendukung keputusan; AI tidak mengambil keputusan individual.

## 7. Lima alternatif nama sistem

| Nama | Kepanjangan/arti | Kelebihan | Catatan |
|---|---|---|---|
| **SIMUTU PT** | Sistem Informasi Survei Mutu Perguruan Tinggi | formal, langsung menjelaskan konteks | direkomendasikan sebagai working title; perlu cek nama/domain/merek |
| **SIKLUS MUTU** | Sistem Informasi Kepuasan, Layanan, Umpan Balik, dan Survei Mutu | menekankan feedback loop, mudah diingat | kepanjangan cukup panjang |
| **SUARA MUTU** | Sistem Umpan Balik dan Analitik Respons untuk Mutu | human-centered dan komunikatif | perlu penjelasan agar tidak dianggap kanal pengaduan |
| **PANTAU MUTU** | Platform Analitik dan Tindak Lanjut Umpan Balik Mutu | menekankan monitoring dan action | jangan memberi kesan pengawasan individu |
| **SIRAMU** | Sistem Informasi Respons dan Mutu | singkat dan mudah diucapkan | makna perlu diperkuat dalam identitas visual |

Working title Phase 02: **SIMUTU PT**. Pemilihan final memerlukan validasi stakeholder, pemeriksaan domain, merek, singkatan internal, dan potensi makna lintas bahasa.

## 8. Daftar istilah formal

| Istilah | Definisi formal dalam produk |
|---|---|
| Survey family | klasifikasi survei berdasarkan objek keputusan dan stakeholder, bukan metode analisis |
| Template | blueprint reusable berisi purpose, family, population type, hierarchy item, method, dan governance default |
| Instrument version | snapshot berversi dari category, indicator, item, scale, branching, dan scoring rule |
| Campaign | pelaksanaan satu instrument version pada population, period, owner, dan privacy policy tertentu |
| Population frame | daftar/unit sumber yang mendefinisikan siapa yang mungkin eligible |
| Eligibility | rule yang menentukan siapa dapat menjadi responden campaign |
| Participant | anggota population frame yang eligible/diundang; tidak identik dengan response content |
| Response | kumpulan jawaban draft/submitted; mengikuti privacy mode campaign |
| Privacy mode | strict anonymous, anonymous-content detached, confidential pseudonymous, atau identifiable |
| Disposition | status operasional complete, partial, refusal, ineligible, undeliverable, duplicate, atau unknown eligibility |
| Scoring snapshot | formula, precision, rounding, missing, threshold, dan interpretation yang dipin pada campaign |
| Analysis run | eksekusi analisis dengan input hash, method/version, parameter, pelaku, dan output lineage |
| Reporting threshold | minimum dan control risiko sebelum cell boleh dirilis/ditafsirkan |
| Finding | isu/prioritas yang diturunkan dari evidence hasil dan memerlukan keputusan |
| Action plan | tindakan dengan owner, target, milestone, resource, due date, dan acceptance evidence |
| Verification | penilaian independen bahwa evidence memenuhi target tindakan |
| Impact evaluation | perbandingan baseline, target, dan observed result untuk menilai efektivitas |
| Closing the feedback loop | rangkaian hasil–tindakan–bukti–verifikasi–komunikasi–pengukuran dampak |
| PPEPP | Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan standar pendidikan tinggi |
| LPMPP | unit/fungsi penjaminan mutu institusi; nama dan mandat formal harus dikonfirmasi |

## 9. Keputusan yang harus dikonfirmasi pemilik sistem

| ID | Keputusan | Pilihan/pertanyaan | Dampak bila tertunda | Owner yang disarankan |
|---|---|---|---|---|
| PD-01 | Nama resmi produk | lima alternatif §7 atau nama institusi | branding/domain/dokumen tertunda | Sponsor + LPMPP + Humas |
| PD-02 | Product owner dan sponsor | siapa memiliki roadmap serta outcome | prioritas/approval tidak jelas | Pimpinan |
| PD-03 | Struktur organisasi/data scope | institusi–fakultas–UPPS–prodi–unit layanan | akses/report salah scope | LPMPP + TIK |
| PD-04 | Keluarga survei pilot | layanan akademik mahasiswa atau family lain | desain pilot/metric berubah | LPMPP + unit owner |
| PD-05 | Privacy mode pilot | detached anonymous atau confidential | arsitektur, notice, rights berubah | Fungsi PDP + LPMPP |
| PD-06 | Population source dan identity | SIAKAD/HR/alumni/manual, SSO, external link | import/dedup/delivery berubah | Data owner + TIK |
| PD-07 | Role dan separation of duties | reviewer, analyst, PIC, verifikator, approver | workflow/permission tidak final | LPMPP + pimpinan |
| PD-08 | Threshold, release, dan retention | minimum cell, public output, raw access, deletion | risiko privacy/storage/audit | PDP/hukum + data owner |
| PD-09 | Target layanan dan KPI | target pilot/production serta baseline | success tidak dapat dinilai | Sponsor + LPMPP + TIK |
| PD-10 | Channel notifikasi | email/in-app/provider dan reminder policy | coverage/biaya/consent | LPMPP + TIK + Humas |
| PD-11 | Scope SKM/akreditasi | unit layanan publik dan mapping APS/LAM | label/regulatory evidence salah | Hukum + PIC akreditasi |
| PD-12 | AI | tetap out sampai governance atau dijadwalkan post-MVP | risiko/provider/evaluasi | Pimpinan + PDP + keamanan |

Keputusan terbuka tidak menghalangi dokumentasi scope, tetapi **PD-02–PD-09 adalah release gate sebelum build/pilot yang berdampak data nyata**.
