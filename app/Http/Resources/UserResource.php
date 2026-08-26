<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'phone_number' => $this->phone_number,
            'portfolio_slug' => $this->portfolio_slug,
            'location' => [
                'country' => $this->country,
                'state' => $this->state,
                'city' => $this->city,
                'zip_code' => $this->zip_code,
                'address' => $this->address,
            ],
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'is_public' => $this->is_public,
            'preferred_theme' => $this->preferred_theme,
            'status' => $this->status?->value,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
        ];
    }
}
