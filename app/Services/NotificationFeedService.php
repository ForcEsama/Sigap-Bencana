<?php

namespace App\Services;

use App\Models\IncidentHistory;
use App\Models\News;
use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationFeedService
{
    public const FILTERS = [
        'all' => 'Semua',
        'warning' => 'Peringatan',
        'information' => 'Informasi',
        'activity' => 'Aktivitas',
    ];

    public function __construct(
        private readonly BmkgNowcastService $warnings,
        private readonly BmkgEarthquakeService $earthquakes,
        private readonly BnpbNewsService $bnpbNews,
    ) {}

    public function build(User $user, Collection $storedNotifications): array
    {
        $items = [];
        $historyIds = $storedNotifications
            ->where('related_type', 'incident_history')
            ->pluck('related_id')->filter()->unique()->values();
        $historyTargets = $this->historyTargets($user, $historyIds);

        foreach ($storedNotifications as $notification) {
            $category = $this->storedCategory($notification->type);
            if ($category === null) {
                continue;
            }
            $items[] = [
                'key' => 'notification:'.$notification->id,
                'notification_id' => $notification->id,
                'category' => $category,
                'title' => $notification->title,
                'message' => $notification->message,
                'created_at' => $notification->created_at,
                'source' => 'SIGAP BENCANA',
                'source_url' => null,
                'target_url' => $this->storedTarget($user, $notification, $historyTargets),
                'read_at' => $notification->read_at,
                'persistent' => true,
                'source_status' => 'available',
                'expires_at' => null,
            ];
        }

        $items = [...$items, ...$this->legacyActivities($user, $storedNotifications)];

        $feedStates = [];
        foreach ($this->externalItems($feedStates) as $item) {
            $items[] = $item;
        }

        usort($items, static function (array $a, array $b): int {
            return ($b['created_at']?->getTimestamp() ?? 0) <=> ($a['created_at']?->getTimestamp() ?? 0);
        });

        return ['items' => $items, 'feed_states' => $feedStates];
    }

    private function storedCategory(string $type): ?string
    {
        return in_array($type, ['warning', 'information', 'activity'], true) ? $type : null;
    }

    private function storedTarget(User $user, Notification $notification, Collection $historyTargets): ?string
    {
        $id = (int) $notification->related_id;
        if ($id <= 0) {
            return null;
        }

        return match ($notification->related_type) {
            'disaster_report' => route('reports.show', $id),
            'sos_request' => route('sos.status', $id),
            'incident_history' => $this->historyTargetUrl($historyTargets->get($id)),
            default => null,
        };
    }

    private function historyTargets(User $user, Collection $ids): Collection
    {
        if ($ids->isEmpty() || ! Schema::hasTable('incident_histories')) {
            return collect();
        }

        return IncidentHistory::query()
            ->whereIn('id', $ids)
            ->with(['disasterReport:id,user_id', 'sosRequest:id,user_id'])
            ->get()
            ->filter(fn (IncidentHistory $history): bool => $this->historyBelongsTo($history, $user))
            ->keyBy('id');
    }

    private function historyTargetUrl(?IncidentHistory $history): ?string
    {
        if (! $history) {
            return null;
        }
        if ($history->disasterReport) {
            return route('reports.show', $history->disasterReport->id);
        }
        if ($history->sosRequest) {
            return route('sos.status', $history->sosRequest->id);
        }

        return null;
    }

    private function historyBelongsTo(IncidentHistory $history, User $user): bool
    {
        return (int) ($history->disasterReport?->user_id ?? 0) === (int) $user->id
            || (int) ($history->sosRequest?->user_id ?? 0) === (int) $user->id;
    }

    private function legacyActivities(User $user, Collection $storedNotifications): array
    {
        $candidates = [];
        $reportIds = collect();
        $sosIds = collect();
        $historyIds = collect();

        if (Schema::hasTable('disaster_reports')) {
            $reports = $user->disasterReports()
                ->latest('created_at')->limit(20)
                ->get(['id', 'title', 'verification_status', 'handling_status', 'created_at']);
            foreach ($reports as $report) {
                $reportIds->push($report->id);
                $candidates[] = $this->legacyItem(
                    'disaster_report:'.$report->id,
                    'disaster_report',
                    $report->id,
                    'Laporan bencana dicatat',
                    'Laporan #'.$report->id.' dibuat dari akun Anda.',
                    $report->created_at,
                    route('reports.show', $report->id),
                );
            }
        }

        if (Schema::hasTable('sos_requests')) {
            $requests = $user->sosRequests()
                ->latest('created_at')->limit(20)
                ->get(['id', 'disaster_type', 'status', 'created_at', 'requested_at']);
            foreach ($requests as $sos) {
                $sosIds->push($sos->id);
                $status = trim((string) $sos->status);
                $candidates[] = $this->legacyItem(
                    'sos_request:'.$sos->id,
                    'sos_request',
                    $sos->id,
                    'Permintaan SOS tercatat di aplikasi',
                    'Permintaan bantuan tercatat dengan status “'.($status !== '' ? $status : 'tidak tersedia').'”. Hal ini tidak menyatakan petugas telah menerimanya.',
                    $sos->requested_at ?: $sos->created_at,
                    route('sos.status', $sos->id),
                );
            }
        }

        if (Schema::hasTable('incident_histories') && (Schema::hasTable('disaster_reports') || Schema::hasTable('sos_requests'))) {
            $histories = IncidentHistory::query()
                ->whereNotNull('old_status')
                ->where(function ($query) use ($user): void {
                    $query->whereHas('disasterReport', fn ($report) => $report->where('user_id', $user->id))
                        ->orWhereHas('sosRequest', fn ($sos) => $sos->where('user_id', $user->id));
                })
                ->with(['disasterReport:id,user_id', 'sosRequest:id,user_id'])
                ->latest('created_at')->limit(40)->get();

            foreach ($histories as $history) {
                $historyIds->push($history->id);
                $relatedUrl = $this->historyTargetUrl($history);
                $subject = $history->disasterReport ? 'laporan bencana' : 'permintaan SOS';
                $candidates[] = $this->legacyItem(
                    'incident_history:'.$history->id,
                    'incident_history',
                    $history->id,
                    'Pembaruan status '.$subject,
                    'Status berubah dari “'.$history->old_status.'” menjadi “'.$history->new_status.'”.',
                    $history->created_at,
                    $relatedUrl,
                );
            }
        }

        $known = $this->knownSourceKeys($user, $reportIds, $sosIds, $historyIds);
        $storedKeys = $storedNotifications->map(fn (Notification $notification): string => $notification->related_type.':'.$notification->related_id)->all();

        return array_values(array_filter($candidates, static function (array $item) use ($known, $storedKeys): bool {
            $key = $item['source_key'];

            return ! in_array($key, $known, true) && ! in_array($key, $storedKeys, true);
        }));
    }

    private function knownSourceKeys(User $user, Collection $reportIds, Collection $sosIds, Collection $historyIds): array
    {
        if (! Schema::hasTable('notifications') || ($reportIds->isEmpty() && $sosIds->isEmpty() && $historyIds->isEmpty())) {
            return [];
        }
        $query = $user->notifications()->select(['related_type', 'related_id'])->where(function ($builder) use ($reportIds, $sosIds, $historyIds): void {
            if ($reportIds->isNotEmpty()) {
                $builder->orWhere(fn ($q) => $q->where('related_type', 'disaster_report')->whereIn('related_id', $reportIds));
            }
            if ($sosIds->isNotEmpty()) {
                $builder->orWhere(fn ($q) => $q->where('related_type', 'sos_request')->whereIn('related_id', $sosIds));
            }
            if ($historyIds->isNotEmpty()) {
                $builder->orWhere(fn ($q) => $q->where('related_type', 'incident_history')->whereIn('related_id', $historyIds));
            }
        });

        return $query->get()->map(fn ($row): string => $row->related_type.':'.$row->related_id)->all();
    }

    private function legacyItem(string $sourceKey, string $relatedType, int $relatedId, string $title, string $message, mixed $createdAt, ?string $target): array
    {
        return [
            'key' => 'history:'.$sourceKey,
            'source_key' => $sourceKey,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'notification_id' => null,
            'category' => 'activity',
            'title' => $title,
            'message' => $message,
            'created_at' => $createdAt ? CarbonImmutable::parse($createdAt) : null,
            'source' => 'Aktivitas akun',
            'source_url' => null,
            'target_url' => $target,
            'read_at' => null,
            'persistent' => false,
            'source_status' => 'available',
            'expires_at' => null,
        ];
    }

    private function externalItems(array &$feedStates): array
    {
        $items = [];
        try {
            $warningFeed = $this->warnings->warnings();
        } catch (\Throwable) {
            $warningFeed = ['status' => 'unavailable', 'items' => []];
        }
        $feedStates[] = $this->feedState('Peringatan BMKG', $warningFeed);
        foreach (array_slice($warningFeed['items'] ?? [], 0, 20) as $warning) {
            $url = $this->allowedUrl($warning['source_url'] ?? null, ['www.bmkg.go.id']);
            if ($url === null || empty($warning['title'])) {
                continue;
            }
            $items[] = $this->externalItem(
                'bmkg-warning:'.hash('sha256', $url), 'warning', $warning['title'],
                trim((string) ($warning['summary'] ?? '')).' '.trim((string) ($warning['region'] ?? '')),
                $warning['published_at'] ?? $warning['effective'] ?? null, 'BMKG', $url,
                $warningFeed['status'] ?? 'unavailable', $warningFeed['fetched_at'] ?? null,
                $warning['expires'] ?? null,
            );
        }

        try {
            $earthquakeFeed = $this->earthquakes->earthquakes();
        } catch (\Throwable) {
            $earthquakeFeed = ['status' => 'unavailable', 'items' => []];
        }
        $feedStates[] = $this->feedState('Informasi gempa BMKG', $earthquakeFeed);
        foreach (array_slice($earthquakeFeed['items'] ?? [], 0, 10) as $earthquake) {
            $url = $this->allowedUrl($earthquake['source_url'] ?? null, ['data.bmkg.go.id']);
            if ($url === null || empty($earthquake['occurred_at']) || empty($earthquake['event_key'])) {
                continue;
            }
            $items[] = $this->externalItem(
                'bmkg-earthquake:'.hash('sha256', $earthquake['event_key']), 'information',
                trim(($earthquake['title'] ?? 'Gempa bumi').' — '.($earthquake['region'] ?? '')),
                trim((string) ($earthquake['summary'] ?? '')),
                $earthquake['occurred_at'], 'BMKG', $url,
                $earthquakeFeed['status'] ?? 'unavailable', $earthquakeFeed['fetched_at'] ?? null,
            );
        }

        try {
            $newsFeed = $this->bnpbNews->news();
        } catch (\Throwable) {
            $newsFeed = ['status' => 'unavailable', 'items' => []];
        }
        $feedStates[] = $this->feedState('Berita BNPB', $newsFeed);
        foreach (array_slice($newsFeed['items'] ?? [], 0, 10) as $article) {
            $url = $this->allowedUrl($article['source_url'] ?? null, ['www.bnpb.go.id']);
            if ($url === null || empty($article['published_at']) || empty($article['title'])) {
                continue;
            }
            $items[] = $this->externalItem(
                'bnpb-news:'.hash('sha256', $url), 'information', $article['title'],
                trim((string) ($article['summary'] ?? 'Ringkasan tidak tersedia; buka sumber untuk informasi lengkap.')),
                $article['published_at'], 'BNPB', $url,
                $newsFeed['status'] ?? 'unavailable', $newsFeed['fetched_at'] ?? null,
            );
        }

        if (Schema::hasTable('news')) {
            $localNews = News::query()->where('status', 'published')->whereNotNull('published_at')
                ->where('published_at', '<=', now())->latest('published_at')->limit(10)->get(['id', 'slug', 'title', 'excerpt', 'source', 'source_url', 'published_at']);
            foreach ($localNews as $article) {
                $url = $this->allowedUrl($article->source_url, null) ?: route('disaster-alerts.news.show', $article->slug);
                $items[] = $this->externalItem(
                    'local-news:'.$article->id, 'information', $article->title,
                    $article->excerpt ?: 'Buka halaman artikel untuk membaca ringkasan dan sumber.',
                    $article->published_at, $article->source ?: 'Berita & Edukasi', $url,
                    'available', null,
                );
            }
        }

        $seenUrls = [];

        return array_values(array_filter($items, static function (array $item) use (&$seenUrls): bool {
            $url = $item['source_url'] ?? null;
            $key = $item['source_key'] ?? null;
            $identity = $key && str_starts_with($key, 'bmkg-earthquake:') ? $key : $url;
            if (! $identity || isset($seenUrls[$identity])) {
                return false;
            }
            $seenUrls[$identity] = true;

            return true;
        }));
    }

    private function externalItem(string $key, string $category, string $title, string $message, mixed $time, string $source, string $url, string $status, mixed $fetchedAt, mixed $expiresAt = null): array
    {
        $createdAt = null;
        try {
            if ($time !== null && $time !== '') {
                $createdAt = CarbonImmutable::parse($time);
                if ($createdAt->isFuture()) {
                    $createdAt = null;
                }
            }
        } catch (\Throwable) {
            $createdAt = null;
        }

        $message = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($message)) ?? ''), 500);
        $expires = null;
        try {
            if ($expiresAt !== null) {
                $expires = CarbonImmutable::parse($expiresAt);
            }
        } catch (\Throwable) {
            $expires = null;
        }

        return [
            'key' => 'external:'.$key,
            'source_key' => $key,
            'related_type' => null,
            'related_id' => null,
            'notification_id' => null,
            'category' => $category,
            'title' => Str::limit(trim(strip_tags($title)), 240),
            'message' => $message !== '' ? $message : 'Buka sumber resmi untuk membaca informasi selengkapnya.',
            'created_at' => $createdAt,
            'source' => $source,
            'source_url' => $url,
            'target_url' => $url,
            'read_at' => null,
            'persistent' => false,
            'source_status' => $status,
            'fetched_at' => $this->safeDate($fetchedAt),
            'expires_at' => $expires,
        ];
    }

    private function safeDate(mixed $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function feedState(string $name, array $feed): array
    {
        return [
            'name' => $name,
            'status' => in_array($feed['status'] ?? null, ['available', 'partial', 'stale'], true) ? $feed['status'] : 'unavailable',
            'fetched_at' => $this->safeDate($feed['fetched_at'] ?? null),
        ];
    }

    private function allowedUrl(?string $url, ?array $allowedHosts): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if ($allowedHosts !== null && ! in_array(strtolower($parts['host']), $allowedHosts, true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) === false ? null : $url;
    }
}
