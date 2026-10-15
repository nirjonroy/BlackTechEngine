<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin; // Admin model import kora holo
use Illuminate\Support\Facades\Hash; // Password hash korar jonno

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'admin',
            'email' => 'admin24@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
    }
}