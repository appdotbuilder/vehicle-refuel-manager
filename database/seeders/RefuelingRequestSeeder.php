<?php

namespace Database\Seeders;

use App\Models\RefuelingRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RefuelingRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users with specific roles
        $distributor = User::factory()->distributor()->create([
            'name' => 'John Distributor',
            'email' => 'distributor@example.com',
        ]);

        $sales = User::factory()->sales()->create([
            'name' => 'Jane Sales',
            'email' => 'sales@example.com',
        ]);

        $shift = User::factory()->shift()->create([
            'name' => 'Bob Shift',
            'email' => 'shift@example.com',
        ]);

        // Create sample refueling requests
        RefuelingRequest::factory(3)
            ->pending()
            ->create(['created_by' => $distributor->id]);

        RefuelingRequest::factory(2)
            ->approved()
            ->create([
                'created_by' => $distributor->id,
                'approved_by' => $sales->id,
            ]);

        RefuelingRequest::factory(1)
            ->rejected()
            ->create([
                'created_by' => $distributor->id,
                'approved_by' => $sales->id,
            ]);

        RefuelingRequest::factory(1)
            ->completed()
            ->create([
                'created_by' => $distributor->id,
                'approved_by' => $sales->id,
            ]);
    }
}