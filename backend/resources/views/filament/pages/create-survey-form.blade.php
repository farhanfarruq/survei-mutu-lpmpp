<x-filament-panels::page>
    <form
        wire:submit="create"
        class="survey-builder"
        x-data="{
            form: $wire.entangle('data').live,
            items(value) {
                return Array.isArray(value) ? value : Object.values(value || {})
            },
            questions() {
                return this.items(this.form?.questions)
            },
            options(question) {
                return this.items(question?.options)
                    .map((option) => typeof option === 'string' ? option : option?.label)
                    .filter(Boolean)
            },
            typeLabel(type) {
                return {
                    scale: 'Skala kepuasan 1–5',
                    short_text: 'Jawaban singkat',
                    long_text: 'Jawaban panjang',
                    single_choice: 'Pilih satu',
                    multiple_choice: 'Pilih beberapa',
                    number: 'Angka',
                }[type] || 'Jawaban singkat'
            },
        }"
    >
        <header class="survey-builder__toolbar">
            <div class="survey-builder__toolbar-title">
                <div class="survey-builder__mark" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-document-text" />
                </div>
                <div>
                    <p>Editor formulir</p>
                    <h1 x-text="form?.title || 'Formulir tanpa judul'"></h1>
                </div>
            </div>

            <div class="survey-builder__toolbar-actions">
                <span class="survey-builder__draft-status">
                    <span aria-hidden="true"></span>
                    Draf baru
                </span>
                <x-filament::button
                    type="submit"
                    icon="heroicon-o-check"
                    wire:loading.attr="disabled"
                    wire:target="create"
                >
                    <span wire:loading.remove wire:target="create">Simpan formulir</span>
                    <span wire:loading wire:target="create">Menyimpan...</span>
                </x-filament::button>
            </div>
        </header>

        <div class="survey-builder__workspace">
            <section class="survey-builder__editor" aria-labelledby="builder-heading">
                <div class="survey-builder__section-heading">
                    <span class="survey-builder__step">Bangun</span>
                    <h2 id="builder-heading">Bangun formulir</h2>
                    <p>Isi judul, pilih jenis jawaban, lalu tambah pertanyaan.</p>
                </div>

                {{ $this->form }}

                <div class="survey-builder__hint">
                    <x-filament::icon icon="heroicon-o-light-bulb" aria-hidden="true" />
                    <p><strong>Tips:</strong> gunakan kalimat singkat dan satu maksud untuk setiap pertanyaan.</p>
                </div>
            </section>

            <aside class="survey-builder__preview" aria-labelledby="preview-heading">
                <div class="survey-builder__preview-heading">
                    <div>
                        <span>Pratinjau</span>
                        <h2 id="preview-heading">Pratinjau langsung</h2>
                    </div>
                    <p><span x-text="questions().length"></span> pertanyaan</p>
                </div>

                <div class="survey-builder__browser">
                    <div class="survey-builder__browser-bar" aria-hidden="true">
                        <i></i><i></i><i></i>
                        <span>Pratinjau formulir</span>
                    </div>

                    <div class="survey-builder__canvas">
                        <article class="survey-builder__form-preview">
                            <div class="survey-builder__form-accent"></div>
                            <div class="survey-builder__form-header">
                                <p>Survei Mutu LPMPP</p>
                                <h2 x-text="form?.title || 'Formulir tanpa judul'"></h2>
                                <div
                                    x-show="form?.description"
                                    x-text="form?.description"
                                    class="survey-builder__description"
                                ></div>
                                <small><span aria-hidden="true">*</span> Menandai pertanyaan wajib</small>
                            </div>

                            <div class="survey-builder__empty" x-show="questions().length === 0">
                                <x-filament::icon icon="heroicon-o-plus-circle" aria-hidden="true" />
                                <strong>Belum ada pertanyaan</strong>
                                <span>Gunakan tombol “Tambah pertanyaan” di panel kiri.</span>
                            </div>

                            <div class="survey-builder__questions" x-show="questions().length > 0">
                                <template x-for="(question, index) in questions()" :key="index">
                                    <section class="survey-builder__question">
                                        <div class="survey-builder__question-heading">
                                            <span x-text="index + 1"></span>
                                            <div>
                                                <h3>
                                                    <span x-text="question.item_text || 'Pertanyaan baru'"></span>
                                                    <em x-show="question.is_required" aria-label="Wajib diisi">*</em>
                                                </h3>
                                                <p x-show="question.help_text" x-text="question.help_text"></p>
                                            </div>
                                        </div>

                                        <div class="survey-builder__answer">
                                            <template x-if="!question.response_type || question.response_type === 'short_text'">
                                                <div class="survey-builder__line-input">Ketik jawaban singkat</div>
                                            </template>
                                            <template x-if="question.response_type === 'long_text'">
                                                <div class="survey-builder__long-input">Ketik jawaban</div>
                                            </template>
                                            <template x-if="question.response_type === 'number'">
                                                <div class="survey-builder__line-input">Masukkan angka</div>
                                            </template>
                                            <template x-if="question.response_type === 'scale'">
                                                <div class="survey-builder__choices">
                                                    <template x-for="label in ['1 — Sangat tidak puas', '2 — Tidak puas', '3 — Cukup', '4 — Puas', '5 — Sangat puas']" :key="label">
                                                        <div><span class="is-radio" aria-hidden="true"></span><span x-text="label"></span></div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="['single_choice', 'multiple_choice'].includes(question.response_type)">
                                                <div class="survey-builder__choices">
                                                    <template x-for="(option, optionIndex) in options(question)" :key="optionIndex">
                                                        <div>
                                                            <span
                                                                :class="question.response_type === 'single_choice' ? 'is-radio' : 'is-checkbox'"
                                                                aria-hidden="true"
                                                            ></span>
                                                            <span x-text="option"></span>
                                                        </div>
                                                    </template>
                                                    <div x-show="options(question).length === 0" class="survey-builder__choice-placeholder">
                                                        Pilihan jawaban akan tampil di sini
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <span class="survey-builder__type" x-text="typeLabel(question.response_type)"></span>
                                    </section>
                                </template>
                            </div>

                            <button type="button" class="survey-builder__preview-button" disabled>Kirim jawaban</button>
                        </article>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    <style>
        .survey-builder {
            --builder-accent: #0284c7;
            --builder-accent-soft: #e0f2fe;
            --builder-border: #e5e7eb;
            --builder-muted: #64748b;
            --builder-panel: #ffffff;
            --builder-surface: #f8fafc;
            color: #0f172a;
            overflow: hidden;
            border: 1px solid var(--builder-border);
            border-radius: 1rem;
            background: var(--builder-panel);
            box-shadow: 0 1px 3px rgb(15 23 42 / 0.08);
        }

        .dark .survey-builder {
            --builder-accent-soft: #0c4a6e;
            --builder-border: #334155;
            --builder-muted: #94a3b8;
            --builder-panel: #0f172a;
            --builder-surface: #111827;
            color: #f8fafc;
        }

        .survey-builder__toolbar,
        .survey-builder__toolbar-title,
        .survey-builder__toolbar-actions,
        .survey-builder__preview-heading,
        .survey-builder__question-heading,
        .survey-builder__choices > div {
            display: flex;
            align-items: center;
        }

        .survey-builder__toolbar {
            min-height: 4.5rem;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--builder-border);
            background: var(--builder-panel);
        }

        .survey-builder__toolbar-title,
        .survey-builder__toolbar-actions {
            gap: 0.75rem;
        }

        .survey-builder__mark {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 0.75rem;
            color: #ffffff;
            background: var(--builder-accent);
        }

        .survey-builder__mark svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .survey-builder__toolbar-title p,
        .survey-builder__toolbar-title h1,
        .survey-builder__section-heading h2,
        .survey-builder__section-heading p,
        .survey-builder__preview-heading h2,
        .survey-builder__preview-heading p,
        .survey-builder__form-header p,
        .survey-builder__form-header h2,
        .survey-builder__question h3,
        .survey-builder__question p {
            margin: 0;
        }

        .survey-builder__toolbar-title p,
        .survey-builder__preview-heading span,
        .survey-builder__form-header > p {
            color: var(--builder-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .survey-builder__toolbar-title h1 {
            max-width: 32rem;
            overflow: hidden;
            font-size: 1rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .survey-builder__draft-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: var(--builder-muted);
            font-size: 0.875rem;
        }

        .survey-builder__draft-status span {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: #f59e0b;
        }

        .survey-builder__workspace {
            display: grid;
            grid-template-columns: minmax(22rem, 29rem) minmax(0, 1fr);
            min-height: min(50rem, calc(100dvh - 10rem));
        }

        .survey-builder__editor {
            padding: 1.5rem;
            border-right: 1px solid var(--builder-border);
            background: var(--builder-panel);
        }

        .survey-builder__section-heading {
            margin-bottom: 1.5rem;
        }

        .survey-builder__step {
            display: inline-flex;
            margin-bottom: 0.625rem;
            padding: 0.25rem 0.625rem;
            border-radius: 999px;
            color: #0369a1;
            background: var(--builder-accent-soft);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .survey-builder__step {
            color: #e0f2fe;
        }

        .survey-builder__section-heading h2,
        .survey-builder__preview-heading h2,
        .survey-builder__form-header h2 {
            text-wrap: balance;
        }

        .survey-builder__section-heading h2 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .survey-builder__section-heading p {
            margin-top: 0.25rem;
            color: var(--builder-muted);
            font-size: 0.875rem;
            text-wrap: pretty;
        }

        .survey-builder__editor .fi-fo-repeater-item {
            border-color: var(--builder-border);
            border-radius: 0.875rem;
            box-shadow: none;
        }

        .survey-builder__editor .fi-fo-repeater-add-between-action-ctn {
            display: none;
        }

        .survey-builder__hint {
            display: flex;
            gap: 0.625rem;
            margin-top: 1rem;
            padding: 0.875rem;
            border: 1px solid #bae6fd;
            border-radius: 0.75rem;
            color: #0c4a6e;
            background: #f0f9ff;
            font-size: 0.8125rem;
        }

        .dark .survey-builder__hint {
            border-color: #075985;
            color: #e0f2fe;
            background: #082f49;
        }

        .survey-builder__hint svg {
            width: 1.1rem;
            height: 1.1rem;
            flex: 0 0 auto;
        }

        .survey-builder__hint p {
            margin: 0;
            text-wrap: pretty;
        }

        .survey-builder__preview {
            min-width: 0;
            padding: 1.5rem;
            background: var(--builder-surface);
        }

        .survey-builder__preview-heading {
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .survey-builder__preview-heading h2 {
            font-size: 1rem;
            font-weight: 700;
        }

        .survey-builder__preview-heading p {
            color: var(--builder-muted);
            font-size: 0.8125rem;
            font-variant-numeric: tabular-nums;
        }

        .survey-builder__browser {
            position: sticky;
            top: 5.5rem;
            overflow: hidden;
            border: 1px solid var(--builder-border);
            border-radius: 0.875rem;
            background: #ffffff;
            box-shadow: 0 1px 3px rgb(15 23 42 / 0.08);
        }

        .survey-builder__browser-bar {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            height: 2.5rem;
            padding: 0 0.875rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .survey-builder__browser-bar i {
            width: 0.625rem;
            height: 0.625rem;
            border-radius: 999px;
            background: #ef4444;
        }

        .survey-builder__browser-bar i:nth-child(2) { background: #f59e0b; }
        .survey-builder__browser-bar i:nth-child(3) { background: #22c55e; }

        .survey-builder__browser-bar span {
            margin-left: 0.75rem;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        .survey-builder__canvas {
            max-height: calc(100dvh - 16rem);
            overflow: auto;
            padding: clamp(1rem, 3vw, 2.5rem);
            background: #f1f5f9;
        }

        .survey-builder__form-preview {
            position: relative;
            width: min(100%, 42rem);
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            color: #0f172a;
            background: #ffffff;
            box-shadow: 0 1px 3px rgb(15 23 42 / 0.08);
        }

        .survey-builder__form-accent {
            height: 0.375rem;
            background: var(--builder-accent);
        }

        .survey-builder__form-header {
            padding: 1.75rem 1.75rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .survey-builder__form-header > p {
            color: #0284c7;
        }

        .survey-builder__form-header h2 {
            margin-top: 0.375rem;
            font-size: clamp(1.35rem, 3vw, 1.75rem);
            font-weight: 700;
        }

        .survey-builder__description {
            margin-top: 0.625rem;
            color: #475569;
            font-size: 0.9rem;
            white-space: pre-line;
            text-wrap: pretty;
        }

        .survey-builder__form-header small {
            display: block;
            margin-top: 1rem;
            color: #64748b;
            font-size: 0.75rem;
        }

        .survey-builder__form-header small span,
        .survey-builder__question em {
            color: #dc2626;
        }

        .survey-builder__questions {
            padding: 0 1.75rem;
        }

        .survey-builder__question {
            position: relative;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .survey-builder__question-heading {
            align-items: flex-start;
            gap: 0.75rem;
        }

        .survey-builder__question-heading > span {
            display: grid;
            width: 1.75rem;
            height: 1.75rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 999px;
            color: #0369a1;
            background: #e0f2fe;
            font-size: 0.75rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .survey-builder__question h3 {
            padding-right: 5rem;
            font-size: 0.95rem;
            font-style: normal;
            font-weight: 600;
            text-wrap: pretty;
        }

        .survey-builder__question h3 em {
            font-style: normal;
        }

        .survey-builder__question p {
            margin-top: 0.25rem;
            color: #64748b;
            font-size: 0.8rem;
            text-wrap: pretty;
        }

        .survey-builder__answer {
            margin: 1rem 0 0 2.5rem;
        }

        .survey-builder__line-input,
        .survey-builder__long-input {
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            color: #94a3b8;
            background: #ffffff;
            font-size: 0.8125rem;
        }

        .survey-builder__line-input {
            padding: 0.7rem 0.8rem;
        }

        .survey-builder__long-input {
            min-height: 5rem;
            padding: 0.8rem;
        }

        .survey-builder__choices {
            display: grid;
            gap: 0.625rem;
            color: #334155;
            font-size: 0.85rem;
        }

        .survey-builder__choices > div {
            gap: 0.625rem;
        }

        .survey-builder__choices .is-radio,
        .survey-builder__choices .is-checkbox {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
            border: 1px solid #94a3b8;
            background: #ffffff;
        }

        .survey-builder__choices .is-radio { border-radius: 999px; }
        .survey-builder__choices .is-checkbox { border-radius: 0.25rem; }

        .survey-builder__choice-placeholder {
            color: #94a3b8;
            font-style: italic;
        }

        .survey-builder__type {
            position: absolute;
            top: 1.5rem;
            right: 0;
            color: #64748b;
            font-size: 0.7rem;
        }

        .survey-builder__empty {
            display: grid;
            justify-items: center;
            gap: 0.3rem;
            padding: 3rem 1.5rem;
            color: #64748b;
            text-align: center;
        }

        .survey-builder__empty svg {
            width: 2rem;
            height: 2rem;
            margin-bottom: 0.25rem;
            color: #0284c7;
        }

        .survey-builder__empty strong { color: #334155; }
        .survey-builder__empty span { font-size: 0.8125rem; }

        .survey-builder__preview-button {
            margin: 1.5rem 1.75rem 1.75rem;
            padding: 0.7rem 1.1rem;
            border: 0;
            border-radius: 0.5rem;
            color: #ffffff;
            background: var(--builder-accent);
            font-size: 0.875rem;
            font-weight: 600;
            opacity: 1;
        }

        @media (max-width: 1023px) {
            .survey-builder__workspace {
                grid-template-columns: 1fr;
            }

            .survey-builder__editor {
                border-right: 0;
                border-bottom: 1px solid var(--builder-border);
            }

            .survey-builder__browser {
                position: static;
            }

            .survey-builder__canvas {
                max-height: none;
            }
        }

        @media (max-width: 639px) {
            .survey-builder {
                border-right: 0;
                border-left: 0;
                border-radius: 0;
            }

            .survey-builder__toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .survey-builder__toolbar-actions {
                width: 100%;
                justify-content: space-between;
            }

            .survey-builder__toolbar-title h1 {
                max-width: 15rem;
            }

            .survey-builder__editor,
            .survey-builder__preview {
                padding: 1rem;
            }

            .survey-builder__form-header,
            .survey-builder__questions {
                padding-right: 1rem;
                padding-left: 1rem;
            }

            .survey-builder__question h3 {
                padding-right: 0;
            }

            .survey-builder__type {
                position: static;
                display: block;
                margin: 0.75rem 0 0 2.5rem;
            }
        }
    </style>
</x-filament-panels::page>
