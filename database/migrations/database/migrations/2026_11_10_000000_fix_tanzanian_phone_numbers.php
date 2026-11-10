<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix numbers saved as "+2550XXXXXXXXX" (the local 0 typed after
 * choosing +255). Nobody could find these users from their phone
 * contacts, so they always showed up under "Invite to Webs".
 *
 * "+2550712345678" becomes "+255712345678". If that corrected
 * number already belongs to another account, the row is left alone
 * and logged, so no account is overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'pending_registrations'] as $table) {
            $rows = DB::table($table)
                ->where('phone_number', 'like', '+2550%')
                ->get(['id', 'phone_number']);

            foreach ($rows as $row) {
                if (!preg_match('/^\+2550(\d{9})$/', $row->phone_number, $m)) {
                    continue;
                }

                $fixed = '+255' . $m[1];

                $taken = DB::table($table)
                    ->where('phone_number', $fixed)
                    ->where('id', '!=', $row->id)
                    ->exists();

                if ($taken) {
                    Log::warning("Phone fix skipped: {$table}#{$row->id} {$row->phone_number} -> {$fixed} already used");
                    continue;
                }

                DB::table($table)
                    ->where('id', $row->id)
                    ->update(['phone_number' => $fixed]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible: the old form was a typo.
    }
};
