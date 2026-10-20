<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * A fixed bcrypt hash (cost 12) of a throwaway string.
     *
     * Used to equalise the timing of the "unknown account" and "wrong
     * password" branches. Without it the unknown-account path skips the
     * bcrypt comparison entirely and answers ~250 ms faster, which is a
     * usable account-enumeration oracle. The hash is public by design:
     * it protects nothing and only equalises cost.
     */
    private const TIMING_EQUALISER_HASH = '$2y$12$WYiCqaSCzLgF4iRxbdR5VetK6HG17siCsw2AfZRJttznTz1a.mhMG';

    /** Account-scoped attempt budget (independent of the source IP). */
    private const ACCOUNT_MAX_ATTEMPTS = 20;

    /** Account-scoped attempt window, in seconds. */
    private const ACCOUNT_DECAY_SECONDS = 3600;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        // Timing equalisation: when the account does not exist, still pay
        // for one bcrypt comparison against a fixed placeholder so the
        // response time cannot be used to enumerate registered accounts.
        $guard = Auth::guard('web');
        $account = $guard instanceof SessionGuard
            ? $guard->getProvider()->retrieveByCredentials($this->only('email', 'password'))
            : null;

        if ($account === null) {
            Hash::check((string) $this->input('password'), self::TIMING_EQUALISER_HASH);
        }

        if (!Auth::attempt($this->only('email', 'password'), $this->filled('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountKey(), self::ACCOUNT_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)
            && !RateLimiter::tooManyAttempts($this->accountKey(), self::ACCOUNT_MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = min(
            RateLimiter::availableIn($this->throttleKey()),
            RateLimiter::availableIn($this->accountKey()),
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * Per (account, source IP): a single source gets 5 attempts a minute.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('email')) . '|' . $this->ip();
    }

    /**
     * Account-scoped throttle key.
     *
     * The per-IP key alone lets a distributed attacker rotate source IPs
     * for unlimited attempts against one account. This key is not IP
     * bound, so the budget survives IP rotation. It is also not a
     * lockout: the account stays usable, only further password guesses
     * from anyone are throttled.
     *
     * @return string
     */
    public function accountKey()
    {
        return Str::lower((string) $this->input('email'));
    }
}
