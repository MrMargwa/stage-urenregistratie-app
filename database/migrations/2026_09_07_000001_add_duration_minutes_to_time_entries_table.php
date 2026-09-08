<?php

use App\Helpers\DurationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->default(0)->after('break_minutes');
        });

        DB::table('time_entries')->chunkById(500, function ($entries): void {
            foreach ($entries as $entry) {
                $duration = DurationHelper::toMinutes(
                    $entry->start_time,
                    $entry->end_time,
                    (int) ($entry->break_minutes ?? 0)
                );

                DB::table('time_entries')
                    ->where('id', $entry->id)
                    ->update(['duration_minutes' => $duration]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};
