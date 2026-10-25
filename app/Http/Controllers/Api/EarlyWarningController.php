<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarlyWarningResource;
use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class EarlyWarningController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EarlyWarningResource::collection(
            $this->activeWarningsQuery()
                ->orderByDesc(EarlyWarningSource::query()
                    ->select('source_published_at')
                    ->whereColumn('early_warning_id', 'early_warnings.id'))
                ->orderByDesc('id')
                ->paginate(20),
        );
    }

    public function show(int $id): EarlyWarningResource
    {
        return new EarlyWarningResource($this->activeWarningsQuery()->findOrFail($id));
    }

    private function activeWarningsQuery(): Builder
    {
        return EarlyWarning::query()
            ->where('status', 'published')
            ->whereNotNull('source')
            ->where('expires_at', '>', Carbon::now())
            ->whereHas('sourceMetadata', function (Builder $query): void {
                $query->whereNotNull('source_url')->where('source_url', '<>', '');
            })
            ->with('sourceMetadata')
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            });
    }
}
