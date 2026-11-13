<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Turnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Determine whether Turnstile verification is configured.
     */
    public static function isEnabled(): bool
    {
        return filled(config('services.turnstile.secret_key'));
    }

    /**
     * Validate the Cloudflare Turnstile token.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isEnabled()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Selesaikan verifikasi keamanan terlebih dahulu.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::VERIFY_URL, [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification request failed.', [
                'error' => $exception->getMessage(),
            ]);

            $fail('Verifikasi keamanan tidak dapat diproses. Coba lagi.');

            return;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
