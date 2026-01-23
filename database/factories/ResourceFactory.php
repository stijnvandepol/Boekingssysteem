<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'timezone' => 'Europe/Amsterdam',
            'default_slot_length_minutes' => 30,
            'default_capacity' => 1,
            'is_active' => true,
        ];
    }
}
