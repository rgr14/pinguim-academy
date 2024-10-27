<?php

namespace App\Livewire\Password;

use App\Models\User;
use App\Notifications\PasswordRecoveryNotification;
use Livewire\Component;

class Recovery extends Component
{
    public ?string $email = null;
    public ?string $message = null;

    public function render()
    {
        return view('livewire.password.recovery');
    }

    public function startPasswordRecovery(): void
    {
        $user = User::where('email', $this->email)->first();

        $user?->notify(new PasswordRecoveryNotification());

        $this->message = 'You will receive an email with the password recovery link.';
    }
}