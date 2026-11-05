<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $additionalFields = [
            'domicile_address',
            'province',
            'regency_city',
            'district',
            'village',
            'postal_code',
            'date_of_birth',
            'gender',
        ];
        $additionalFieldsRequested = array_intersect(array_keys($request->all()), $additionalFields) !== [];
        $missingAdditionalFields = array_filter(
            $additionalFields,
            static fn (string $field): bool => ! Schema::hasColumn('users', $field),
        );

        if ($additionalFieldsRequested && $missingAdditionalFields !== []) {
            return response()->json([
                'message' => 'Penyimpanan profil tambahan belum tersedia. Migrasi database belum siap.',
            ], 503);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'domicile_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'regency_city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'village' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'gender' => ['sometimes', 'nullable', Rule::in(['laki-laki', 'perempuan'])],
        ]);

        $emailChanged = $validated['email'] !== $user->email;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        foreach ($additionalFields as $field) {
            if (array_key_exists($field, $validated)) {
                $user->{$field} = $validated[$field];
            }
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        $responseFields = ['id', 'name', 'email', 'phone', 'role'];
        foreach ($additionalFields as $field) {
            if (Schema::hasColumn('users', $field)) {
                $responseFields[] = $field;
            }
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->only($responseFields),
        ]);
    }
}
