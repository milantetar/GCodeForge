<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class AuthUserController extends Controller
{
    /**
     * Display Registration Page
     */
    public function create(): Response
    {
        return Inertia::render(('auth/register'));
    }

    /**
     * Display Login Page
     */
    public function login(): Response
    {
        return Inertia::render(('auth/login'));
    }

    /**
     * @var list<string>
     */
    private const SOCIALITE_PROVIDERS = ['github', 'google'];

    /**
     * redirect to the selected social provider
     */
    public function redirectToProvider(string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SOCIALITE_PROVIDERS, true), 404);
        if (! config('services.'.$provider.'.client_id') || ! config('services.'.$provider.'.client_secret') || ! config('services.'.$provider.'.redirect')) {
            return to_route('login')->withErrors(['email' => 'This sign-in provider is not configured yet. Use email and password for now.']);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * callback to the selected social provider
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SOCIALITE_PROVIDERS, true), 404);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'We could not sign you in with that provider. Please try again.']);
        }

        $email = $socialiteUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your social account did not share an email address.']);
        }

        $email = Str::lower(trim($email));
        $providerId = (string) $socialiteUser->getId();

        $user = DB::transaction(function () use ($email, $provider, $providerId, $socialiteUser): User {
            $socialAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerId)
                ->first();

            if ($socialAccount) {
                return $socialAccount->user;
            }

            if (User::withTrashed()->where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'An account already uses this email. Sign in using its existing method.']);
            }
            $user = (function () use ($email, $socialiteUser): User {
                $user = User::create([
                    'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: $email,
                    'email' => $email,
                    'avatar_url' => $socialiteUser->getAvatar(),
                    'email_verified_at' => null,
                    'preferred_theme' => 'dark',
                ]);

                $user->assignRole(Role::findOrCreate('user', 'web'));

                return $user;
            })();

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $providerId,
                'avatar_url' => $socialiteUser->getAvatar(),
            ]);

            if (! $user->avatar_url && $socialiteUser->getAvatar()) {
                $user->forceFill(['avatar_url' => $socialiteUser->getAvatar()])->save();
            }

            return $user;
        });

        if ($user->status !== UserStatus::Active) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This account is not currently active.']);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            request()->session()->put(['login.id' => $user->id, 'login.remember' => true]);

            return redirect()->route('two-factor.login');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
