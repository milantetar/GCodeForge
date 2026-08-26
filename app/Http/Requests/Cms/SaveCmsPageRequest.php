<?php

namespace App\Http\Requests\Cms;

use App\Enums\CmsPageStatus;
use App\Models\CmsPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCmsPageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $sections = $this->input('sections', []);

        if (is_array($sections)) {
            $sections = array_map(function (mixed $section): mixed {
                if (! is_array($section) || ! is_string($section['content_json'] ?? null)) {
                    return $section;
                }

                $section['content'] = json_decode($section['content_json'], true);
                $section['settings'] = is_string($section['settings_json'] ?? null)
                    ? json_decode($section['settings_json'], true)
                    : ($section['settings'] ?? []);

                return $section;
            }, $sections);
        }

        $settings = $this->input('settings');
        if (is_string($this->input('settings_json'))) {
            $settings = json_decode((string) $this->input('settings_json'), true);
        }

        $this->merge(['sections' => $sections, 'settings' => $settings]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('cms.manage')) {
            return false;
        }

        return $this->input('status') !== CmsPageStatus::Published->value
            || $user->can('cms.publish');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $page = $this->route('cmsPage');

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->ignore($page instanceof CmsPage ? $page->id : null),
            ],
            'type' => ['required', Rule::in(['landing', 'legal', 'standard'])],
            'status' => ['required', Rule::enum(CmsPageStatus::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'settings' => ['nullable', 'array'],
            'change_note' => ['nullable', 'string', 'max:500'],
            'sections' => ['present', 'array', 'max:40'],
            'sections.*.key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'sections.*.type' => [
                'required',
                Rule::in(['hero', 'feature_grid', 'workflow', 'security', 'cta', 'rich_text', 'stats', 'custom']),
            ],
            'sections.*.name' => ['required', 'string', 'max:160'],
            'sections.*.content' => ['required', 'array'],
            'sections.*.settings' => ['nullable', 'array'],
            'sections.*.is_visible' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function cmsData(): array
    {
        return $this->validated();
    }
}
