<?php

namespace App\Services;

class SyncResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $deleted = 0;

    public int $skipped = 0;

    /** @var array<string> */
    public array $errors = [];

    public function summary(): string
    {
        return sprintf(
            'Aangemaakt: %d · Bijgewerkt: %d · Verwijderd: %d · Overgeslagen: %d',
            $this->created,
            $this->updated,
            $this->deleted,
            $this->skipped,
        );
    }
}
