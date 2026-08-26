<?php

namespace Tests\Feature\GCode;

use App\Enums\ConversionFileKind;
use App\Enums\ConversionStatus;
use App\Enums\ProjectStatus;
use App\Jobs\ProcessGCodeConversion;
use App\Models\Conversion;
use App\Models\MachineProfile;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GCodeProjectApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_can_create_a_machine_profile_and_project(): void
    {
        $user = $this->authenticatedUser();

        $profileId = $this->postJson('/api/v1/machine-profiles', [
            'name' => 'Four Axis Mill',
            'units' => 'mm',
            'settings' => [
                'repetitions' => 2,
                'b_increment' => '8.16',
                'rapid_feed_rate' => 10000,
                'engraving_feed_rate' => 1200,
            ],
            'is_default' => true,
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/v1/projects', [
            'name' => 'Hex Bolt Batch',
            'description' => 'August production run',
            'machine_profile_id' => $profileId,
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'hex-bolt-batch')
            ->assertJsonPath('data.machine_profile.id', $profileId);

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Hex Bolt Batch');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'slug' => 'hex-bolt-batch',
        ]);
    }

    public function test_synchronous_conversion_persists_history_and_private_files(): void
    {
        $user = $this->authenticatedUser();
        $project = $this->projectFor($user);

        $response = $this->postJson('/api/v1/conversions/pattern-repeater', [
            'project_id' => $project->id,
            'gcode' => "M3\nZ4 X2 Y1.5\nM5",
            'source_filename' => 'badge.nc',
            'options' => [
                'repetitions' => 2,
                'y_distance' => '5',
            ],
        ])->assertCreated()
            ->assertJsonPath('message', 'Conversion completed.')
            ->assertJsonPath('data.conversion.status', 'completed');

        $this->assertStringContainsString(
            'Z1.500000 X-2.000000 Y7.000000',
            (string) $response->json('data.output'),
        );

        $conversion = Conversion::query()->firstOrFail();
        $outputFile = $conversion->files()
            ->where('kind', ConversionFileKind::Output->value)
            ->firstOrFail();

        $this->assertSame(ConversionStatus::Completed, $conversion->status);
        Storage::disk('local')->assertExists($outputFile->path);

        $this->get('/api/v1/conversions/'.$conversion->public_id.'/download')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_async_conversion_is_queued_without_putting_source_in_the_job_payload(): void
    {
        Queue::fake();
        $user = $this->authenticatedUser();
        $project = $this->projectFor($user);

        $this->postJson('/api/v1/conversions/two-axis', [
            'project_id' => $project->id,
            'gcode' => "M3\nG1 F100 X1 Y2\nZ4\nM5",
            'async' => true,
            'options' => [
                'repetitions' => 2,
                'b_increment' => '8.16',
                'rapid_feed_rate' => 10000,
                'engraving_feed_rate' => 1200,
            ],
        ])->assertAccepted()
            ->assertJsonPath('message', 'Conversion queued.')
            ->assertJsonPath('data.output', null);

        $conversion = Conversion::query()->firstOrFail();
        $this->assertSame(ConversionStatus::Pending, $conversion->status);

        Queue::assertPushed(
            ProcessGCodeConversion::class,
            fn (ProcessGCodeConversion $job): bool => $job->conversionId === $conversion->id,
        );
    }

    public function test_conversion_is_automatically_queued_above_the_size_threshold(): void
    {
        Queue::fake();
        config()->set('gcode.queue_threshold_bytes', 10);
        $user = $this->authenticatedUser();
        $project = $this->projectFor($user);

        $this->postJson('/api/v1/conversions/pattern-repeater', [
            'project_id' => $project->id,
            'gcode' => "M3\nZ4 X1 Y1\nM5",
            'options' => ['repetitions' => 1, 'y_distance' => 1],
        ])->assertAccepted();

        Queue::assertPushed(ProcessGCodeConversion::class);
    }

    public function test_invalid_program_returns_validation_errors_and_retains_failed_history(): void
    {
        $user = $this->authenticatedUser();
        $project = $this->projectFor($user);

        $this->postJson('/api/v1/conversions/two-axis', [
            'project_id' => $project->id,
            'gcode' => "G1 X1 Y1\nZ4",
            'options' => [
                'repetitions' => 1,
                'b_increment' => 2,
                'rapid_feed_rate' => 100,
                'engraving_feed_rate' => 50,
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['gcode']]);

        $this->assertDatabaseHas('conversions', [
            'project_id' => $project->id,
            'status' => ConversionStatus::Failed->value,
        ]);
    }

    public function test_project_and_conversion_ownership_is_enforced(): void
    {
        $owner = $this->authenticatedUser();
        $project = $this->projectFor($owner);

        $intruder = User::factory()->create();
        $intruder->assignRole('user');
        Sanctum::actingAs($intruder);

        $this->getJson('/api/v1/projects/'.$project->id)->assertForbidden();
        $this->postJson('/api/v1/conversions/pattern-repeater', [
            'project_id' => $project->id,
            'gcode' => "M3\nZ4 X1 Y1\nM5",
            'options' => ['repetitions' => 1, 'y_distance' => 1],
        ])->assertForbidden();
    }

    public function test_unverified_user_cannot_access_project_or_converter_routes(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/projects')->assertForbidden();
    }

    public function test_machine_profile_ownership_and_archived_project_lifecycle_are_enforced(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('user');
        $profile = MachineProfile::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Machine',
            'slug' => 'private-machine',
            'units' => 'mm',
        ]);

        $user = $this->authenticatedUser();

        $this->postJson('/api/v1/projects', [
            'name' => 'Invalid Profile Project',
            'machine_profile_id' => (string) $profile->id,
        ])->assertForbidden();

        $project = $this->projectFor($user);
        $project->forceFill(['status' => ProjectStatus::Archived])->save();

        $this->postJson('/api/v1/conversions/pattern-repeater', [
            'project_id' => $project->id,
            'gcode' => "M3\nZ4 X1 Y1\nM5",
            'options' => ['repetitions' => 1, 'y_distance' => 1],
        ])->assertConflict();
    }

    private function authenticatedUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        return $user;
    }

    private function projectFor(User $user): Project
    {
        return Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => ProjectStatus::Active,
            'last_activity_at' => now(),
        ]);
    }
}
