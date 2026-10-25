<?php

namespace Tests\Feature;

use App\Models\EarlyWarning;
use App\Models\EarlyWarningSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_warning_feed_returns_only_current_published_warnings_with_source_metadata(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 25)->setTime(1, 0));
        $activeWarning = $this->createWarning();
        $this->createWarning('expired', ['expires_at' => now()->subMinute()]);
        $this->createWarning('future', ['starts_at' => now()->addMinute()]);
        $this->createWarning('draft', ['status' => 'draft']);

        $this->getJson('/api/v1/warnings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeWarning->id)
            ->assertJsonPath('data.0.source', 'BMKG - Peringatan Dini Cuaca')
            ->assertJsonPath('data.0.severity', 'Severe')
            ->assertJsonPath('data.0.urgency', 'Immediate')
            ->assertJsonPath('data.0.affected_area', 'Tanjungpinang; Bintan')
            ->assertJsonPath('data.0.status', 'published');

        $this->getJson('/api/v1/warnings/'.$activeWarning->id)->assertOk();
        $this->getJson('/api/v1/warnings/'.$this->warningId('expired'))->assertNotFound();
    }

    /** @param array<string, mixed> $overrides */
    private function warningId(string $identifier): int
    {
        return (int) EarlyWarningSource::query()->where('source_identifier', $identifier)->value('early_warning_id');
    }

    /** @param array<string, mixed> $overrides */
    private function createWarning(string $identifier = 'active', array $overrides = []): EarlyWarning
    {
        $warning = EarlyWarning::query()->create(array_merge([
            'title' => 'Hujan Lebat di Kepulauan Riau',
            'disaster_type' => 'cuaca',
            'description' => 'Peringatan resmi BMKG.',
            'severity' => 'Severe',
            'affected_area' => 'Tanjungpinang; Bintan',
            'source' => 'BMKG - Peringatan Dini Cuaca',
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'status' => 'published',
        ], $overrides));
        $warning->sourceMetadata()->create([
            'source_identifier' => $identifier,
            'source_url' => 'https://www.bmkg.go.id/alerts/nowcast/id/'.$identifier.'_alert.xml',
            'source_published_at' => now()->subMinutes(10),
            'urgency' => 'Immediate',
        ]);

        return $warning;
    }
}
