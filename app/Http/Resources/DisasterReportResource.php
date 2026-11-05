<?php

namespace App\Http\Resources;

use App\Models\DisasterReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisasterReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DisasterReport $report */
        $report = $this->resource;

        return [
            'id' => $report->id,
            'title' => $report->title,
            'disaster_type' => $report->disaster_type,
            'description' => $report->description,
            'address' => $report->address,
            'latitude' => (float) $report->latitude,
            'longitude' => (float) $report->longitude,
            'occurred_at' => $report->occurred_at?->toIso8601String(),
            'photo_path' => $report->photo_path,
            'verification_status' => $report->verification_status,
            'handling_status' => $report->handling_status,
            'verified_at' => $report->verified_at?->toIso8601String(),
            'assigned_to' => $report->relationLoaded('assignment')
                ? $report->assignment?->assigned_to
                : null,
            'reporter' => $this->whenLoaded('user', fn () => [
                'id' => $report->user->id,
                'name' => $report->user->name,
            ]),
            'assigned_officer' => $this->whenLoaded('assignedOfficer', fn () => $report->assignedOfficer === null
                ? null
                : ['id' => $report->assignedOfficer->id, 'name' => $report->assignedOfficer->name]),
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];
    }
}
