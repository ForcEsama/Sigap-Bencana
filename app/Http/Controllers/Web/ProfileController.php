<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->input('form_type') === 'password') {
            return $this->updatePassword($request);
        }

        return $this->updateProfile($request);
    }

    private function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profileFields = [
            'domicile_address',
            'province',
            'regency_city',
            'district',
            'village',
            'postal_code',
            'date_of_birth',
            'gender',
        ];
        $missingProfileFields = array_filter(
            $profileFields,
            static fn (string $column): bool => ! Schema::hasColumn('users', $column),
        );

        if ($missingProfileFields !== []) {
            return redirect()
                ->route('profile.edit')
                ->withErrors([
                    'profile_schema' => 'Kolom profil tambahan belum tersedia pada database. Perubahan belum disimpan; jalankan migrasi database setelah masalah MySQL diperbaiki.',
                ], 'profile')
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']));
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'domicile_address' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'regency_city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('profile.edit')
                ->withErrors($validator, 'profile')
                ->withInput($request->only([
                    'name',
                    'email',
                    'phone',
                    'domicile_address',
                    'province',
                    'regency_city',
                    'district',
                    'village',
                    'postal_code',
                    'date_of_birth',
                    'gender',
                ]));
        }

        $validated = $validator->validated();
        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->domicile_address = $validated['domicile_address'] ?? null;
        $user->province = $validated['province'] ?? null;
        $user->regency_city = $validated['regency_city'] ?? null;
        $user->district = $validated['district'] ?? null;
        $user->village = $validated['village'] ?? null;
        $user->postal_code = $validated['postal_code'] ?? null;
        $user->date_of_birth = $validated['date_of_birth'] ?? null;
        $user->gender = $validated['gender'] ?? null;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    private function updatePassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('profile.edit')
                ->withErrors($validator, 'password')
                ->withInput($request->only('name', 'email'));
        }

        $validated = $validator->validated();
        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return redirect()
                ->route('profile.edit')
                ->withErrors([
                    'current_password' => 'Kata sandi saat ini tidak sesuai.',
                ], 'password')
                ->withInput($request->only('name', 'email'));
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
