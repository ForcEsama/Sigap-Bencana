<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationFeedService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationFeedService $feed): View
    {
        $validated = $request->validate(['filter' => ['nullable', 'in:all,warning,information,activity']]);
        $filter = $validated['filter'] ?? 'all';
        $user = $request->user();
        $storageAvailable = Schema::hasTable('notifications');
        $stored = $storageAvailable
            ? $user->notifications()->latest('created_at')->limit(100)->get()
            : collect();
        $built = $feed->build($user, $stored);
        $items = array_values(array_filter($built['items'], static fn (array $item): bool => $filter === 'all' || $item['category'] === $filter));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('notifications.index', [
            'notifications' => $paginator,
            'filters' => NotificationFeedService::FILTERS,
            'activeFilter' => $filter,
            'unreadCount' => $storageAvailable ? $user->notifications()->whereNull('read_at')->count() : 0,
            'storageAvailable' => $storageAvailable,
            'feedStates' => $built['feed_states'],
        ]);
    }

    public function markRead(Request $request, int $notification): RedirectResponse
    {
        abort_unless(Schema::hasTable('notifications'), 503, 'Penyimpanan notifikasi belum tersedia.');
        $record = $request->user()->notifications()->findOrFail($notification);
        if ($record->read_at === null) {
            $record->forceFill(['read_at' => now()])->save();
        }

        return $this->returnToFilter($request);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('notifications'), 503, 'Penyimpanan notifikasi belum tersedia.');
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->returnToFilter($request);
    }

    private function returnToFilter(Request $request): RedirectResponse
    {
        $filter = $request->validate(['filter' => ['nullable', 'in:all,warning,information,activity']])['filter'] ?? 'all';

        return redirect()->route('notifications.index', ['filter' => $filter]);
    }
}
