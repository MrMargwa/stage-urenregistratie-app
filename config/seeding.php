<?php

/*
|--------------------------------------------------------------------------
| Seeding-configuratie
|--------------------------------------------------------------------------
|
| Waarden voor het aanmaken van accounts via UsersSeeder. Deze worden via
| env() uit de omgeving gelezen zodat config:cache in productie werkt.
|
| In productie M&Ugrave;ST SEED_ADMIN_PASSWORD worden gezet. Er wordt nooit
| een standaardwachtwoord gebruikt voor productie-accounts.
|
*/

return [

    /*
    | E-mailadres van het admin-account.
    */
    'admin_email' => env('SEED_ADMIN_EMAIL', 'admin@example.com'),

    /*
    | E-mailadres van het testaccount (alleen aangemaakt in de dev-omgeving).
    */
    'user_email' => env('SEED_USER_EMAIL', 'testaccount01@example.com'),

    /*
    | Wachtwoorden voor deze accounts. In productie altijd verplicht via env.
    | In de dev-omgeving wordt een willekeurig wachtwoord gegenereerd als deze
    | niet is ingesteld.
    */
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    'user_password' => env('SEED_USER_PASSWORD'),

];
