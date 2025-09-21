<?php

namespace Database\Seeders;

use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PhaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 3 default phases
        $phases = [
            [
                'type' => 'vong_loai',
                'name' => 'Vòng Loại 2025',
                'start_at' => Carbon::now()->addDays(1)->setTime(8, 0, 0),
                'end_at' => Carbon::now()->addDays(1)->setTime(12, 0, 0),
                'status' => 'draft',
                'matches_per_player' => 3
            ],
            [
                'type' => 'ban_ket',
                'name' => 'Bán Kết 2025',
                'start_at' => Carbon::now()->addDays(2)->setTime(8, 0, 0),
                'end_at' => Carbon::now()->addDays(2)->setTime(12, 0, 0),
                'status' => 'draft',
                'matches_per_player' => 2
            ],
            [
                'type' => 'chung_ket',
                'name' => 'Chung Kết 2025',
                'start_at' => Carbon::now()->addDays(3)->setTime(8, 0, 0),
                'end_at' => Carbon::now()->addDays(3)->setTime(12, 0, 0),
                'status' => 'draft',
                'matches_per_player' => 1
            ]
        ];

        foreach ($phases as $phaseData) {
            Phase::create($phaseData);
        }

        // Create sample users for testing
        $sampleUsers = [
            ['name' => 'Nguyễn Văn An', 'gender' => 'M'],
            ['name' => 'Trần Thị Bình', 'gender' => 'F'],
            ['name' => 'Lê Văn Cường', 'gender' => 'M'],
            ['name' => 'Phạm Thị Dung', 'gender' => 'F'],
            ['name' => 'Hoàng Văn Em', 'gender' => 'M'],
            ['name' => 'Vũ Thị Phương', 'gender' => 'F'],
            ['name' => 'Đặng Văn Giang', 'gender' => 'M'],
            ['name' => 'Ngô Thị Hoa', 'gender' => 'F'],
            ['name' => 'Bùi Văn Inh', 'gender' => 'M'],
            ['name' => 'Lý Thị Kim', 'gender' => 'F'],
        ];

        foreach ($sampleUsers as $userData) {
            User::firstOrCreate(
                ['name' => $userData['name']],
                ['gender' => $userData['gender']]
            );
        }
    }
}
