<?php

namespace App\Features\IeLayout\Seeders;

use Illuminate\Database\Seeder;
use App\Features\IeLayout\Models\Operation;

class OperationSeeder extends Seeder
{
    public function run(): void
    {
        $ops = [
            ['code' => 'OP-001', 'name' => 'Join Shoulder', 'sequence' => 10, 'machine_type' => 'Overlock 4-Thread', 'grade' => 'A'],
            ['code' => 'OP-002', 'name' => 'Set Sleeve', 'sequence' => 20, 'machine_type' => 'Single Needle', 'grade' => 'B'],
            ['code' => 'OP-003', 'name' => 'Side Seam', 'sequence' => 30, 'machine_type' => 'Overlock 4-Thread', 'grade' => 'A'],
            ['code' => 'OP-004', 'name' => 'Hem Bottom', 'sequence' => 40, 'machine_type' => 'Coverstitch', 'grade' => 'C'],
            ['code' => 'OP-005', 'name' => 'Attach Label', 'sequence' => 50, 'machine_type' => 'Single Needle', 'grade' => 'D'],
        ];

        foreach ($ops as $op) {
            Operation::updateOrCreate(['code' => $op['code']], $op);
        }
    }
}
