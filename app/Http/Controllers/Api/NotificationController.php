<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\SosRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['sometimes', 'string', 'in:Peringatan,Informasi,Aktivitas'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $notifications = $request->user()
            ->notifications()
            ->when(isset($filters['category']), function ($query) use ($filters): void {
                $query->where('type', $filters['category']);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));

        $relatedSosIds = $notifications->getCollection()
            ->where('related_type', 'sos_request')
            ->pluck('related_id')
            ->filter()
            ->unique();
        $relatedSosRequests = SosRequest::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($relatedSosIds)
            ->with(['histories.actor'])
            ->get()
            ->keyBy('id');

        $notifications->getCollection()->each(function (Notification $notification) use ($relatedSosRequests): void {
            $notification->setRelation(
                'relatedSosRequest',
                $notification->related_type === 'sos_request'
                    ? $relatedSosRequests->get($notification->related_id)
                    : null,
            );
        });

        return NotificationResource::collection($notifications);
    }

    public function markRead(Request $request, int $id): NotificationResource
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $relatedSosRequest = null;
        if ($notification->related_type === 'sos_request') {
            $relatedSosRequest = SosRequest::query()
                ->where('user_id', $request->user()->id)
                ->with(['histories.actor'])
                ->find($notification->related_id);
        }
        $notification->setRelation('relatedSosRequest', $relatedSosRequest);

        return new NotificationResource($notification);
    }
}
