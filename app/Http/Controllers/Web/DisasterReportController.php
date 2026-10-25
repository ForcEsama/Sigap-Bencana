<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DisasterReport;
use App\Services\UserNotificationWriter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class DisasterReportController extends Controller
{
    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request, UserNotificationWriter $notificationWriter): RedirectResponse
    {
        $request->merge([
            'address' => is_string($request->input('address'))
                ? trim($request->input('address'))
                : $request->input('address'),
        ]);

        $validated = $request->validate([
            'disaster_type' => [
                'required',
                'string',
                'in:gempa,banjir,longsor,kebakaran,tsunami,lainnya',
            ],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'decimal:0,7', 'between:-180,180'],
            'description' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'disaster_type.required' => 'Pilih jenis bencana.',
            'disaster_type.in' => 'Jenis bencana yang dipilih tidak valid.',
            'address.required' => 'Alamat atau keterangan lokasi wajib diisi.',
            'address.max' => 'Alamat maksimal 1.000 karakter.',
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.decimal' => 'Latitude maksimal memiliki 7 angka desimal.',
            'latitude.between' => 'Latitude harus berada di antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.decimal' => 'Longitude maksimal memiliki 7 angka desimal.',
            'longitude.between' => 'Longitude harus berada di antara -180 dan 180.',
            'description.required' => 'Deskripsi kejadian wajib diisi.',
            'description.max' => 'Deskripsi maksimal 5.000 karakter.',
            'occurred_at.date' => 'Waktu kejadian tidak valid.',
            'occurred_at.before_or_equal' => 'Waktu kejadian tidak boleh di masa depan.',
            'photo.image' => 'Lampiran harus berupa foto yang valid.',
            'photo.mimes' => 'Format foto yang didukung hanya JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 10 MB.',
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            try {
                $photoPath = $request->file('photo')->store('disaster-reports', 'local');
            } catch (Throwable) {
                return back()->withErrors([
                    'photo' => 'Foto gagal disimpan. Silakan coba lagi.',
                ])->withInput();
            }

            if ($photoPath === false) {
                return back()->withErrors([
                    'photo' => 'Foto gagal disimpan. Silakan coba lagi.',
                ])->withInput();
            }
        }

        $categoryLabels = [
            'gempa' => 'Gempa',
            'banjir' => 'Banjir',
            'longsor' => 'Tanah Longsor',
            'kebakaran' => 'Kebakaran',
            'tsunami' => 'Tsunami',
            'lainnya' => 'Lainnya',
        ];

        $reportTime = filled($validated['occurred_at'] ?? null)
            ? Carbon::parse($validated['occurred_at'], config('app.timezone'))
            : now();

        try {
            $report = DB::transaction(function () use ($request, $validated, $categoryLabels, $reportTime, $photoPath, $notificationWriter): DisasterReport {
                $report = DisasterReport::create([
                    'user_id' => $request->user()->id,
                    'title' => 'Laporan '.$categoryLabels[$validated['disaster_type']].' - '.$reportTime->locale('id')->translatedFormat('d F Y H:i'),
                    'disaster_type' => $validated['disaster_type'],
                    'description' => $validated['description'],
                    'address' => $validated['address'],
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'occurred_at' => $validated['occurred_at'] ?? null,
                    'photo_path' => $photoPath,
                    'verification_status' => 'pending',
                    'handling_status' => 'unhandled',
                    'verified_by' => null,
                    'verified_at' => null,
                ]);

                $notificationWriter->create(
                    $request->user(),
                    'activity',
                    'Laporan bencana berhasil dicatat',
                    'Laporan kejadian #'.$report->id.' berhasil dicatat dan menunggu verifikasi.',
                    'disaster_report',
                    $report->id,
                );

                return $report;
            });
        } catch (Throwable $exception) {
            if ($photoPath !== null) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('reports.create')
            ->with('success', 'Laporan berhasil dikirim dan menunggu verifikasi. Nomor laporan #'.$report->id.'.');
    }
}
