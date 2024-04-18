<?php

namespace App\Http\Livewire\Peminjam;

use Livewire\Component;
use App\Models\Buku;
use App\Models\KoleksiPribadi;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\DetailPeminjaman;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Koleksi extends Component
{
    public $koleksiPribadi;

    public function mount()
    {
        // Load koleksi pribadi pengguna yang sedang login
        if (Auth::check()) {
            $this->koleksiPribadi = KoleksiPribadi::where('user_id', Auth::id())->get();
        } else {
            $this->koleksiPribadi = collect(); // Jika pengguna tidak login, inisialisasikan dengan koleksi kosong
        }
    }

    public function loadKoleksiPribadi()
    {
        // Load koleksi pribadi pengguna yang sedang login
        $this->koleksiPribadi = KoleksiPribadi::where('user_id', Auth::id())->get();
    }

    public function hapus($id)
    {
        // Hapus buku dari koleksi pribadi
        KoleksiPribadi::findOrFail($id)->delete();
        session()->flash('sukses', 'Buku berhasil dihapus dari koleksi pribadi');
        $this->loadKoleksiPribadi(); // Muat ulang koleksi pribadi setelah menghapus
    }

    public function keranjang(Buku $buku)
    {
        // User harus login
        if (auth()->check()) {

            // Pastikan pengguna memiliki peran peminjam
            if (auth()->user()->hasRole('peminjam')) {

                // Periksa apakah pengguna sudah memiliki peminjaman yang belum selesai
                $peminjaman_lama = Peminjaman::where('peminjam_id', auth()->id())
                    ->where('status', '!=', 3)
                    ->get();

                // Jika belum memiliki peminjaman yang aktif
                if ($peminjaman_lama->isEmpty()) {
                    // Buat peminjaman baru
                    $peminjaman_baru = Peminjaman::create([
                        'kode_pinjam' => random_int(100000000, 999999999),
                        'peminjam_id' => auth()->id(),
                        'status' => 0 // Tentukan status peminjaman sesuai kebutuhan
                    ]);

                    // Tambahkan detail peminjaman
                    DetailPeminjaman::create([
                        'peminjaman_id' => $peminjaman_baru->id,
                        'buku_id' => $buku->id
                    ]);

                    // Emit event untuk memberitahu perubahan
                    $this->emit('tambahKeranjang');
                    session()->flash('sukses', 'Buku berhasil ditambahkan ke dalam keranjang');
                } else {
                    // Periksa apakah buku sudah ada dalam koleksi pengguna
                    $existingCollection = KoleksiPribadi::where('user_id', auth()->id())
                        ->where('buku_id', $buku->id)
                        ->first();

                    if ($existingCollection) {
                        session()->flash('gagal', 'Anda sudah mengkoleksi buku ini sebelumnya');
                    } else {
                        // Tambahkan buku ke koleksi pribadi
                        KoleksiPribadi::create([
                            'user_id' => auth()->id(),
                            'buku_id' => $buku->id,
                            'status_pinjam' => 'tersedia' // Sesuaikan status pinjam sesuai kebutuhan
                        ]);

                        $this->emit('tambahKeranjang');
                        session()->flash('sukses', 'Buku berhasil ditambahkan ke dalam keranjang');
                    }
                }

            } else {
                session()->flash('gagal', 'Role user anda bukan peminjam');
            }

        } else {
            session()->flash('gagal', 'Anda harus login terlebih dahulu');
            return redirect('/login');
        }
    }



    public function render()
    {
        // Load koleksi pribadi pengguna yang sedang login
        if (Auth::check()) {
            // Menggunakan metode filter untuk menyaring koleksi pribadi berdasarkan user_id
            $this->koleksiPribadi = KoleksiPribadi::all()->filter(function ($koleksi) {
                return $koleksi->user_id == Auth::id();
            });
        } else {
            $this->koleksiPribadi = collect(); // Jika pengguna tidak login, inisialisasikan dengan koleksi kosong
        }

        // Render view dengan koleksi pribadi yang sudah dimuat
        return view('livewire.peminjam.koleksi', [
            'koleksiPribadi' => $this->koleksiPribadi
        ]);
    }
}
