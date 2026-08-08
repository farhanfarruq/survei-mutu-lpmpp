# Glosarium

| Istilah | Definisi |
|---|---|
| LPMPP | Lembaga Penjaminan Mutu dan Pengembangan Pembelajaran. |
| Phase 00 | Tahap onboarding dan verifikasi kesiapan repository, tanpa implementasi fitur bisnis. |
| Phase 01 | Tahap discovery dan riset untuk memverifikasi konteks, regulasi, metode, pembanding, risiko, dan pertanyaan stakeholder tanpa desain/implementasi fitur. |
| Baseline | Struktur, versi, service, dan quality gate yang ditetapkan master prompt. |
| BLOCKED | Kondisi ketika pekerjaan lanjutan tidak dapat dilakukan sebelum blocker eksternal/struktural diselesaikan. |
| Development database | Database operasional lokal baseline, bernama `lpmpp_survey`. |
| Test database | Database pengujian terpisah, bernama `lpmpp_survey_test`. |
| Horizon | Dashboard dan worker queue Laravel berbasis Redis. |
| Scheduler | Proses `php artisan schedule:work` untuk tugas terjadwal Laravel. |
| Mailpit | SMTP dan UI inbox lokal untuk pengujian email. |
| Quality gate | Test, lint, type-check, dan build yang harus lulus sebelum tahap berikutnya. |
| SPMI | Sistem Penjaminan Mutu Internal perguruan tinggi. |
| PPEPP | Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan standar pendidikan tinggi. |
| SPME | Sistem Penjaminan Mutu Eksternal melalui akreditasi. |
| IAPT 4.1 | Instrumen Akreditasi Perguruan Tinggi berdasarkan PerBAN-PT 35/2025, diberlakukan untuk pengajuan baru mulai 1 Juni 2026. |
| APS | Akreditasi Program Studi; instrumennya bergantung kewenangan BAN-PT/LAM, disiplin, jenjang, modus, status, dan periode. |
| Feedback loop | Rangkaian hasil, interpretasi, tindakan, bukti, verifikasi, komunikasi kembali, dan pengukuran dampak. |
| Anonymous | Jawaban tidak dapat secara wajar dihubungkan kembali kepada individu. |
| Confidential/pseudonymous | Linkage identitas dipisahkan dan hanya dapat dibuka secara terbatas; bukan anonim. |
| Small-cell suppression | Menyembunyikan agregat kelompok yang terlalu kecil untuk menekan salah tafsir dan risiko identifikasi ulang. |
| SERVQUAL | Metode kualitas layanan berbasis perbandingan harapan dan persepsi. |
| SERVPERF | Metode kualitas layanan berbasis persepsi kinerja saja. |
| IPA | Importance–Performance Analysis untuk memprioritaskan atribut berdasarkan kepentingan dan kinerja. |
| CSI | Customer Satisfaction Index; label keluarga indeks yang formula spesifiknya harus dinyatakan. |
| SKM/IKM | Survei/Indeks Kepuasan Masyarakat dalam konteks unit penyelenggara pelayanan publik sesuai pedoman yang berlaku. |
| NPS | Net Promoter Score, persentase promoter dikurangi detractor pada pertanyaan rekomendasi 0–10. |
| CSI internal | Indeks 0–100 yang membobot performance ternormalisasi dengan importance; bukan ACSI dan bukan standar hukum. |
| I-CVI | Item Content Validity Index: proporsi ahli yang menilai item relevan pada kategori rating yang ditetapkan. |
| S-CVI/Ave | Rata-rata I-CVI seluruh item sebagai salah satu ringkasan content validity, bukan pengganti telaah kelengkapan. |
| Cronbach's alpha | Estimasi internal consistency dengan asumsi tertentu; tidak membuktikan validitas atau unidimensionalitas. |
| McDonald's omega | Estimasi reliabilitas berbasis model faktor yang sesuai; tetap bukan bukti validitas tunggal. |
| EFA | Exploratory Factor Analysis untuk mengeksplorasi struktur latent ketika model belum pasti. |
| CFA | Confirmatory Factor Analysis untuk menguji model pengukuran yang ditetapkan, idealnya pada data independen. |
| Response rate | Rasio respons menurut disposition dan denominator yang dinyatakan; bukan bukti otomatis bebas nonresponse bias. |
| Nonresponse bias | Bias karena respondent dan nonrespondent berbeda secara sistematis pada ukuran yang diteliti. |
| Reporting threshold | Minimum jumlah observasi dan syarat risiko sebelum cell boleh ditampilkan atau ditafsirkan. |
| Complementary suppression | Menyembunyikan cell tambahan agar nilai cell kecil tidak dapat dihitung dari total dan cell lain. |
| Detached participation tracking | Pelacakan sudah/belum berpartisipasi yang secara teknis dan organisatoris tidak mempunyai join key ke isi jawaban. |
| Functional requirement (FR) | Perilaku/fungsi sistem yang unik, dapat diuji, dan ditelusuri ke tujuan serta acceptance evidence. |
| Non-functional requirement (NFR) | Target kualitas atau constraint terukur seperti latency, availability, security, dan accessibility. |
| Business rule (BR) | Invariant atau kebijakan domain yang membatasi perilaku terlepas dari antarmuka implementasi. |
| Acceptance criteria | Kondisi Given/When/Then yang menentukan kapan story dianggap memenuhi kebutuhan. |
| RBAC | Role-Based Access Control; grant operasi berdasarkan role, tetap dipersempit scope/state/assignment. |
| Data scope | Batas institution, unit hierarchy, campaign, assignment, field class, dan purpose yang boleh diakses. |
| Separation of duties | Pemisahan creator/requester/executor/approver untuk mengurangi konflik dan penyalahgunaan. |
| RPO | Recovery Point Objective, umur maksimum data yang boleh hilang setelah gangguan. |
| RTO | Recovery Time Objective, waktu maksimum pemulihan layanan setelah gangguan. |
| Idempotency | Sifat retry operasi menghasilkan satu efek bisnis logis, bukan duplikasi. |
| Golden dataset | Fixture input-output yang dipin untuk menguji formula, suppression, dan parity lintas channel. |
| Legal hold | Penangguhan deletion untuk kewajiban hukum/audit tanpa memperluas akses baca. |
| Risk appetite | Tingkat dan jenis risiko residual yang bersedia diterima oleh authority yang berwenang. |
| Product vision | Pernyataan keadaan masa depan dan nilai utama yang ingin diwujudkan produk bagi stakeholder. |
| Product outcome | Perubahan perilaku/proses/keputusan yang dihasilkan penggunaan produk, bukan sekadar output fitur. |
| MVP | Vertical slice minimum yang cukup untuk menguji core value dan risiko utama pada pengguna nyata secara terkendali. |
| MoSCoW | Prioritas Must, Should, Could, dan Won't Now berdasarkan outcome, risk, dependency, serta trade-off. |
| Release gate | Syarat bukti/approval yang harus terpenuhi sebelum masuk atau keluar dari release stage. |
| VILR | Verified Improvement Loop Rate: proporsi campaign eligible yang finding prioritasnya telah ditindaklanjuti, diverifikasi, dan dijadwalkan evaluasi dampaknya dalam SLA. |
| System boundary | Garis yang membedakan tanggung jawab sistem dari aktor dan sistem eksternal. |
| Use case | Kontrak interaksi aktor–sistem untuk mencapai tujuan yang bernilai dan teramati. |
| Include | Relasi wajib: use case dasar selalu menjalankan perilaku use case yang disertakan. |
| Extend | Relasi kondisional: perilaku tambahan berjalan hanya ketika guard/extension point terpenuhi. |
| Generalization | Relasi pewarisan tujuan/perilaku dari aktor atau use case umum ke bentuk yang lebih khusus. |
| Precondition | Keadaan yang harus benar sebelum trigger use case dapat diproses. |
| Postcondition | Keadaan yang dijamin setelah use case selesai, termasuk jaminan konsistensi ketika gagal. |
| Alternative flow | Jalur bisnis sah selain main flow yang menghasilkan outcome lain atau outcome sukses yang sama. |
| Failure flow | Jalur penolakan/error yang mencegah outcome dan menetapkan state aman serta recovery. |
| Activity diagram | Model alur kerja, keputusan, paralelisme, dan tanggung jawab proses. |
| Sequence diagram | Model urutan message antaraktor/komponen logis untuk satu skenario interaksi. |
| State machine | Model lifecycle object berdasarkan state, event, guard, transition, dan invariant. |
| Data flow diagram | Model perpindahan data antara external entity, process, dan logical data store. |
| Reconciliation | Pemeriksaan serta penyelarasan hasil asynchronous terhadap sumber kebenaran setelah timeout atau partial failure. |
| C4 model | Model arsitektur bertingkat: system context, container, component, dan bila perlu code. |
| Modular monolith | Satu deployable application dengan boundary modul internal eksplisit dan tanpa overhead distributed services prematur. |
| UUIDv7 | UUID time-ordered yang disimpan pada native PostgreSQL `uuid`; bukan secret atau authorization control. |
| Linkage Vault | Store/credential terisolasi untuk mapping identity–response pada mode confidential yang disetujui; kosong/tidak dipakai pada mode anonymous. |
| Envelope encryption | Data dienkripsi dengan DEK yang dilindungi KEK eksternal di KMS/secret manager. |
| KMS | Key Management Service; pengelola lifecycle, akses, rotasi, dan audit kunci enkripsi. |
| Transactional outbox | Intent side effect yang ditulis dalam transaction yang sama dengan business state agar dapat dikirim/retry secara durable. |
| Aggregate snapshot | Proyeksi metric immutable yang membawa denominator, policy/suppression, checksum, dan release lineage. |
| STRIDE | Kategori ancaman Spoofing, Tampering, Repudiation, Information disclosure, Denial of service, dan Elevation of privilege. |
| SSRF | Server-Side Request Forgery: server dipaksa mengakses endpoint internal/tidak sah melalui input URL/provider. |
| RPO | Recovery Point Objective: batas maksimum kehilangan data berdasarkan titik recovery. |
| RTO | Recovery Time Objective: target waktu pemulihan capability setelah gangguan. |
| Legal hold | Penangguhan disposition karena kewajiban hukum/audit dengan scope, approval, review, dan release tercatat. |
| Crypto-shredding | Membuat ciphertext tidak dapat dipulihkan melalui penghancuran kunci yang terkontrol setelah hold/dependency check. |
| Prompt versioning | Pengelolaan prompt AI sebagai artefak immutable dengan schema, policy, checksum, approval, dan evaluation lineage. |
| AI provider registry | Daftar provider/model/endpoint policy yang disetujui; menggantikan arbitrary Base URL atau dynamic plugin. |
| API Resource | Laravel transformation layer yang mengubah model/domain output menjadi representation field/relationship/meta yang diizinkan. |
| OpenAPI | Machine-readable specification untuk paths, operations, schemas, security, examples, and errors HTTP API. |
| CSRF | Cross-Site Request Forgery; state change dari browser ditolak tanpa token yang terikat pada first-party session. |
| Idempotency-Key | Header yang mengidentifikasi satu logical command agar retry payload yang sama tidak membuat side effect ganda. |
| Optimistic locking | Pencegahan lost update dengan membandingkan version yang dibaca client terhadap version saat write. |
| ETag | HTTP representation identifier yang dipakai sebagai resource version pada kontrak ini. |
| If-Match | Request precondition yang hanya mengizinkan write bila ETag/version masih cocok. |
| Cursor pagination | Pagination dengan opaque stable cursor yang terikat scope/filter/sort, bukan offset yang mudah bergeser. |
| Sparse field selection | Permintaan subset field melalui allowlist yang tetap diinterseksikan dengan permission dan classification. |
| Problem Details | Struktur error HTTP standar dengan type/title/status/detail/instance dan extension code/request/error metadata. |
| Webhook | Delivery event HTTP antarsistem yang asynchronous dan at-least-once; disabled sampai subscription contract disetujui. |
| Dead-letter | Durable operator case untuk event/job yang gagal setelah retry atau diblokir kontrol, bukan sekadar payload queue. |
| Contract test | Pengujian provider/consumer terhadap schema, semantics, security, error, retry, and forbidden data tanpa production credentials. |
| Clickable prototype | Artefak interaktif untuk memvalidasi alur, hierarki informasi, state, dan bahasa sebelum implementasi production. |
| Fixture | Data contoh deterministik yang tidak berasal dari database atau pengguna production. |
| Released aggregate | Snapshot hasil teragregasi yang telah melewati governance/release gate dan tetap tunduk pada scope serta suppression. |
| Masked secret | Representasi yang hanya menunjukkan placeholder/suffix; bukan mekanisme untuk membaca kembali credential asli. |
| Stateful SPA authentication | Autentikasi browser first-party memakai server session cookie dan proteksi CSRF, bukan bearer token yang disimpan oleh JavaScript. |
| Problem Details | Format error HTTP terstruktur `application/problem+json` dengan status, code stabil, detail aman, field pointer, dan request ID. |
| Organizational scope | Batas unit organisasi yang boleh diakses user berdasarkan membership, subtree, atau grant global eksplisit. |
| Public ID | UUIDv7 yang dipakai pada kontrak eksternal tanpa menggantikan primary key internal package pada Phase 09. |
| Instrument version | Snapshot content instrumen dengan semantic version, review hash, dan status; immutable setelah approved. |
| Content hash | SHA-256 canonical structure instrument yang membuktikan content review tidak berubah sebelum approval. |
| Campaign preflight | Pemeriksaan blocker instrument, waktu, privacy notice, threshold, action owner, dan target sebelum review/publish. |
| Policy snapshot | Salinan instrument hash dan parameter privacy/reporting/timezone yang dibekukan saat survey dipublikasikan. |
| Response counter guard | Counter integrasi yang membuat configuration/target campaign tidak dapat diubah setelah respons tersedia. |
