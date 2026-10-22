<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSee('action="'.route('register').'"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('login.html')
        ->assertDontSee('signup.html');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration requires valid account details and matching passwords', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response
        ->assertRedirect('/register')
        ->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('registration preserves safe input but never repopulates passwords', function () {
    $this->from('/register')->post('/register', [
        'name' => 'Alex Morgan',
        'email' => 'alex@example.com',
        'password' => 'sensitive-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');

    $this->get('/register')
        ->assertOk()
        ->assertSee('value="Alex Morgan"', false)
        ->assertSee('value="alex@example.com"', false)
        ->assertDontSee('sensitive-password')
        ->assertDontSee('different-password');
});

test('authenticated users are redirected away from the registration screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/register')
        ->assertRedirect(route('dashboard', absolute: false));
});
