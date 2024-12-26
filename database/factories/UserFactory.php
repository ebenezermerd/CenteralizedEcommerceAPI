<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Company;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'sex' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->address,
            'password' => bcrypt('password'),
            'company_id' => null,
            'status' => $this->faker->randomElement(['active', 'pending']),
            'verified' => $this->faker->boolean,
            'email_verified_at' => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            // Assign a random role to the user
            $role = Role::inRandomOrder()->first();
            if ($role) {
                $user->assignRole($role);
            }
        });
    }

    public function supplier(): static
    {
        return $this->state(function (array $attributes) {
            $company = Company::factory()->create();
            return [
                'company_id' => $company->id,
            ];
        })->afterCreating(function (User $user) {
            $user->assignRole('supplier');
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'verified' => false,
        ]);
    }
}
