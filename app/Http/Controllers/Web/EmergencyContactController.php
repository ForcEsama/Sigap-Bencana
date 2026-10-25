<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use Illuminate\View\View;

class EmergencyContactController extends Controller
{
    public function index(): View
    {
        $contacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'category',
                'phone',
                'description',
                'region',
                'address',
                'website_url',
            ]);

        return view('emergency-contacts.index', compact('contacts'));
    }
}
