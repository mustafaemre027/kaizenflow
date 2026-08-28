<?php

namespace App\Actions\Kaizens;

use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateKaizenDraft
{
    public function execute(User $actor, Kaizen $kaizen, array $attributes): Kaizen
    {
        return DB::transaction(function () use ($actor, $kaizen, $attributes) {
            $lockedKaizen = Kaizen::where('id', $kaizen->id)->lockForUpdate()->first();

            if (! $lockedKaizen) {
                throw ValidationException::withMessages(['kaizen' => 'Kayıt bulunamadı.']);
            }

            if (! Gate::forUser($actor)->allows('update', $lockedKaizen)) {
                throw ValidationException::withMessages(['payload' => 'Bu kayıt güncellenemez durumda veya yetkiniz yok.']);
            }

            $allowedKeys = [
                'category_id',
                'title',
                'current_situation',
                'proposed_situation',
                'priority',
                'target_date',
            ];

            if (! Arr::hasAny($attributes, array_merge($allowedKeys, ['benefits']))) {
                throw ValidationException::withMessages(['payload' => 'Güncellenecek en az bir geçerli alan bulunmalıdır.']);
            }

            $safeAttributes = Arr::only($attributes, $allowedKeys);

            if (array_key_exists('category_id', $safeAttributes)) {
                $category = Category::find($safeAttributes['category_id']);
                if (! $category || ! $category->is_active) {
                    throw ValidationException::withMessages(['category_id' => 'Kategori bulunamadı veya pasif durumda.']);
                }
            }

            $lockedKaizen->fill($safeAttributes);
            $lockedKaizen->save();

            return $lockedKaizen->refresh();
        });
    }
}
