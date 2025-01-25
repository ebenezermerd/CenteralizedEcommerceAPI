<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run seeders in correct order
        $this->call([
            RoleSeeder::class,     // Create roles and permissions first
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        // Create users with proper role assignments
        User::factory(10)->create()->each(function ($user) {
            $faker = \Faker\Factory::create();

            // Randomly assign one role to each user
            $role = Role::inRandomOrder()->first();
            $user->assignRole($role->name);

            // Set basic user data
            $user->firstName = $faker->firstName;
            $user->lastName = $faker->lastName;
            $user->email = $faker->unique()->safeEmail;
            $user->phone = $faker->phoneNumber;
            $user->sex = $faker->randomElement(['male', 'female']);
            $user->address = $faker->address;
            $user->country = $faker->country;
            $user->city = $faker->city;
            $user->about = $faker->text;
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->verified = true;
            $user->zip_code = $faker->postcode;
            $user->image = $faker->imageUrl(640, 480, 'people');
            $user->region = $faker->state;
            $user->password = bcrypt('password');

            // Create company for suppliers
            if ($role->name === 'supplier') {
                $company = Company::create([
                    'name' => $faker->company,
                    'description' => $faker->text,
                    'email' => $faker->companyEmail,
                    'phone' => $faker->phoneNumber,
                    'country' => $faker->country,
                    'city' => $faker->city,
                    'address' => $faker->address,
                    'agreement' => true,
                ]);
                $user->company_id = $company->id;
            }

            $user->save();
        });

    }
}
