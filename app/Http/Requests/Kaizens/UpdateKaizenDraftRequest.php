<?php

namespace App\Http\Requests\Kaizens;

use App\Enums\KaizenPriority;
use App\Models\Kaizen;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateKaizenDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $kaizen = $this->route('kaizen');
        if (! $kaizen instanceof Kaizen) {
            return false;
        }

        return $user->can('update', $kaizen);
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'title' => ['sometimes', 'required', 'string', 'min:5', 'max:255'],
            'current_situation' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'proposed_situation' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'expected_benefit' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
            'priority' => ['sometimes', 'nullable', new Enum(KaizenPriority::class)],
            'target_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],

            'current_situation_images' => ['nullable', 'array', 'max:8'],
            'current_situation_images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            'proposed_situation_images' => ['nullable', 'array', 'max:8'],
            'proposed_situation_images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer', 'distinct', 'exists:kaizen_attachments,id'],

            'code' => ['prohibited'],
            'creator_user_id' => ['prohibited'],
            'department_id' => ['prohibited'],
            'assigned_user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'actual_result' => ['prohibited'],
            'realized_benefit' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'approved_at' => ['prohibited'],
            'started_at' => ['prohibited'],
            'completed_at' => ['prohibited'],
            'rejected_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasAny([
                'category_id',
                'title',
                'current_situation',
                'proposed_situation',
                'expected_benefit',
                'priority',
                'target_date',
                'current_situation_images',
                'proposed_situation_images',
                'remove_attachment_ids',
            ])) {
                $validator->errors()->add('payload', 'Güncellenecek en az bir geçerli alan bulunmalıdır.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'Kategori',
            'title' => 'Başlık',
            'current_situation' => 'Mevcut Durum',
            'proposed_situation' => 'Önerilen Durum',
            'expected_benefit' => 'Beklenen Fayda',
            'priority' => 'Öncelik',
            'target_date' => 'Hedef Tarih',
            'current_situation_images' => 'Mevcut Durum Fotoğrafları',
            'current_situation_images.*' => 'Mevcut Durum Fotoğrafı',
            'proposed_situation_images' => 'Önerilen Durum Fotoğrafları',
            'proposed_situation_images.*' => 'Önerilen Durum Fotoğrafı',
            'remove_attachment_ids' => 'Kaldırılacak Fotoğraflar',
            'remove_attachment_ids.*' => 'Kaldırılacak Fotoğraf',
        ];
    }
}
