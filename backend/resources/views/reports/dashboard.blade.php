<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Survei</title>
    <style>
        body { color: #17324d; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        h1 { color: #123f5b; font-size: 21px; margin: 0 0 4px; }
        h2 { color: #155f82; font-size: 14px; margin: 22px 0 8px; }
        .subtitle { color: #60758a; margin-bottom: 18px; }
        .meta, .score { border: 1px solid #d8e2eb; border-radius: 6px; padding: 12px; }
        .meta table { margin: 0; }
        .score { background: #edf7fb; margin-top: 12px; }
        .score strong { color: #0f5878; font-size: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #d8e2eb; padding: 7px 6px; text-align: left; vertical-align: top; }
        th { background: #155f82; color: #fff; }
        .number { text-align: right; white-space: nowrap; }
        .muted { color: #60758a; }
        ul { margin: 6px 0; padding-left: 18px; }
        .footer { border-top: 1px solid #d8e2eb; color: #60758a; margin-top: 24px; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Laporan Hasil Survei</h1>
    <div class="subtitle">Ringkasan agregat dashboard SIMUTU</div>

    <div class="meta">
        <table>
            <tr><td><strong>Survei</strong></td><td>{{ $snapshot->survey?->name ?? '-' }}</td></tr>
            <tr><td><strong>Unit</strong></td><td>{{ $snapshot->ownerUnit?->name ?? '-' }}</td></tr>
            <tr><td><strong>Periode</strong></td><td>{{ $snapshot->period?->name ?? '-' }}</td></tr>
            <tr><td><strong>Diperbarui</strong></td><td>{{ $snapshot->generated_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-' }} WIB</td></tr>
            <tr><td><strong>Respons</strong></td><td>{{ $snapshot->response_count }} dari {{ $snapshot->eligible_count }} responden yang memenuhi syarat</td></tr>
        </table>
    </div>

    <div class="score">
        <div class="muted">Skor keseluruhan</div>
        <strong>{{ isset($metrics['overall']['normalized_score']) ? number_format($metrics['overall']['normalized_score'], 1, ',', '.') : '-' }}</strong>
        <div>{{ $metrics['overall']['interpretation'] ?? 'Belum tersedia' }}</div>
    </div>

    <h2>Skor per kategori</h2>
    <table>
        <thead><tr><th>Kategori</th><th class="number">Jumlah</th><th class="number">Skor</th><th>Interpretasi</th></tr></thead>
        <tbody>
        @forelse ($metrics['categories'] ?? [] as $row)
            <tr>
                <td>{{ $row['name'] ?? $row['code'] ?? '-' }}</td>
                <td class="number">{{ $row['n'] ?? '-' }}</td>
                <td class="number">{{ isset($row['normalized_score']) ? number_format($row['normalized_score'], 1, ',', '.') : '-' }}</td>
                <td>{{ $row['interpretation'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Data kategori belum tersedia.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if (! empty($snapshot->limitations))
        <h2>Keterbatasan analisis</h2>
        <ul>
            @foreach ($snapshot->limitations as $limitation)
                <li>{{ $limitation }}</li>
            @endforeach
        </ul>
    @endif

    <div class="footer">Laporan ini hanya memuat data agregat. Jawaban individual tidak ditampilkan.</div>
</body>
</html>
