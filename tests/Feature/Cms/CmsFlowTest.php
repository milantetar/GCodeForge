<?php

namespace Tests\Feature\Cms;

use App\Models\CmsPage;
use App\Models\User;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CmsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsPageSeeder::class);
    }

    public function test_published_content_is_available_to_web_and_api_consumers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('cmsPage.slug', 'home')
                ->where('cmsPage.status', 'published'));

        $this->getJson('/api/v1/content/pages/home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'home')
            ->assertJsonPath('data.sections.0.key', 'hero');
    }

    public function test_regular_users_cannot_manage_cms_pages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get('/admin/cms/pages')
            ->assertForbidden();
    }

    public function test_verified_admin_can_manage_cms_through_the_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/cms/pages')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'home');
    }

    public function test_unverified_admin_is_blocked_from_the_cms_api(): void
    {
        $admin = User::factory()->unverified()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/cms/pages')->assertForbidden();
    }

    public function test_admin_can_save_a_revision_and_publish_updated_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $page = CmsPage::query()->where('slug', 'home')->with('sections')->firstOrFail();
        $sections = $page->sections->map(fn ($section) => [
            'key' => $section->key,
            'type' => $section->type,
            'name' => $section->name,
            'content' => $section->key === 'hero'
                ? [...$section->content, 'title' => 'Backend-controlled manufacturing.']
                : $section->content,
            'settings' => $section->settings ?? [],
            'is_visible' => $section->is_visible,
        ])->all();

        $this->actingAs($admin)
            ->put("/admin/cms/pages/{$page->id}", [
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => $page->type,
                'status' => 'draft',
                'excerpt' => $page->excerpt,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'settings' => $page->settings ?? [],
                'change_note' => 'Updated the home hero',
                'sections' => $sections,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->getJson('/api/v1/content/pages/home')->assertNotFound();

        $this->actingAs($admin)
            ->post("/admin/cms/pages/{$page->id}/publish")
            ->assertRedirect();

        $this->getJson('/api/v1/content/pages/home')
            ->assertOk()
            ->assertJsonPath('data.sections.0.content.title', 'Backend-controlled manufacturing.');

        $this->assertDatabaseHas('cms_page_revisions', [
            'cms_page_id' => $page->id,
            'change_note' => 'Updated the home hero',
        ]);
    }
}
