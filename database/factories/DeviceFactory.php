<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_uuid' => (string) Str::uuid(),
            'device_secret_hash' => null,
            'name' => fake()->name() . ' Phone',
            'platform' => 'android',
            'model' => 'Test Phone',
            'manufacturer' => 'Test Manufacturer',
            'os_version' => '15',
            'app_version' => '1.0.0',
            'battery_level' => null,
            'is_charging' => null,
            'network_type' => null,
            'location_enabled' => null,
            'last_seen_at' => null,
            'is_active' => true,
            'is_lost' => false,
        ];
    }
}