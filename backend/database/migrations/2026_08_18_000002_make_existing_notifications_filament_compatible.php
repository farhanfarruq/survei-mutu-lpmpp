<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->orderBy('created_at')->chunk(200, function ($notifications): void {
            foreach ($notifications as $notification) {
                $data = json_decode($notification->data, true, flags: JSON_THROW_ON_ERROR);
                $data['format'] = 'filament';
                $data['body'] ??= $data['message'] ?? '';
                $data['status'] ??= 'info';

                DB::table('notifications')->where('id', $notification->id)->update([
                    'data' => json_encode($data, JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    public function down(): void {}
};
