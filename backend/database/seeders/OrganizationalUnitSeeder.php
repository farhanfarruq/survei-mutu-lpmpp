<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        $university = OrganizationalUnit::query()->updateOrCreate(
            ['code' => 'ITDA'],
            ['name' => 'Institut Teknologi Dirgantara Adisutjipto', 'type' => 'university', 'is_active' => true],
        );

        foreach ([
            'FTK' => [
                'name' => 'Fakultas Teknologi Kedirgantaraan',
                'programs' => [
                    ['code' => 'PRODI-TD', 'name' => 'S1 Teknik Dirgantara'],
                    ['code' => 'PRODI-TM', 'name' => 'S1 Teknik Mesin'],
                    ['code' => 'PRODI-AE', 'name' => 'D3 Aeronautika'],
                ],
            ],
            'FTI' => [
                'name' => 'Fakultas Teknologi Industri',
                'programs' => [
                    ['code' => 'PRODI-TE', 'name' => 'S1 Teknik Elektro'],
                    ['code' => 'PRODI-IF', 'name' => 'S1 Informatika'],
                    ['code' => 'PRODI-TI', 'name' => 'S1 Teknik Industri'],
                ],
            ],
        ] as $code => $facultyData) {
            $faculty = OrganizationalUnit::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $facultyData['name'], 'parent_id' => $university->id, 'type' => 'faculty', 'is_active' => true],
            );

            foreach ($facultyData['programs'] as $program) {
                OrganizationalUnit::query()->updateOrCreate(
                    ['code' => $program['code']],
                    $program + ['parent_id' => $faculty->id, 'type' => 'program', 'is_active' => true],
                );
            }
        }

        foreach ([
            ['code' => 'REKTORAT', 'name' => 'Rektorat'],
            ['code' => 'LPMPP', 'name' => 'Lembaga Penjaminan Mutu dan Pengembangan Pendidikan'],
            ['code' => 'LPPM', 'name' => 'Lembaga Penelitian dan Pengabdian kepada Masyarakat'],
            ['code' => 'LPIK', 'name' => 'Lembaga Pengembangan Inovasi dan Kewirausahaan'],
            ['code' => 'SPI', 'name' => 'Satuan Pengawasan Internal'],
            ['code' => 'PERPUSTAKAAN', 'name' => 'Perpustakaan'],
            ['code' => 'PLTI', 'name' => 'Pusat Layanan Teknologi Informasi'],
            ['code' => 'PLUKK', 'name' => 'Pusat Layanan Umum, Kerumahtanggaan dan Keamanan'],
            ['code' => 'LAB-TERPADU', 'name' => 'Laboratorium Terpadu'],
            ['code' => 'HUMAS-ADMISI', 'name' => 'Pusat Humas dan Admisi'],
            ['code' => 'BIRO-AKADEMIK', 'name' => 'Biro Akademik'],
            ['code' => 'BIRO-SDM', 'name' => 'Biro Sumber Daya Manusia'],
            ['code' => 'BIRO-KEMAHASISWAAN', 'name' => 'Biro Kemahasiswaan'],
            ['code' => 'BIRO-KEUANGAN-ASET', 'name' => 'Biro Keuangan dan Aset'],
            ['code' => 'BIRO-KERJA-SAMA', 'name' => 'Biro Kerja Sama dan Alumni'],
        ] as $unit) {
            OrganizationalUnit::query()->updateOrCreate(
                ['code' => $unit['code']],
                $unit + ['parent_id' => $university->id, 'type' => 'unit', 'is_active' => true],
            );
        }
    }
}
