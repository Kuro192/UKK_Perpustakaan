<?php

namespace App\Http\Livewire\Peminjam;

use App\Models\Buku as ModelsBuku;
use App\Models\DetailPeminjaman;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\KoleksiPribadi;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Buku extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['pilihKategori', 'semuaKategori'];

    public $kategori_id, $pilih_kategori, $buku_id, $detail_buku, $search, $koleksiPribadi;

    public function pilihKategori($id)
    {
        $this->format();
        $this->kategori_id = $id;
        $this->pilih_kategori = true;
        $this->updatingSearch();
    }

    public function semuaKategori()
    {
        $this->format();
        $this->pilih_kategori = false;
        $this->updatingSearch();
    }

    public function detailBuku($id)
    {
        $this->format();
        $this->detail_buku = true;
        $this->buku_id = $id;
    }

    public function keranjang(ModelsBuku $buku)
    {
        // user harus login
        if (auth()->user()) {

            // role peminjam
            if (auth()->user()->hasRole('peminjam')) {

                $peminjaman_lama = DB::table('peminjaman')
                    ->join('detail_peminjaman', 'peminjaman.id', '=', 'detail_peminjaman.peminjaman_id')
                    ->where('peminjam_id', auth()->user()->id)
                    ->where('status', '!=', 3)
                    ->get();

                // jumlah maksimal 2
                if ($peminjaman_lama->count() == 2) {
                    session()->flash('gagal', 'Buku yang dipinjam maksimal 2');
                } else {

                    // peminjaman belum ada isinya
                    if ($peminjaman_lama->count() == 0) {
                        $peminjaman_baru = Peminjaman::create([
                            'kode_pinjam' => random_int(100000000, 999999999),
                            'peminjam_id' => auth()->user()->id,
                            'status' => 0
                        ]);

                        DetailPeminjaman::create([
                            'peminjaman_id' => $peminjaman_baru->id,
                            'buku_id' => $buku->id
                        ]);

                        $this->emit('tambahKeranjang');
                        session()->flash('sukses', 'Buku berhasil ditambahkan ke dalam keranjang');
                    } else {

                        // buku tidak boleh sama
                        if ($peminjaman_lama[0]->buku_id == $buku->id) {
                            session()->flash('gagal', 'Buku tidak boleh sama');
                        } else {

                            DetailPeminjaman::create([
                                'peminjaman_id' => $peminjaman_lama[0]->peminjaman_id,
                                'buku_id' => $buku->id
                            ]);

                            $this->emit('tambahKeranjang');
                            session()->flash('sukses', 'Buku berhasil ditambahkan ke dalam keranjang');
                        }

                    }

                }

            } else {
                session()->flash('gagal', 'Role user anda bukan peminjam');
            }

        } else {
            session()->flash('gagal', 'Anda harus login terlebih dahulu');
            redirect('/login');
        }

    }

    public function koleksi()
    {
        // Pastikan pengguna sudah login
        if (auth()->check()) {

            // Pastikan pengguna memiliki peran peminjam
            if (auth()->user()->hasRole('peminjam')) {

                // Periksa apakah jumlah koleksi sudah mencapai batas 10
                $totalKoleksi = KoleksiPribadi::where('user_id', auth()->id())->count();
                if ($totalKoleksi >= 10) {
                    session()->flash('gagal', 'Anda tidak dapat mengkoleksi lebih dari 10 buku');
                    return;
                }

                // Periksa apakah buku sudah ada dalam koleksi pengguna
                $existingCollection = KoleksiPribadi::where('user_id', auth()->id())
                                                    ->where('buku_id', $this->buku_id)
                                                    ->first();
                if ($existingCollection) {
                    session()->flash('gagal', 'Anda sudah mengkoleksi buku ini sebelumnya');
                    return;
                }

                // Tambahkan buku ke koleksi pribadi
                KoleksiPribadi::create([
                    'user_id' => auth()->id(),
                    'buku_id' => $this->buku_id,
                    'status_pinjam' => 'tersedia' // Sesuaikan status pinjam sesuai kebutuhan
                ]);

                session()->flash('sukses', 'Buku berhasil ditambahkan ke koleksi pribadi');

            } else {
                session()->flash('gagal', 'Role user anda bukan peminjam');
            }

        } else {
            session()->flash('gagal', 'Anda harus login terlebih dahulu');
            return redirect('/login');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        if ($this->pilih_kategori) {
            if ($this->search) {
                $buku = ModelsBuku::latest()->where('judul', 'like', '%'. $this->search .'%')->where('kategori_id', $this->kategori_id)->paginate(12);
            } else {
                $buku = ModelsBuku::latest()->where('kategori_id', $this->kategori_id)->paginate(12);
            }
            $title = Kategori::find($this->kategori_id)->nama;
        }elseif ($this->detail_buku) {
            $buku = ModelsBuku::find($this->buku_id);
            $title = 'Detail Buku';
        } else {
            if ($this->search) {
                $buku = ModelsBuku::latest()->where('judul', 'like', '%'. $this->search .'%')->paginate(12);
            } else {
                $buku = ModelsBuku::latest()->paginate(12);
            }
            $title = 'Semua Buku';
        }

        return view('livewire.peminjam.buku', compact('buku', 'title'));
    }

    public function format()
    {
        $this->detail_buku = false;
        $this->pilih_kategori = false;
        unset($this->buku_id);
        unset($this->kategori_id);
    }
}
