<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->name = "Aitor";
        $user->email = "aitorgomis@gmail.com";
        $user->password = "1234aitor";
        $user->puesto = "Directivo";
        $user->biografia = "el mejor trabajador de la empresa, siguiente CEO";
        $user->salario = 20000;
        $user->save();
    }
}
