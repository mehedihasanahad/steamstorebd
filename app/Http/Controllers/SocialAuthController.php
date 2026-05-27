<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        // Save the intended URL before Socialite redirects to Google.
        // The OAuth round-trip can lose session data in some production
        // environments, so we keep a separate backup key.
        Session::put('google_oauth_intended', Session::get('url.intended', route('home')));

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        // Pull the backup intended URL before any session migration happens.
        $intended = Session::pull('google_oauth_intended', route('home'));

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::warning('Google OAuth state/token error', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        try {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Google OAuth user creation failed', [
                'email' => $googleUser->getEmail(),
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('login')->with('error', 'Could not sign you in. Please try again.');
        }

        Auth::login($user, remember: true);

        // Priority order for post-login redirect:
        // 1. url.intended set by the auth middleware (e.g. user was heading to /checkout)
        // 2. Cart has items → send to checkout so the purchase flow continues
        // 3. Pre-OAuth backup ($intended) → falls back to home if no checkout intent existed
        $redirectTo = Session::pull('url.intended')
            ?? (!empty(Session::get('cart')) ? route('checkout') : null)
            ?? $intended;

        return redirect($redirectTo);
    }
}
