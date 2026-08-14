<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Mutu';

    protected static ?string $navigationLabel = 'Dashboard Mutu';

    protected ?string $subheading = 'Pantau partisipasi, capaian mutu, tren, dan tindak lanjut dalam scope organisasi Anda.';
}
