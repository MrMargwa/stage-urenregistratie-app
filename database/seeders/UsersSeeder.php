<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Maakt het admin-account aan (en in de dev-omgeving ook een testaccount).
     *
     * De seeder is idempotent: bestaande accounts worden bijgewerkt, maar er
     * worden nooit stage-uren of andere gegevens gewijzigd.
     */
    public function run(): void
    {
        $this->upsertUser($this->account('admin', Role::Admin));

        if ($this->isLocal()) {
            $this->upsertUser($this->account('user', Role::User));
        }
    }

    /**
     * @return array{email: string, name: string, password: string, role: Role}
     */
    private function account(string $kind, Role $role): array
    {
        return [
            'email' => config('seeding.'.$kind.'_email'),
            'name' => $kind === 'admin' ? 'Admin' : 'Test Account 01',
            'password' => $this->password($kind),
            'role' => $role,
        ];
    }

    private function isLocal(): bool
    {
        return ! app()->environment('production');
    }

    /**
     * Bepaalt het wachtwoord voor een account.
     *
     * Lokaal wordt een veilige willekeurige fallback gegenereerd en in de
     * console getoond. In productie is het wachtwoord altijd verplicht via een
     * omgevingsvariabele; zonder die variabele stoppen we met een duidelijke
     * foutmelding in plaats van een bekend/zwak wachtwoord te gebruiken.
     */
    private function password(string $kind): string
    {
        $value = config('seeding.'.$kind.'_password');

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (! $this->isLocal()) {
            throw new \RuntimeException(
                'Omgevingsvariabele [SEED_'.strtoupper($kind).'_PASSWORD] ontbreekt. '.
                'In productie moet het account-wachtwoord expliciet worden ingesteld; '.
                'er wordt nooit een standaardwachtwoord gebruikt.'
            );
        }

        $generated = Str::password(20);

        $this->command?->warn(
            'Geen SEED_'.strtoupper($kind).'_PASSWORD ingesteld => willekeurig wachtwoord '.
            "gegenereerd voor {$kind}-account: {$generated}"
        );

        return $generated;
    }

    /**
     * @param  array{email: string, name: string, password: string, role: Role}  $attributes
     */
    private function upsertUser(array $attributes): void
    {
        $email = $attributes['email'];

        unset($attributes['email']);

        User::updateOrCreate(['email' => $email], $attributes);
    }
}
