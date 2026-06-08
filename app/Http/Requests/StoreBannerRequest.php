<?php

namespace App\Http\Requests;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['subtitle', 'link_url', 'link_text', 'starts_at', 'ends_at', 'notes'];
        $merged   = [];
        foreach ($nullable as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }
        if ($merged) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:191'],
            'subtitle'   => ['nullable', 'string', 'max:191'],
            'link_url'   => ['nullable', 'string', 'max:500'],
            'link_text'  => ['nullable', 'string', 'max:100'],
            'position'   => ['required', 'string', Rule::in(array_keys(Banner::POSITIONS))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'starts_at'  => ['nullable', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'     => ['required', 'boolean'],
            'notes'      => ['nullable', 'string', 'max:2000'],
            'image'      => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'position.in'          => 'Please pick a valid position.',
            'ends_at.after_or_equal' => 'End date must be on or after the start date.',
            'image.image'          => 'The banner image must be an image file.',
            'image.mimes'          => 'Accepted image types: JPEG, PNG, WebP, GIF.',
            'image.max'            => 'Banner image must not exceed 4 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'      => 'Title',
            'position'   => 'Position',
            'sort_order' => 'Sort Order',
            'starts_at'  => 'Start Date',
            'ends_at'    => 'End Date',
            'status'     => 'Status',
        ];
    }
}
