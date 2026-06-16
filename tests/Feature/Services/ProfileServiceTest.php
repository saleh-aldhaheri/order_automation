<?php

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->profileService = new ProfileService;
});

describe('updateProfile', function () {
    it('updates the user name and email', function () {
        $user = User::factory()->create([
            'name' => 'Before',
            'email' => 'before@example.com',
        ]);

        $this->profileService->updateProfile($user, 'After', 'after@example.com');

        $user->refresh();

        expect($user->name)->toBe('After')
            ->and($user->email)->toBe('after@example.com');
    });

    it('updates the password when a non-empty plain password is given', function () {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->profileService->updateProfile(
            $user,
            $user->name,
            $user->email,
            'new-secret-password-123',
        );

        $user->refresh();

        expect($user->password)->not->toBe($oldHash)
            ->and(Hash::check('new-secret-password-123', $user->password))->toBeTrue();
    });

    it('does not change the password when plain password is null', function () {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->profileService->updateProfile($user, 'New Name', $user->email, null);

        $user->refresh();

        expect($user->password)->toBe($oldHash)
            ->and($user->name)->toBe('New Name');
    });

    it('does not change the password when plain password is an empty string', function () {
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->profileService->updateProfile($user, $user->name, $user->email, '');

        $user->refresh();

        expect($user->password)->toBe($oldHash);
    });
});
