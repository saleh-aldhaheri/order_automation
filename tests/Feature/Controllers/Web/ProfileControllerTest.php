<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createUserAs();
});

describe('edit', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('auth.login'));
    });

    it('redirects unverified users to the verification notice', function () {
        $unverified = User::factory()->unverified()->create();

        actingAs($unverified)
            ->get(route('profile.edit'))
            ->assertRedirect(route('verification.notice'));
    });

    it('returns 200 and renders the profile edit view for verified users', function () {
        actingAs($this->user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertViewIs('profile.edit')
            ->assertViewHas('user', fn ($u) => $u->is($this->user));
    });
});

describe('update', function () {
    it('updates the profile and redirects back to the edit page', function () {
        $newEmail = 'profile-'.uniqid('', true).'@example.com';

        actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => 'Updated Profile Name',
                'email' => $newEmail,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', __('Profile updated.'));

        $this->user->refresh();

        expect($this->user->name)->toBe('Updated Profile Name')
            ->and($this->user->email)->toBe($newEmail);
    });

    it('updates the password when the current password is correct', function () {
        $newPassword = 'Brand-new-pass-99';

        actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'current_password' => '123Password',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', __('Profile updated.'));

        $this->user->refresh();

        expect(Hash::check($newPassword, $this->user->password))->toBeTrue();
    });

    it('redirects guests to the login page', function () {
        $this->put(route('profile.update'), [
            'name' => 'X',
            'email' => 'x@example.com',
        ])
            ->assertRedirect(route('auth.login'));
    });

    it('redirects back with validation errors when the payload is invalid', function () {
        actingAs($this->user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => '',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['name', 'email']);
    });

    it('redirects back when the email belongs to another user', function () {
        $other = User::factory()->create();

        actingAs($this->user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $this->user->name,
                'email' => $other->email,
            ])
            ->assertSessionHasErrors('email');
    });

    it('redirects back when password is set without the current password', function () {
        actingAs($this->user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'password' => 'Some-new-pass-88',
                'password_confirmation' => 'Some-new-pass-88',
            ])
            ->assertSessionHasErrors('current_password');
    });

    it('redirects back when the current password is wrong', function () {
        actingAs($this->user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'current_password' => 'wrong-password',
                'password' => 'Some-new-pass-88',
                'password_confirmation' => 'Some-new-pass-88',
            ])
            ->assertSessionHasErrors('current_password');
    });
});
