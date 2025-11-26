<?php
namespace Database\Seeders;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class UserSeeder extends Seeder
{
   public function run(): void
   {
       ini_set('memory_limit', '1024M');
       $totalUsers = 1000000;
       $chunkSize = 5000;

       $this->command->info("Starting to seed {$totalUsers} users with addresses...");
       $this->command->info("This may take several minutes...");

       DB::connection()->disableQueryLog();
       $progressBar = $this->command->getOutput()->createProgressBar($totalUsers);
       $progressBar->start();
       $now = now();
       $firstNames = ['John','Jane','Michael','Sarah','David','Emily','James','Jessica','Robert','Ashley','William','Amanda','Richard','Melissa','Joseph','Deborah','Thomas','Michelle','Christopher','Laura'];
       $lastNames = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin','Lee'];

       $countriesJsonPath = resource_path('js/data/countries.json');
       if (!file_exists($countriesJsonPath)) {
           $this->command->error("Countries file not found at {$countriesJsonPath}");
           return;
       }
       $countriesData = json_decode(file_get_contents($countriesJsonPath), true);
       $countriesWithCities = [];
       foreach ($countriesData as $c) {

           $countryName = $c['name'] ?? null;
           $cities = $c['cities'] ?? [];
           if ($countryName) {
               $countriesWithCities[$countryName] = array_values($cities);
           }
       }
       $countryNames = array_keys($countriesWithCities);
       if (empty($countryNames)) {
           $this->command->error("No countries loaded from {$countriesJsonPath}");
           return;
       }

       $faker = Faker::create();
       for ($i = 0; $i < $totalUsers; $i += $chunkSize) {
           $users = [];
           $emails = [];
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
               $emails[] = $email;
           }
           DB::table('users')->insert($users);
           $pdo = DB::getPdo();
           $firstId = (int) $pdo->lastInsertId();
           if ($firstId > 0) {
               $insertedIds = [];
               for ($k = 0; $k < $max; $k++) {
                   $insertedIds[] = $firstId + $k;
               }
           } else {
               $insertedIds = DB::table('users')->whereIn('email', $emails)->orderBy('id')->pluck('id')->toArray();
           }
           $addresses = [];
           $countryCount = count($countryNames);
           foreach ($insertedIds as $userId) {
               $cIdx = random_int(0, $countryCount - 1);
               $country = $countryNames[$cIdx];
               $cities = $countriesWithCities[$country];
               $city = $cities[random_int(0, count($cities) - 1)];
               $addresses[] = [
                   'user_id'    => $userId,
                   'country'    => $country,
                   'city'       => $city,
                   'post_code'  => $faker->numberBetween(10000, 99999),
                   'street'     => $faker->streetAddress(),
                   'created_at' => $now,
                   'updated_at' => $now,
               ];
           }
           DB::table('addresses')->insert($addresses);
           unset($users, $addresses, $insertedIds, $emails);
           gc_collect_cycles();
           $progressBar->advance($max);
       }
       $progressBar->finish();
       $this->command->newLine();
       $this->command->info("Successfully seeded {$totalUsers} users with addresses!");
   }
}