<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $roles = Role::all();

        $users = User::factory(10)->create()->each(function ($user) use ($roles) {
            $faker = \Faker\Factory::create();
            $user->role_id = $roles->random()->id;
            $user->firstName = $faker->firstName;
            $user->lastName = $faker->lastName;
            $user->email = $faker->unique()->safeEmail;
            $user->phone = $faker->phoneNumber;
            $user->sex = $faker->randomElement(['male', 'female']);
            $user->address = $faker->address;
            $user->password = bcrypt('password');
            if ($user->role->name === 'supplier') {
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

        Order::factory(50)
            ->recycle($users)
            ->create();
        
        // Create 10 products
        $products = Product::factory(10)->create();

        // Create 50 orders, reusing the created users and products
        Order::factory(50)
            ->recycle($users)
            ->recycle($products)
            ->create();
    }
}
