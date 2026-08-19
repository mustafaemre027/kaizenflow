<?php

namespace App\Http\Requests\Kaizens;

use App\Enums\KaizenPriority;
use App\Models\Kaizen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreKaizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('create', Kaizen::class);
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'current_situation' => ['required', 'string', 'min:10', 'max:5000'],
            'proposed_situation' => ['required', 'string', 'min:10', 'max:5000'],
            'expected_benefit' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['nullable', new Enum(KaizenPriority::class)],
            'target_date' => ['nullable', 'date', 'after_or_equal:today'],

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

            'current_situation_images' => [
                'nullable',
                'array',
                'max:'.config('kaizen.attachments.max_images_per_context', 8),
            ],
            'current_situation_images.*' => [
                'file',
                'image',
                'mimetypes:'.implode(',', config('kaizen.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp'])),
                'max:'.config('kaizen.attachments.max_image_kb', 8192),
            ],

            'proposed_situation_images' => [
                'nullable',
                'array',
                'max:'.config('kaizen.attachments.max_images_per_context', 8),
            ],
            'proposed_situation_images.*' => [
                'file',
                'image',
                'mimetypes:'.implode(',', config('kaizen.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp'])),
                'max:'.config('kaizen.attachments.max_image_kb', 8192),
            ],
        ];
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
        ];
    }

    public function messages(): array
    {
        return [
            'current_situation_images.required' => 'Mevcut durum için en az bir fotoğraf yüklemelisiniz.',
            'current_situation_images.min' => 'Mevcut durum için en az bir fotoğraf yüklemelisiniz.',
            'proposed_situation_images.required' => 'Önerilen durum için en az bir fotoğraf yüklemelisiniz.',
            'proposed_situation_images.min' => 'Önerilen durum için en az bir fotoğraf yüklemelisiniz.',
            'current_situation_images.*.mimetypes' => 'Yalnızca JPEG, PNG veya WEBP fotoğrafları yükleyebilirsiniz.',
            'current_situation_images.*.image' => 'Yalnızca JPEG, PNG veya WEBP fotoğrafları yükleyebilirsiniz.',
            'current_situation_images.*.max' => 'Bir fotoğraf izin verilen boyut sınırını aşıyor.',
            'current_situation_images.*.file' => 'Seçilen fotoğraflardan biri yüklenemedi.',
            'proposed_situation_images.*.mimetypes' => 'Yalnızca JPEG, PNG veya WEBP fotoğrafları yükleyebilirsiniz.',
            'proposed_situation_images.*.image' => 'Yalnızca JPEG, PNG veya WEBP fotoğrafları yükleyebilirsiniz.',
            'proposed_situation_images.*.max' => 'Bir fotoğraf izin verilen boyut sınırını aşıyor.',
            'proposed_situation_images.*.file' => 'Seçilen fotoğraflardan biri yüklenemedi.',
        ];
    }
}
