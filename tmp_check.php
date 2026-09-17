<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (DB::table('users')->get(['id', 'email', 'ui_preferences']) as $row) {
    echo $row->id.' | '.$row->email.' | '.json_encode($row->ui_preferences).PHP_EOL;
}