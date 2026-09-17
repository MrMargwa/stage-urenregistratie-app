<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validatie taalregels
    |--------------------------------------------------------------------------
    |
    | De onderstaande zinnen zijn de standaard foutmeldingen die de validator
    | gebruikt. Sommige regels kennen meerdere varianten, zoals de size-regels.
    | Voel je vrij om de teksten hier aan te passen aan je eigen stijl.
    |
    */

    'accepted' => 'Het veld :attribute moet worden geaccepteerd.',
    'accepted_if' => 'Het veld :attribute moet worden geaccepteerd wanneer :other gelijk is aan :value.',
    'active_url' => 'Het veld :attribute is geen geldige URL.',
    'after' => 'Het veld :attribute moet een datum zijn ná :date.',
    'after_or_equal' => 'Het veld :attribute moet een datum zijn ná of gelijk aan :date.',
    'alpha' => 'Het veld :attribute mag alleen letters bevatten.',
    'alpha_dash' => 'Het veld :attribute mag alleen letters, cijfers, streepjes (-) en underscores bevatten.',
    'alpha_num' => 'Het veld :attribute mag alleen letters en cijfers bevatten.',
    'any_of' => 'Het veld :attribute is ongeldig.',
    'array' => 'Het veld :attribute moet een array zijn.',
    'array_keys' => 'Het veld :attribute mag alleen de volgende sleutels bevatten: :values.',
    'ascii' => 'Het veld :attribute mag alleen uit enkel-byte alfanumerieke karakters en symbolen bestaan.',
    'base64' => 'Het veld :attribute moet een geldige Base64-tekenreeks zijn.',
    'before' => 'Het veld :attribute moet een datum zijn vóór :date.',
    'before_or_equal' => 'Het veld :attribute moet een datum zijn vóór of gelijk aan :date.',
    'between' => [
        'array' => 'Het veld :attribute moet tussen :min en :max items bevatten.',
        'file' => 'Het veld :attribute moet tussen :min en :max kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet tussen :min en :max zijn.',
        'string' => 'Het veld :attribute moet tussen :min en :max karakters bevatten.',
    ],
    'boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
    'can' => 'Het veld :attribute bevat een waarde waarvoor geen toestemming is.',
    'confirmed' => 'De bevestiging van het veld :attribute komt niet overeen.',
    'contains' => 'Het veld :attribute mist een verplichte waarde.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => 'Het veld :attribute is geen geldige datum.',
    'date_equals' => 'Het veld :attribute moet een datum zijn gelijk aan :date.',
    'date_format' => 'Het veld :attribute moet overeenkomen met het formaat :format.',
    'decimal' => 'Het veld :attribute moet :decimal decimalen hebben.',
    'declined' => 'Het veld :attribute moet worden afgewezen.',
    'declined_if' => 'Het veld :attribute moet worden afgewezen wanneer :other gelijk is aan :value.',
    'different' => 'Het veld :attribute en :other moeten verschillend zijn.',
    'digits' => 'Het veld :attribute moet uit :digits cijfers bestaan.',
    'digits_between' => 'Het veld :attribute moet tussen :min en :max cijfers bevatten.',
    'dimensions' => 'Het veld :attribute heeft ongeldige afbeeldingsdimensies.',
    'distinct' => 'Het veld :attribute bevat een dubbele waarde.',
    'doesnt_contain' => 'Het veld :attribute mag geen van de volgende waarden bevatten: :values.',
    'doesnt_end_with' => 'Het veld :attribute mag niet eindigen met een van de volgende waarden: :values.',
    'doesnt_start_with' => 'Het veld :attribute mag niet beginnen met een van de volgende waarden: :values.',
    'email' => 'Het veld :attribute is geen geldig e-mailadres.',
    'encoding' => 'Het veld :attribute moet gecodeerd zijn in :encoding.',
    'ends_with' => 'Het veld :attribute moet eindigen met een van de volgende waarden: :values.',
    'enum' => 'De gekozen :attribute is ongeldig.',
    'exists' => 'De gekozen :attribute is ongeldig.',
    'extensions' => 'Het veld :attribute moet een van de volgende extensies hebben: :values.',
    'file' => 'Het veld :attribute moet een bestand zijn.',
    'filled' => 'Het veld :attribute moet een waarde hebben.',
    'gt' => [
        'array' => 'Het veld :attribute moet meer dan :value items bevatten.',
        'file' => 'Het veld :attribute moet groter zijn dan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet groter zijn dan :value.',
        'string' => 'Het veld :attribute moet groter zijn dan :value karakters.',
    ],
    'gte' => [
        'array' => 'Het veld :attribute moet :value items of meer bevatten.',
        'file' => 'Het veld :attribute moet groter dan of gelijk zijn aan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet groter dan of gelijk zijn aan :value.',
        'string' => 'Het veld :attribute moet groter dan of gelijk zijn aan :value karakters.',
    ],
    'hex_color' => 'Het veld :attribute moet een geldige hexadecimale kleur zijn.',
    'image' => 'Het veld :attribute moet een afbeelding zijn.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'in_array' => 'Het veld :attribute moet bestaan in :other.',
    'in_array_keys' => 'Het veld :attribute moet ten minste een van de volgende sleutels bevatten: :values.',
    'integer' => 'Het veld :attribute moet een geheel getal zijn.',
    'ip' => 'Het veld :attribute moet een geldig IP-adres zijn.',
    'ipv4' => 'Het veld :attribute moet een geldig IPv4-adres zijn.',
    'ipv6' => 'Het veld :attribute moet een geldig IPv6-adres zijn.',
    'json' => 'Het veld :attribute moet een geldige JSON-tekenreeks zijn.',
    'list' => 'Het veld :attribute moet een lijst zijn.',
    'lowercase' => 'Het veld :attribute mag alleen kleine letters bevatten.',
    'lt' => [
        'array' => 'Het veld :attribute moet minder dan :value items bevatten.',
        'file' => 'Het veld :attribute moet kleiner zijn dan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet kleiner zijn dan :value.',
        'string' => 'Het veld :attribute moet kleiner zijn dan :value karakters.',
    ],
    'lte' => [
        'array' => 'Het veld :attribute mag niet meer dan :value items bevatten.',
        'file' => 'Het veld :attribute moet kleiner dan of gelijk zijn aan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet kleiner dan of gelijk zijn aan :value.',
        'string' => 'Het veld :attribute moet kleiner dan of gelijk zijn aan :value karakters.',
    ],
    'mac_address' => 'Het veld :attribute moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => 'Het veld :attribute mag niet meer dan :max items bevatten.',
        'file' => 'Het veld :attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => 'Het veld :attribute mag niet groter zijn dan :max.',
        'string' => 'Het veld :attribute mag niet groter zijn dan :max karakters.',
    ],
    'max_digits' => 'Het veld :attribute mag niet meer dan :max cijfers bevatten.',
    'mimes' => 'Het veld :attribute moet een bestand zijn van het type: :values.',
    'mimetypes' => 'Het veld :attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'array' => 'Het veld :attribute moet ten minste :min items bevatten.',
        'file' => 'Het veld :attribute moet ten minste :min kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet ten minste :min zijn.',
        'string' => 'Het veld :attribute moet ten minste :min karakters bevatten.',
    ],
    'min_digits' => 'Het veld :attribute moet ten minste :min cijfers bevatten.',
    'missing' => 'Het veld :attribute moet ontbreken.',
    'missing_if' => 'Het veld :attribute moet ontbreken wanneer :other gelijk is aan :value.',
    'missing_unless' => 'Het veld :attribute moet ontbreken tenzij :other gelijk is aan :value.',
    'missing_with' => 'Het veld :attribute moet ontbreken wanneer :values aanwezig is.',
    'missing_with_all' => 'Het veld :attribute moet ontbreken wanneer :values aanwezig zijn.',
    'multiple_of' => 'Het veld :attribute moet een veelvoud van :value zijn.',
    'not_in' => 'De gekozen :attribute is ongeldig.',
    'not_regex' => 'Het veld :attribute heeft een ongeldig formaat.',
    'numeric' => 'Het veld :attribute moet een getal zijn.',
    'password' => [
        'letters' => 'Het veld :attribute moet ten minste één letter bevatten.',
        'mixed' => 'Het veld :attribute moet ten minste één hoofdletter en één kleine letter bevatten.',
        'numbers' => 'Het veld :attribute moet ten minste één cijfer bevatten.',
        'symbols' => 'Het veld :attribute moet ten minste één symbool bevatten.',
        'uncompromised' => 'Het opgegeven :attribute is verschenen in een datalek. Kies een ander :attribute.',
    ],
    'present' => 'Het veld :attribute moet aanwezig zijn.',
    'present_if' => 'Het veld :attribute moet aanwezig zijn wanneer :other gelijk is aan :value.',
    'present_unless' => 'Het veld :attribute moet aanwezig zijn tenzij :other gelijk is aan :value.',
    'present_with' => 'Het veld :attribute moet aanwezig zijn wanneer :values aanwezig is.',
    'present_with_all' => 'Het veld :attribute moet aanwezig zijn wanneer :values aanwezig zijn.',
    'prohibited' => 'Het veld :attribute is niet toegestaan.',
    'prohibited_if' => 'Het veld :attribute is niet toegestaan wanneer :other gelijk is aan :value.',
    'prohibited_if_accepted' => 'Het veld :attribute is niet toegestaan wanneer :other is geaccepteerd.',
    'prohibited_if_declined' => 'Het veld :attribute is niet toegestaan wanneer :other is afgewezen.',
    'prohibited_unless' => 'Het veld :attribute is niet toegestaan tenzij :other in :values voorkomt.',
    'prohibits' => 'Het veld :attribute verhindert dat :other aanwezig is.',
    'regex' => 'Het veld :attribute heeft een ongeldig formaat.',
    'required' => 'Het veld :attribute is verplicht.',
    'required_array_keys' => 'Het veld :attribute moet vermeldingen bevatten voor: :values.',
    'required_if' => 'Het veld :attribute is verplicht wanneer :other gelijk is aan :value.',
    'required_if_accepted' => 'Het veld :attribute is verplicht wanneer :other is geaccepteerd.',
    'required_if_declined' => 'Het veld :attribute is verplicht wanneer :other is afgewezen.',
    'required_unless' => 'Het veld :attribute is verplicht tenzij :other in :values voorkomt.',
    'required_with' => 'Het veld :attribute is verplicht wanneer :values aanwezig is.',
    'required_with_all' => 'Het veld :attribute is verplicht wanneer :values aanwezig zijn.',
    'required_without' => 'Het veld :attribute is verplicht wanneer :values niet aanwezig is.',
    'required_without_all' => 'Het veld :attribute is verplicht wanneer geen van :values aanwezig zijn.',
    'same' => 'Het veld :attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => 'Het veld :attribute moet uit :size items bestaan.',
        'file' => 'Het veld :attribute moet :size kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet :size zijn.',
        'string' => 'Het veld :attribute moet uit :size karakters bestaan.',
    ],
    'starts_with' => 'Het veld :attribute moet beginnen met een van de volgende waarden: :values.',
    'string' => 'Het veld :attribute moet een tekenreeks zijn.',
    'timezone' => 'Het veld :attribute moet een geldige tijdzone zijn.',
    'unique' => 'De :attribute is al in gebruik.',
    'uploaded' => 'Het uploaden van het veld :attribute is mislukt.',
    'uppercase' => 'Het veld :attribute mag alleen hoofdletters bevatten.',
    'url' => 'Het veld :attribute is geen geldige URL.',
    'ulid' => 'Het veld :attribute moet een geldige ULID zijn.',
    'uuid' => 'Het veld :attribute moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Aangepaste validatie taalregels
    |--------------------------------------------------------------------------
    |
    | Hier kun je per veld een specifieke foutmelding definiëren, met de
    | conventie "veldnaam.regel" als naam van de taalregel.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Aangepaste veldnamen
    |--------------------------------------------------------------------------
    |
    | De regels hieronder vervangen de :attribute-plaatshouder door een leesbare
    | naam, zoals "e-mailadres" in plaats van "email". De meeste formulieren in
    | deze app gebruiken de labeltekst van het veld zelf als veldnaam; deze
    | lijst vangt de rest op.
    |
    */

    'attributes' => [
        'name' => 'naam',
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
        'password_confirmation' => 'wachtwoordbevestiging',
        'role' => 'rol',
        'date' => 'datum',
        'start_time' => 'begintijd',
        'end_time' => 'eindtijd',
        'break_minutes' => 'pauze',
        'description' => 'beschrijving',
        'target_hours' => 'aantal stage-uren',
        'default_start_time' => 'standaard begintijd',
        'default_end_time' => 'standaard eindtijd',
        'default_break_minutes' => 'standaard pauzeminuten',
        'file' => 'bestand',
        'user_id' => 'gebruiker',
    ],

];