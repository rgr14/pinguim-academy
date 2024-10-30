<?php

use App\Livewire\Auth\Password;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use function Pest\Laravel\get;

test('need to receive a valid token with a combination with the email and open the page', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(Password\Recovery::class)
        ->set('email', $user->email)
        ->call('startPasswordRecovery');

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) {
            get(route('password.reset') . '?token=' . $notification->token)
                ->assertSuccessful();

            get(route('password.reset') . '?token=' . 'any-token')
                ->assertRedirect(route('login'));

            return true;
        }
    );
});

test('test if is possible to reset the passwird with the given token', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(Password\Recovery::class)
        ->set('email', $user->email)
        ->call('startPasswordRecovery');

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use ($user) {

            Livewire::test(
                Password\Reset::class,
                ['token' => $notification->token, 'email' => $user->email]
            )
                ->set('email_confirmation', $user->email)
                ->set('password', 'new-password')
                ->set('password_confirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

            $user->refresh();

            \PHPUnit\Framework\assertTrue(
                Hash::check('new-password', $user->password),
            );

            return true;
        }
    );
});

test('testing email property', function ($field, $value, $rule) {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(Password\Recovery::class)
        ->set('email', $user->email)
        ->call('startPasswordRecovery');

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use ($user, $field, $value, $rule) {
            Livewire::test(Password\Reset::class, ['token' => $notification->token, 'email' => $user->email])
                ->set($field, $value)
                ->call('updatePassword')
                ->assertHasErrors([$field => $rule]);

            return true;
        }
    );
})->with([
    'email:required' => ['field' => 'email', 'value' => '', 'rule' => 'required'],
    'email:confirmed' => ['field' => 'email', 'value' => 'email@email.com', 'rule' => 'confirmed'],
    'email:email' => ['field' => 'email', 'value' => 'not-an-email', 'rule' => 'email'],
    'password:required' => ['field' => 'password', 'value' => '', 'rule' => 'required'],
    'password:confirmed' => ['field' => 'password', 'value' => 'any-password', 'rule' => 'confirmed'],
]);
