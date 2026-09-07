<?php

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
                $duration = self::calculateDuration($entry->start_time, $entry->end_time, $entry->break_minutes);

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

    /**
     * Zelfde formule als TimeEntry::computeDuration(), maar zonder Eloquent-model
     * zodat bestaande data veilig kan worden teruggeschreven.
     */
    private static function calculateDuration(string $startTime, string $endTime, ?int $breakMinutes): int
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        $minutes = (int) round(($end - $start) / 60);

        if ($minutes < 0) {
            $minutes += 1440;
        }

        return max(0, $minutes - (int) ($breakMinutes ?? 0));
    }
};
