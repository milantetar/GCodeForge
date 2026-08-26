<?php

namespace Database\Seeders;

use App\Enums\CmsPageStatus;
use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $home = $this->page(
            slug: 'home',
            title: 'GCode Forge Studio',
            type: 'landing',
            excerpt: 'Build, inspect, and optimize machine instructions with confidence.',
            seoTitle: 'Intelligent G-code workspace',
            seoDescription: 'Transform, validate, and manage production-ready CNC G-code in one secure workspace.',
        );

        $this->sections($home, [
            [
                'key' => 'hero',
                'type' => 'hero',
                'name' => 'Hero',
                'content' => [
                    'eyebrow' => 'The intelligent workspace for modern manufacturing',
                    'title' => 'Forge better G-code.',
                    'accent' => 'Ship with confidence.',
                    'description' => 'Build, inspect, and optimize machine instructions with AI-assisted precision, real-time validation, and a workflow your entire team can trust.',
                    'primary_label' => 'Create free workspace',
                    'secondary_label' => 'See how it works',
                ],
            ],
            [
                'key' => 'features',
                'type' => 'feature_grid',
                'name' => 'Features',
                'content' => [
                    'eyebrow' => 'Everything in one studio',
                    'title' => 'A calmer way to build complex things.',
                    'description' => 'Powerful enough for serious engineering, focused enough to stay out of your way.',
                    'items' => [
                        ['icon' => 'bot', 'title' => 'AI toolpath copilot', 'description' => 'Turn intent into precise machine instructions with context-aware assistance.'],
                        ['icon' => 'gauge', 'title' => 'Instant validation', 'description' => 'Catch unsafe moves, collisions, and inefficient paths before they reach your machine.'],
                        ['icon' => 'shield', 'title' => 'Team-grade control', 'description' => 'Role-based access, secure workspaces, and an audit-ready activity trail.'],
                    ],
                ],
            ],
            [
                'key' => 'workflow',
                'type' => 'workflow',
                'name' => 'Workflow',
                'content' => [
                    'eyebrow' => 'Built for flow',
                    'title' => 'Move from first draft to machine-ready without friction.',
                    'description' => 'Analysis, collaboration, revisions, and output live in one traceable workflow.',
                    'items' => [
                        ['title' => 'Describe', 'description' => 'Set material, machine, and production intent.'],
                        ['title' => 'Forge', 'description' => 'Transform repeatable toolpaths with deterministic converters.'],
                        ['title' => 'Validate', 'description' => 'Review warnings, output, and machine-safe limits.'],
                    ],
                ],
            ],
            [
                'key' => 'security',
                'type' => 'security',
                'name' => 'Security',
                'content' => [
                    'eyebrow' => 'Secure by design',
                    'title' => 'Your manufacturing knowledge stays under your control.',
                    'description' => 'Verified identities, granular roles, two-factor authentication, private storage, and traceable activity protect every workflow.',
                    'badges' => ['Role-based access', 'Two-factor authentication', 'Private by default'],
                ],
            ],
            [
                'key' => 'cta',
                'type' => 'cta',
                'name' => 'Final call to action',
                'content' => [
                    'eyebrow' => 'Ready when you are',
                    'title' => 'Turn raw machine instructions into production confidence.',
                    'description' => 'Create your workspace and start forging safer, repeatable G-code.',
                    'button_label' => 'Start forging',
                ],
            ],
        ]);

        $this->legalPage('terms', 'Terms of Service', [
            ['title' => 'Using the service', 'body' => 'Use the Forge lawfully, protect your credentials, and verify generated machine instructions before operating physical equipment.'],
            ['title' => 'Your content', 'body' => 'You retain ownership of files and instructions you upload. You grant the service only the rights needed to process and store them.'],
            ['title' => 'Safety and availability', 'body' => 'Machining involves real-world risk. You remain responsible for simulation, inspection, machine setup, and safe operation.'],
        ]);

        $this->legalPage('privacy', 'Privacy Policy', [
            ['title' => 'Information collected', 'body' => 'We store account details, authentication records, linked social identities, and workspace activity needed to operate the service.'],
            ['title' => 'How it is used', 'body' => 'Data is used to authenticate you, provide requested features, secure the platform, and improve reliability.'],
            ['title' => 'Your choices', 'body' => 'You may update profile information, secure your account with two-factor authentication, or request account deletion.'],
        ]);
    }

    private function page(
        string $slug,
        string $title,
        string $type,
        string $excerpt,
        string $seoTitle,
        string $seoDescription,
    ): CmsPage {
        return CmsPage::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'type' => $type,
                'status' => CmsPageStatus::Published,
                'excerpt' => $excerpt,
                'seo_title' => $seoTitle,
                'seo_description' => $seoDescription,
                'settings' => ['theme' => 'forge-dark'],
                'published_at' => now(),
            ],
        );
    }

    /** @param array<int, array{key: string, type: string, name: string, content: array<string, mixed>}> $sections */
    private function sections(CmsPage $page, array $sections): void
    {
        foreach ($sections as $order => $section) {
            $page->sections()->updateOrCreate(
                ['key' => $section['key']],
                [
                    'type' => $section['type'],
                    'name' => $section['name'],
                    'content' => $section['content'],
                    'settings' => [],
                    'sort_order' => $order,
                    'is_visible' => true,
                ],
            );
        }
    }

    /** @param array<int, array{title: string, body: string}> $blocks */
    private function legalPage(string $slug, string $title, array $blocks): void
    {
        $page = $this->page(
            slug: $slug,
            title: $title,
            type: 'legal',
            excerpt: 'Important information about using GCode Forge Studio.',
            seoTitle: $title.' · GCode Forge Studio',
            seoDescription: 'Read the GCode Forge Studio '.$title.'.',
        );

        $this->sections($page, [[
            'key' => 'legal-content',
            'type' => 'rich_text',
            'name' => $title,
            'content' => [
                'eyebrow' => 'Legal',
                'title' => $title,
                'intro' => 'This document explains how GCode Forge Studio operates and the responsibilities of its users.',
                'blocks' => $blocks,
                'last_updated' => now()->toDateString(),
            ],
        ]]);
    }
}
