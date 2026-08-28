<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\BenefitType;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SyncExpectedKaizenBenefits
{
    /**
     * Sync the structured expected benefits for a Kaizen.
     *
     * This action MUST be called within an existing DB transaction.
     * It does NOT open its own transaction; the caller (CreateKaizenDraftWithEvidence /
     * UpdateKaizenDraftWithEvidence) already wraps the whole mutation atomically.
     *
     * Lifecycle rule:
     *   - Expected benefits may only be mutated when status is DRAFT or REVISION_REQUESTED.
     *   - Any other status results in a domain exception.
     *
     * No-dual-write guarantee:
     *   - This action never touches kaizens.expected_benefit.
     *   - Legacy column is preserved as-is.
     *
     * @param  Kaizen  $kaizen  (already locked by caller)
     * @param  array<int, array<string, mixed>>  $payload  validated benefits array
     */
    public function execute(User $actor, Kaizen $kaizen, array $payload): void
    {
        // 1. Lifecycle guard
        if (! in_array($kaizen->status, [KaizenStatus::DRAFT, KaizenStatus::REVISION_REQUESTED], true)) {
            throw new \DomainException(
                "Expected benefit mutation is not allowed in status [{$kaizen->status->value}]."
            );
        }

        // 2. Normalize: remove fully-empty entries (no value + no note)
        $entries = [];
        foreach ($payload as $item) {
            $typeId = (int) ($item['benefit_type_id'] ?? 0);
            if (! $typeId) {
                continue;
            }

            $expectedValue = $this->normalizeDecimal($item['expected_value'] ?? null);
            $expectedNote = $this->normalizeText($item['expected_note'] ?? null);

            // Skip semantically empty rows
            if ($expectedValue === null && $expectedNote === null) {
                continue;
            }

            $entries[$typeId] = [
                'expected_value' => $expectedValue,
                'expected_note' => $expectedNote,
            ];
        }

        // 3. Load existing benefit records for this Kaizen (keyed by benefit_type_id)
        $existing = $kaizen->benefits()->get()->keyBy('benefit_type_id');

        // 4. Load currently linked type IDs (includes inactive — for historical preservation)
        $linkedTypeIds = $existing->keys()->all();

        // 5. Determine what type IDs are in the incoming payload
        $incomingTypeIds = array_keys($entries);

        // 6. Validate that all incoming type IDs are either:
        //    a) already linked to this Kaizen (even if now inactive), OR
        //    b) currently active benefit types
        if (! empty($incomingTypeIds)) {
            // Active types available system-wide
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

        // 7. Upsert: create or update incoming entries
        foreach ($entries as $typeId => $data) {
            /** @var KaizenBenefit|null $row */
            $row = $existing->get($typeId);

            if ($row) {
                // Update expected fields only; do NOT touch realized_value / realized_note
                $row->expected_value = $data['expected_value'];
                $row->expected_note = $data['expected_note'];
                $row->save();
            } else {
                $kaizen->benefits()->create([
                    'benefit_type_id' => $typeId,
                    'expected_value' => $data['expected_value'],
                    'expected_note' => $data['expected_note'],
                    'realized_value' => null,
                    'realized_note' => null,
                ]);
            }
        }

        // 8. Remove: delete rows that were NOT in the incoming payload,
        //    but only if they have no realized data (defensive guard).
        $removableTypeIds = array_diff($linkedTypeIds, $incomingTypeIds);
        foreach ($removableTypeIds as $typeId) {
            /** @var KaizenBenefit $row */
            $row = $existing->get($typeId);
            if (! $row) {
                continue;
            }

            if ($row->realized_value === null && $row->realized_note === null) {
                // Safe to remove: no realized data present
                $row->delete();
            } else {
                // Defensive: realized data exists — null out expected fields only
                $row->expected_value = null;
                $row->expected_note = null;
                $row->save();
            }
        }
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

        // Validate numeric
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
