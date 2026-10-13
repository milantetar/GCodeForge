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

        return $this->route('cmsPage') instanceof CmsPage
            || $this->input('status') !== CmsPageStatus::Published->value
            || $user->can('cms.publish');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $page = $this->route('cmsPage');

        return [
            'lock_version' => [$page instanceof CmsPage ? 'required' : 'sometimes', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->ignore($page instanceof CmsPage ? $page->id : null),
                Rule::unique('cms_pages', 'published_slug')->ignore($page instanceof CmsPage ? $page->id : null),
            ],
            'type' => ['required', Rule::in(['landing', 'legal', 'standard'])],
            'status' => ['required', Rule::enum(CmsPageStatus::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'settings' => ['nullable', 'array'],
            'settings.theme' => ['sometimes', Rule::in(['forge-dark', 'midnight', 'forest'])],
            'settings.footer_text' => ['sometimes', 'nullable', 'string', 'max:160'],
            'settings.navigation' => ['sometimes', 'array', 'max:6'],
            'settings.navigation.*.label' => ['required', 'string', 'max:40'],
            'settings.navigation.*.href' => ['required', 'string', 'max:200', 'regex:~^(?:/(?!/)[a-zA-Z0-9/_-]*|#[a-zA-Z0-9_-]+)$~'],
            'change_note' => ['nullable', 'string', 'max:500'],
            'sections' => ['present', 'array', 'max:40'],
            'sections.*.key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'sections.*.type' => [
                'required',
                Rule::in(['hero', 'feature_grid', 'workflow', 'security', 'cta', 'rich_text', 'stats', 'custom']),
            ],
            'sections.*.name' => ['required', 'string', 'max:160'],
            'sections.*.content' => ['required', 'array', 'max:30'],
            'sections.*.content.title' => ['sometimes', 'nullable', 'string', 'max:240'],
            'sections.*.content.description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'sections.*.content.body' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'sections.*.content.accent' => ['sometimes', 'nullable', 'string', 'max:120'],
            'sections.*.content.rotating_phrases' => ['sometimes', 'array', 'max:8'],
            'sections.*.content.rotating_phrases.*' => ['nullable', 'string', 'max:120'],
            'sections.*.content.items' => ['sometimes', 'array', 'max:24'],
            'sections.*.content.items.*' => ['array'],
            'sections.*.content.items.*.title' => ['sometimes', 'nullable', 'string', 'max:240'],
            'sections.*.content.items.*.description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'sections.*.content.items.*.value' => ['sometimes', 'nullable', 'string', 'max:80'],
            'sections.*.content.items.*.label' => ['sometimes', 'nullable', 'string', 'max:160'],
            'sections.*.content.blocks' => ['sometimes', 'array', 'max:40'],
            'sections.*.content.blocks.*.title' => ['sometimes', 'nullable', 'string', 'max:240'],
            'sections.*.content.blocks.*.body' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'sections.*.content.badges' => ['sometimes', 'array', 'max:12'],
            'sections.*.content.badges.*' => ['string', 'max:100'],
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
