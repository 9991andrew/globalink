<?php

namespace App\Http\Requests\Auth;

use App\Models\ManagementUser;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
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

        // Check if the password is null and sha1 is filled in. If the password is correct, upgrade to the new password format.
        // This code (and the password_old_sha1 column in the management_users table) should be removed after all are updated.
        $mu = ManagementUser::where('name', $this->request->get('name'))->first();
        if (isset($mu) && strlen($mu->password_old_sha1) && is_null($mu->password)) {
            if ( hash('sha1', $this->request->get('password')) == $mu->password_old_sha1 ) {
                // If the password matches the old hash, let's upgrade it to the new format.
                $mu->password = Hash::make($this->request->get('password'));
                $mu->password_old_sha1 = null;
                $mu->save();
            }
        }
        if (! Auth::attempt($this->only('name', 'password'), $this->filled('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'name' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // If authentication is successful, we set the last login time.
        $mu->last_login_at = now();
        $mu->save();
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'name' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('name')).'|'.$this->ip();
    }
}
