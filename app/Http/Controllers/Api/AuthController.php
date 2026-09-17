<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const CODE_EXPIRATION_MINUTES = 10;

    private const MAX_VERIFICATION_ATTEMPTS = 5;

    private const RESEND_DELAY_SECONDS = 60;

    public function register(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+[1-9]\d{7,14}$/',
            ],
        ]);

        $name = trim(
            $validated['name']
        );

        $email = strtolower(
            trim(
                $validated['email']
            )
        );

        $phoneNumber =
            $this->normalizePhoneNumber(
                $validated['phone_number']
            );

        $this->checkRegistrationRateLimit(
            $request,
            $email
        );

        if (
            User::where(
                'email',
                $email
            )->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'This email address is already registered.',
                ],
            ]);
        }

        if (
            User::where(
                'phone_number',
                $phoneNumber
            )->exists()
        ) {
            throw ValidationException::withMessages([
                'phone_number' => [
                    'This phone number is already registered.',
                ],
            ]);
        }

        $existingPending =
            PendingRegistration::where(
                'email',
                $email
            )->first();

        if ($existingPending) {
            if (
                $existingPending->code_sent_at
                    ->addSeconds(
                        self::RESEND_DELAY_SECONDS
                    )
                    ->isFuture()
            ) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Please wait before requesting another verification code.',
                    ],
                ]);
            }

            $existingPending->delete();
        }

        $code = $this->generateVerificationCode();

        $pending =
            PendingRegistration::create([
                'name' => $name,

                'email' => $email,

                'phone_number' =>
                    $phoneNumber,

                'verification_code_hash' =>
                    Hash::make($code),

                'attempts' => 0,

                'code_sent_at' => now(),

                'expires_at' =>
                    now()->addMinutes(
                        self::CODE_EXPIRATION_MINUTES
                    ),
            ]);

        try {
            Mail::to($email)
                ->send(
                    new EmailVerificationCodeMail(
                        $name,
                        $code
                    )
                );
        } catch (\Throwable $exception) {
            $pending->delete();

            report($exception);

            return response()->json([
                'message' =>
                    'We could not send the verification email. Please try again.',
            ], 500);
        }

        RateLimiter::hit(
            $this->registrationRateLimitKey(
                $request,
                $email
            ),
            60
        );

        return response()->json([
            'message' =>
                'A verification code has been sent to your email.',
            'email' => $email,
            'expires_in_minutes' =>
                self::CODE_EXPIRATION_MINUTES,
        ], 200);
    }

    public function verifyEmail(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        $email = strtolower(
            trim(
                $validated['email']
            )
        );

        $code = trim(
            $validated['code']
        );

        $pending =
            PendingRegistration::where(
                'email',
                $email
            )->first();

        if (!$pending) {
            throw ValidationException::withMessages([
                'code' => [
                    'This verification request is no longer available. Please register again.',
                ],
            ]);
        }

        if (
            $pending->expires_at->isPast()
        ) {
            $pending->delete();

            throw ValidationException::withMessages([
                'code' => [
                    'This verification code has expired. Please request a new code.',
                ],
            ]);
        }

        if (
            $pending->attempts >=
            self::MAX_VERIFICATION_ATTEMPTS
        ) {
            $pending->delete();

            throw ValidationException::withMessages([
                'code' => [
                    'Too many incorrect attempts. Please register again.',
                ],
            ]);
        }

        if (
            !Hash::check(
                $code,
                $pending->verification_code_hash
            )
        ) {
            $pending->increment(
                'attempts'
            );

            throw ValidationException::withMessages([
                'code' => [
                    'The verification code is incorrect.',
                ],
            ]);
        }

        if (
            User::where(
                'email',
                $email
            )->exists()
        ) {
            $pending->delete();

            throw ValidationException::withMessages([
                'email' => [
                    'This email address is already registered.',
                ],
            ]);
        }

        if (
            User::where(
                'phone_number',
                $pending->phone_number
            )->exists()
        ) {
            $pending->delete();

            throw ValidationException::withMessages([
                'phone_number' => [
                    'This phone number is already registered.',
                ],
            ]);
        }

        $user = User::create([
            'name' =>
                $pending->name,

            'email' =>
                $pending->email,

            'phone_number' =>
                $pending->phone_number,

            'email_verified_at' =>
                now(),
        ]);

        $token = $user
            ->createToken(
                'webs-people-mobile'
            )
            ->plainTextToken;

        $pending->delete();

        return response()->json([
            'message' =>
                'Registration completed successfully.',

            'token' =>
                $token,

            'user' => $user,
        ], 201);
    }

    public function resendVerificationCode(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ]);

        $email = strtolower(
            trim(
                $validated['email']
            )
        );

        $pending =
            PendingRegistration::where(
                'email',
                $email
            )->first();

        if (!$pending) {
            throw ValidationException::withMessages([
                'email' => [
                    'No pending registration was found. Please register again.',
                ],
            ]);
        }

        if (
            $pending->code_sent_at
                ->addSeconds(
                    self::RESEND_DELAY_SECONDS
                )
                ->isFuture()
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Please wait before requesting another verification code.',
                ],
            ]);
        }

        $code = $this->generateVerificationCode();

        $pending->update([
            'verification_code_hash' =>
                Hash::make($code),

            'attempts' => 0,

            'code_sent_at' => now(),

            'expires_at' =>
                now()->addMinutes(
                    self::CODE_EXPIRATION_MINUTES
                ),
        ]);

        try {
            Mail::to($pending->email)
                ->send(
                    new EmailVerificationCodeMail(
                        $pending->name,
                        $code
                    )
                );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' =>
                    'We could not send the verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' =>
                'A new verification code has been sent.',
            'expires_in_minutes' =>
                self::CODE_EXPIRATION_MINUTES,
        ]);
    }

    public function me(
        Request $request
    ): JsonResponse {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    private function generateVerificationCode(): string
    {
        return str_pad(
            (string) random_int(
                0,
                999999
            ),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function normalizePhoneNumber(
        string $phoneNumber
    ): string {
        $phoneNumber = preg_replace(
            '/[\s\-\(\)]/',
            '',
            trim($phoneNumber)
        );

        if (
            str_starts_with(
                $phoneNumber,
                '00'
            )
        ) {
            $phoneNumber =
                '+' .
                substr(
                    $phoneNumber,
                    2
                );
        }

        return $phoneNumber;
    }

    private function registrationRateLimitKey(
        Request $request,
        string $email
    ): string {
        return 'people-registration:' .
            strtolower($email) .
            '|' .
            $request->ip();
    }

    private function checkRegistrationRateLimit(
        Request $request,
        string $email
    ): void {
        $key =
            $this->registrationRateLimitKey(
                $request,
                $email
            );

        if (
            RateLimiter::tooManyAttempts(
                $key,
                5
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Too many registration attempts. Please try again later.',
                ],
            ]);
        }
    }
}