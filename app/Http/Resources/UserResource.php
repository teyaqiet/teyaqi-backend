<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  public function toArray(Request $request): array
{
    $levels = $this->level_data;

    return [
        'id'            => $this->id,
        // CRITICAL: Extract the attached friendship_id
        'friendship_id' => $this->resource->friendship_id ?? null, 
        'name'          => $this->name,
        'username'      => $this->username,
        'avatar'        => $this->avatar ? asset('storage/' . $this->avatar) : null,
        'total_xp'      => (int) ($this->total_xp ?? 0),
        'level'         => (int) ($levels['level'] ?? 1),
        'title'         => $levels['title'] ?? 'Warrior',
        'is_online'     => false, 
    ];
}

}