<?php

namespace App\Services\GCode;

use App\Models\MachineProfile;
use App\Models\User;
use App\Repositories\GCode\MachineProfileRepository;
use App\Services\CoreService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/** @extends CoreService<MachineProfile, MachineProfileRepository> */
class MachineProfileService extends CoreService
{
    public function __construct(protected MachineProfileRepository $profileRepository)
    {
        parent::__construct($profileRepository);
    }

    /** @return LengthAwarePaginator<int, MachineProfile> */
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->profileRepository->query()
            ->where('user_id', $user->id)
            ->latest('is_default')
            ->latest('id')
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): MachineProfile
    {
        return $this->transaction(function () use ($user, $data): MachineProfile {
            $makeDefault = (bool) ($data['is_default'] ?? false)
                || ! $this->profileRepository->query()->where('user_id', $user->id)->exists();

            if ($makeDefault) {
                $this->clearDefault($user);
            }

            /** @var MachineProfile $profile */
            $profile = $this->profileRepository->create([
                ...$data,
                'user_id' => $user->id,
                'slug' => $this->uniqueSlug($user, (string) ($data['slug'] ?? $data['name'])),
                'is_default' => $makeDefault,
            ]);

            return $profile;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(MachineProfile $profile, array $data): MachineProfile
    {
        return $this->transaction(function () use ($profile, $data): MachineProfile {
            if (($data['is_default'] ?? false) === true) {
                $this->clearDefault($profile->user);
            }

            if (isset($data['slug']) || (isset($data['name']) && $data['name'] !== $profile->name)) {
                $data['slug'] = $this->uniqueSlug(
                    $profile->user,
                    (string) ($data['slug'] ?? $data['name']),
                    $profile->id,
                );
            }

            $this->profileRepository->update($profile, $data);

            return $profile->refresh();
        });
    }

    private function clearDefault(User $user): void
    {
        $this->profileRepository->query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function uniqueSlug(User $user, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'machine';
        $slug = $base;
        $suffix = 2;

        while ($this->profileRepository->query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
