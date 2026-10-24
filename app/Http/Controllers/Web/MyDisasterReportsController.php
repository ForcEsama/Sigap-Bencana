<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyDisasterReportsController extends Controller
{
    public function index(Request $request): View
    {
        $reports = $request->user()
            ->disasterReports()
            ->select([
                'id',
                'title',
                'disaster_type',
                'address',
                'verification_status',
                'handling_status',
                'created_at',
            ])
            ->latest()
            ->paginate(10);

        return view('reports.index', compact('reports'));
    }

    public function show(Request $request, int $reportId): View
    {
        $report = $request->user()
            ->disasterReports()
            ->select([
                'id',
                'title',
                'disaster_type',
                'description',
                'address',
                'latitude',
                'longitude',
                'occurred_at',
                'verification_status',
                'handling_status',
                'created_at',
            ])
            ->whereKey($reportId)
            ->firstOrFail();

        return view('reports.show', compact('report'));
    }
}
