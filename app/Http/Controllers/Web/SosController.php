<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Services\UserNotificationWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SosController extends Controller
{
    private const DISASTER_TYPES = [
        'gempa' => 'Gempa Bumi',
        'banjir' => 'Banjir',
        'longsor' => 'Longsor',
        'kebakaran' => 'Kebakaran',
        'tsunami' => 'Tsunami',
    ];

    public function create(Request $request): View
    {
        $request->session()->forget(['sos.disaster_type', 'sos.submission_key']);

        return view('sos.create', ['disasterTypes' => self::DISASTER_TYPES]);
    }

    public function confirmSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'disaster_type' => ['required', 'string', 'in:'.implode(',', array_keys(self::DISASTER_TYPES))],
        ]);

        $request->session()->put('sos.disaster_type', $validated['disaster_type']);
        $request->session()->forget('sos.submission_key');

        return redirect()->route('sos.confirm');
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        $disasterType = $request->session()->get('sos.disaster_type');
        if (! is_string($disasterType) || ! array_key_exists($disasterType, self::DISASTER_TYPES)) {
            return redirect()->route('sos.create')->with('error', 'Pilih jenis keadaan darurat terlebih dahulu.');
        }

        $submissionKey = $request->session()->get('sos.submission_key');
        if (! is_string($submissionKey)) {
            $submissionKey = (string) Str::uuid();
            $request->session()->put('sos.submission_key', $submissionKey);
        }

        return view('sos.confirm', [
            'disasterType' => $disasterType,
            'disasterLabel' => self::DISASTER_TYPES[$disasterType],
            'submissionKey' => $submissionKey,
        ]);
    }

    public function store(Request $request, UserNotificationWriter $notificationWriter): RedirectResponse|JsonResponse
    {
        $disasterType = $request->session()->get('sos.disaster_type');
        $submissionKey = $request->session()->get('sos.submission_key');

        if (! is_string($disasterType) || ! array_key_exists($disasterType, self::DISASTER_TYPES)
            || ! is_string($submissionKey)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi pemilihan keadaan darurat berakhir. Silakan mulai kembali.',
                    'redirect_url' => route('sos.create'),
                ], 409);
            }

            return redirect()->route('sos.create')->with('error', 'Sesi pemilihan keadaan darurat berakhir. Silakan mulai kembali.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'submission_key' => ['required', 'uuid'],
        ]);

        if (! hash_equals($submissionKey, $validated['submission_key'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Formulir ini sudah kedaluwarsa. Muat ulang tahap konfirmasi lokasi.',
                ], 409);
            }

            return back()->withErrors(['submission_key' => 'Formulir ini sudah kedaluwarsa. Muat ulang tahap konfirmasi lokasi.']);
        }

        try {
            $sosRequest = Cache::lock('sos-submit:'.$request->user()->id.':'.$submissionKey, 10)
                ->block(5, function () use ($request, $validated, $disasterType, $submissionKey, $notificationWriter): SosRequest {
                    $existingId = $request->session()->get('sos.submissions.'.$submissionKey);
                    if (is_numeric($existingId)) {
                        return $request->user()->sosRequests()->findOrFail((int) $existingId);
                    }

                    $sosRequest = DB::transaction(function () use ($request, $validated, $disasterType, $notificationWriter): SosRequest {
                        $sosRequest = $request->user()->sosRequests()->create([
                            'disaster_type' => $disasterType,
                            'description' => null,
                            'latitude' => $validated['latitude'],
                            'longitude' => $validated['longitude'],
                            'address' => isset($validated['address']) && trim($validated['address']) !== ''
                                ? trim($validated['address'])
                                : null,
                            'urgency_level' => 'high',
                            'status' => 'sent',
                            'requested_at' => now(),
                        ]);

                        $sosRequest->histories()->create([
                            'actor_id' => $request->user()->id,
                            'old_status' => null,
                            'new_status' => 'sent',
                            'notes' => 'Permintaan disimpan melalui aplikasi web.',
                        ]);

                        $notificationWriter->create(
                            $request->user(),
                            'activity',
                            'Permintaan SOS tercatat di aplikasi',
                            'Permintaan bantuan SOS #'.$sosRequest->id.' berhasil dicatat. Ini tidak menyatakan petugas telah menerima permintaan.',
                            'sos_request',
                            $sosRequest->id,
                        );

                        return $sosRequest;
                    });

                    $request->session()->put('sos.submissions.'.$submissionKey, $sosRequest->id);

                    return $sosRequest;
                });
        } catch (Throwable $exception) {
            Log::error('Gagal menyimpan permintaan SOS.', [
                'user_id' => $request->user()->id,
                'exception_class' => $exception::class,
            ]);

            $message = 'Permintaan belum tersimpan. Periksa koneksi lalu coba kirim kembali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return back()->withInput()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => route('sos.sent', $sosRequest->id),
            ]);
        }

        return redirect()->route('sos.sent', $sosRequest->id);
    }

    public function sent(Request $request, int $sosRequestId): View
    {
        $sosRequest = $request->user()->sosRequests()->with('histories')->findOrFail($sosRequestId);

        return view('sos.sent', [
            'sosRequest' => $sosRequest,
            'requestNumber' => $this->requestNumber($sosRequest),
            'disasterLabel' => $this->disasterLabel($sosRequest->disaster_type),
        ]);
    }

    public function status(Request $request, int $sosRequestId): View
    {
        $sosRequest = $request->user()->sosRequests()
            ->with(['histories' => fn ($query) => $query->orderBy('created_at'), 'assignedOfficer'])
            ->findOrFail($sosRequestId);

        return view('sos.status', [
            'sosRequest' => $sosRequest,
            'requestNumber' => $this->requestNumber($sosRequest),
            'disasterLabel' => $this->disasterLabel($sosRequest->disaster_type),
        ]);
    }

    private function requestNumber(SosRequest $sosRequest): string
    {
        return 'SOS-'.str_pad((string) $sosRequest->getKey(), 6, '0', STR_PAD_LEFT);
    }

    private function disasterLabel(string $disasterType): string
    {
        if ($disasterType === 'gempa_bumi') {
            return 'Gempa Bumi';
        }

        return self::DISASTER_TYPES[$disasterType] ?? $disasterType;
    }
}
