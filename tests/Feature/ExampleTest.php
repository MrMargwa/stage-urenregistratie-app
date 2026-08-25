<?php

test('de homepage stuurt gasten door naar de login', function () {
    $this->get('/')->assertRedirect('/admin/login');
});
