<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\BenefitType;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SyncRealizedKaizenBenefits
{
    /**
     * Sync the structured realized benefits for a Kaizen during implementation completion.
     *
     * This action MUST be called within an existing DB transaction.
     * It does NOT open its own transaction; the caller (CompleteKaizenImplementation)
     * already wraps the whole mutation atomically.
     *
     * Lifecycle rule:
     *   - Realized benefits may only be mutated when status is IN_PROGRESS.
     *
     * No-dual-write guarantee:
     *   - This action never touches kaizens.realized_benefit legacy column.
     *
     * @param  Kaizen  $kaizen  (already locked by caller)
     * @param  array<int, array<string, mixed>>  $payload  validated benefits array
     */
    public function execute(User $actor, Kaizen $kaizen, array $payload): void
    {
        // 1. Lifecycle guard
        if ($kaizen->status !== KaizenStatus::IN_PROGRESS) {
            throw new \DomainException(
                "Realized benefit mutation is not allowed in status [{$kaizen->status->value}]."
            );
        }

        // 2. Normalize: remove fully-empty entries (no value + no note)
        $entries = [];
        foreach ($payload as $item) {
            $typeId = (int) ($item['benefit_type_id'] ?? 0);
            if (! $typeId) {
                continue;
            }

            if (array_key_exists($typeId, $entries)) {
                throw ValidationException::withMessages([
                    'benefits' => 'Aynı fayda türü birden fazla kez eklenemez.',
                ]);
            }

            $realizedValue = $this->normalizeDecimal($item['realized_value'] ?? null);
            $realizedNote = $this->normalizeText($item['realized_note'] ?? null);

            // Skip semantically empty placeholder rows
            if ($realizedValue === null && $realizedNote === null) {
                continue;
            }

            $entries[$typeId] = [
                'realized_value' => $realizedValue,
                'realized_note' => $realizedNote,
            ];
        }

        // 3. Load existing benefit records for this Kaizen (keyed by benefit_type_id)
        $existing = $kaizen->benefits()->get()->keyBy('benefit_type_id');
        $linkedTypeIds = $existing->keys()->all();

        // 4. Determine incoming type IDs
        $incomingTypeIds = array_keys($entries);

        // 5. Validate that incoming type IDs are either already linked OR currently active
        if (! empty($incomingTypeIds)) {
            $activeTypeIds = BenefitType::active()->pluck('id')->all();

            foreach ($incomingTypeIds as $typeId) {
                $isLinked = in_array($typeId, $linkedTypeIds, true);
                $isActive = in_array($typeId, $activeTypeIds, true);

                if (! $isLinked && ! $isActive) {
                    throw ValidationException::withMessages([
                        'benefits' => 'Geçersiz veya pasif bir fayda türü seçildi.',
                    ]);
                }
            }
        }

        // 6. Upsert: create realized-only or update existing realized entries
        foreach ($entries as $typeId => $data) {
            /** @var KaizenBenefit|null $row */
            $row = $existing->get($typeId);

            if ($row) {
                // Update realized fields only; do NOT touch expected fields
                $row->realized_value = $data['realized_value'];
                $row->realized_note = $data['realized_note'];
                $row->save();
            } else {
                // Create realized-only record (unlinked active type)
                $kaizen->benefits()->create([
                    'benefit_type_id' => $typeId,
                    'expected_value' => null,
                    'expected_note' => null,
                    'realized_value' => $data['realized_value'],
                    'realized_note' => $data['realized_note'],
                ]);
            }
        }

        // Note: We DO NOT remove existing expected benefits that aren't in the incoming payload.
        // Realized benefits are optional, and omitting an expected benefit from the completion
        // payload just means no realized value was achieved for it.
        // The expected benefit record itself must remain intact for historical comparison.
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (! is_numeric($str)) {
            return null;
        }

        return $str;
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
