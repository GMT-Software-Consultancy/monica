<?php

namespace Database\Factories;

use App\Models\Gift;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gift::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
