<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $verificationRoute = 'verification.verify',
    ) {
        $this->afterCommit();
    }

    /**
     * Build the GCode Forge Studio verification email.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $expiration = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verify your GCode Forge Studio email')
            ->greeting('Welcome to the Forge!')
            ->line('Confirm your email address to activate your workspace and protect your account.')
            ->action('Verify email address', $this->verificationUrl($notifiable))
            ->line("This secure link expires in {$expiration} minutes.")
            ->line('If you did not create this account, you can safely ignore this email.');
    }

    /**
     * Create a temporary signed verification URL.
     *
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            $this->verificationRoute,
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
