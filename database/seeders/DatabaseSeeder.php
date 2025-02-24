<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Faker\Factory as Faker;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles first
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
        ]);

        // Create single admin user if not exists
        if (!User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->exists()) {
            $admin = User::factory()->admin()->create();
        }

        // Create suppliers
        User::factory(5)->create()->each(function ($user) {
            $faker = Faker::create();

            // Assign supplier role
            $user->assignRole('supplier');

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

            // Create company for supplier with status
            $company = Company::create([
                'name' => $faker->company,
                'description' => $faker->text,
                'email' => $faker->companyEmail,
                'phone' => $faker->phoneNumber,
                'country' => $faker->country,
                'city' => $faker->city,
                'address' => $faker->address,
                'owner_id' => $user->id,
                'agreement' => true,
                'status' => $faker->randomElement(['active', 'pending', 'inactive']), // Exclude 'blocked' from initial seeding
            ]);

            $user->save();

            // Only create products if company is active
            if ($company->status === 'active') {
                Product::factory(rand(5, 10))->create([
                    'vendor_id' => $user->id
                ]);
            }
        });

        // Create customers
        User::factory(5)->create()->each(function ($user) {
            $faker = \Faker\Factory::create();

            // Assign customer role
            $user->assignRole('customer');

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

            $user->save();
        });
    }
}
