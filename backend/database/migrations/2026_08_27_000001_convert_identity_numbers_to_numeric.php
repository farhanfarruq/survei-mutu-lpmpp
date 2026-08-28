<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $used = [];

        foreach (DB::table('users')->whereNotNull('identity_number')->pluck('identity_number') as $identity) {
            if (preg_match('/^[0-9]+$/', (string) $identity)) {
                $used[(string) $identity] = true;
            }
        }

        $knownLegacyNumbers = [
            'ADMIN-001' => '900000000001',
            'LPMPP-001' => '900000000002',
            'PIMPINAN-001' => '120000000001',
            'MHS-001' => '20260001',
        ];
        $nextStudentNumber = 90000000;
        $nextStaffOrLecturerNumber = 900000000010;

        foreach (DB::table('users')->select('id', 'identity_number', 'account_type')->orderBy('id')->get() as $user) {
            $identity = (string) $user->identity_number;

            if (preg_match('/^[0-9]+$/', $identity)) {
                continue;
            }

            $candidate = $knownLegacyNumbers[$identity] ?? null;

            if ($candidate === null || isset($used[$candidate])) {
                $candidate = $user->account_type === 'student'
                    ? (string) $nextStudentNumber++
                    : (string) $nextStaffOrLecturerNumber++;

                while (isset($used[$candidate])) {
                    $candidate = $user->account_type === 'student'
                        ? (string) $nextStudentNumber++
                        : (string) $nextStaffOrLecturerNumber++;
                }
            }

            DB::table('users')->where('id', $user->id)->update(['identity_number' => $candidate]);
            $used[$candidate] = true;
        }
    }

    public function down(): void
    {
        // Identifier changes cannot be reversed safely after users start signing in with them.
    }
};
