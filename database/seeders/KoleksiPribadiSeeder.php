<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KoleksiPribadi;
use App\Models\User;
use App\Models\Buku;

class KoleksiPribadiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ambil semua user
        $users = User::all();

        // Ambil buku tertentu (misalnya buku dengan ID 1)
        $buku = Buku::findOrFail(1);

        // Iterasi setiap user
        foreach ($users as $user) {
            // Tambahkan buku ke koleksi pribadi user
            KoleksiPribadi::create([
                'user_id' => $user->id,
                'buku_id' => $buku->id,
                'status_pinjam' => 'tersedia', // Atau 'dipinjam' sesuai kebutuhan
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
