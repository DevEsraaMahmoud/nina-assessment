<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Increase memory limit for large seeding
        ini_set('memory_limit', '512M');
        
        $totalUsers = 1000000;
        $chunkSize = 1000;

        $this->command->info("Starting to seed {$totalUsers} users with addresses...");
        $this->command->info("This may take several minutes...");

        DB::connection()->disableQueryLog();

        $progressBar = $this->command->getOutput()->createProgressBar($totalUsers);
        $progressBar->start();

        $now = now();

        // Pre-generate common names to reduce Faker memory usage
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Jessica', 'Robert', 'Ashley', 'William', 'Amanda', 'Richard', 'Melissa', 'Joseph', 'Deborah', 'Thomas', 'Michelle', 'Christopher', 'Laura'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee'];
        
        // Load countries and cities from JSON file (matching UserFormModal)
        $countriesJsonPath = resource_path('js/data/countries.json');
        $countriesData = json_decode(file_get_contents($countriesJsonPath), true);
        
        // Convert to array format: country_name => [cities]
        $countriesWithCities = [];
        foreach ($countriesData as $country) {
            $countriesWithCities[$country['name']] = $country['cities'];
        }

        for ($i = 0; $i < $totalUsers; $i += $chunkSize) {
            // Create new Faker instance for each chunk to prevent memory buildup
            $faker = Faker::create();
            
            $users = [];
            $addresses = [];

            $max = min($chunkSize, $totalUsers - $i);

            for ($j = 0; $j < $max; $j++) {
                $index = $i + $j + 1;
                $email = "user{$index}@example.test";

                $users[] = [
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name'  => $lastNames[array_rand($lastNames)],
                    'email'      => $email,
                    'password'   => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($users);

            $pdo = DB::getPdo();
            $firstId = (int) $pdo->lastInsertId();

            if ($firstId <= 0) {
                $emails = array_map(fn($u) => $u['email'], $users);
                $insertedIds = DB::table('users')->whereIn('email', $emails)->pluck('id')->toArray();
            } else {
                $insertedIds = [];
                for ($k = 0; $k < $max; $k++) {
                    $insertedIds[] = $firstId + $k;
                }
            }

            // Prepare addresses
            foreach ($insertedIds as $userId) {
                // Select a random country
                $country = array_rand($countriesWithCities);
                // Select a random city from that country
                $city = $countriesWithCities[$country][array_rand($countriesWithCities[$country])];
                
                $addresses[] = [
                    'user_id'    => $userId,
                    'country'    => $country,
                    'city'       => $city,
                    'post_code'  => $faker->numberBetween(10000, 99999), // Numeric post code
                    'street'     => $faker->streetAddress(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('addresses')->insert($addresses);

            // Clean up memory
            unset($users, $addresses, $insertedIds, $faker);
            gc_collect_cycles();

            $progressBar->advance($max);
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("Successfully seeded {$totalUsers} users with addresses!");
    }
}
