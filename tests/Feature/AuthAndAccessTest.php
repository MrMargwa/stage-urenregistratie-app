<?php

use App\Models\TimeEntry;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('stuurt gasten van de homepage door naar de login', function () {
    $this->get('/')->assertRedirect('/admin/login');
});

it('stuurt ingelogde gebruikers van de homepage door naar het dashboard', function () {
    actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/admin');
});

it('stuurt gasten met een 404 door naar de login', function () {
    $this->get('/pagina-die-niet-bestaat')
        ->assertRedirect('/admin/login');
});

it('stuurt ingelogde gebruikers met een 404 door naar het dashboard', function () {
    actingAs(User::factory()->create())
        ->get('/pagina-die-niet-bestaat')
        ->assertRedirect('/admin');
});

it('stuurt uitgelogde gebruikers van beveiligde paginas door naar de login', function () {
    $this->get('/admin/time-entries')->assertRedirect();
});

it('laat een gewone gebruiker alleen zijn eigen uren zien', function () {
    $user = User::factory()->create();
    $ander = User::factory()->create();

    $eigen = TimeEntry::factory()->for($user)->create(['description' => 'Mijn eigen uur']);
    $vremd = TimeEntry::factory()->for($ander)->create(['description' => 'Geheim van ander']);

    actingAs($user)
        ->get('/admin/time-entries')
        ->assertOk()
        ->assertSee('Mijn eigen uur')
        ->assertDontSee('Geheim van ander');
});

it('blokkeert de gebruikersbeheer-pagina voor niet-admins', function () {
    actingAs(User::factory()->create())
        ->get('/admin/users')
        ->assertForbidden();
});

it('staat de gebruikersbeheer-pagina toe voor admins', function () {
    actingAs(User::factory()->admin()->create())
        ->get('/admin/users')
        ->assertOk();
});

it('blokkeert admins voor andermans uren', function () {
    $admin = User::factory()->admin()->create();
    $ander = User::factory()->create();

    $entry = TimeEntry::factory()->for($ander)->create();

    expect($admin->can('view', $entry))->toBeFalse()
        ->and($admin->can('update', $entry))->toBeFalse()
        ->and($admin->can('delete', $entry))->toBeFalse();
});

it('blokkeert gewone gebruikers voor andermans uren', function () {
    $user = User::factory()->create();
    $ander = User::factory()->create();

    $entry = TimeEntry::factory()->for($ander)->create();

    expect($user->can('view', $entry))->toBeFalse()
        ->and($user->can('update', $entry))->toBeFalse()
        ->and($user->can('delete', $entry))->toBeFalse()
        ->and($user->can('view', TimeEntry::factory()->for($user)->make()))->toBeTrue();
});

it('voorkomt dat een admin zichzelf verwijdert', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});
