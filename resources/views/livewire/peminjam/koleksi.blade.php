<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Koleksi</h1>
        </div>
    </div>

    @include('admin-lte/flash')

    @if($koleksiPribadi->isEmpty())
        <h4>Anda belum memiliki koleksi buku.</h4>
    @else
    <div class="row">
        <div class="col-md-12">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Rak</th>
                        <th>Baris</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($koleksiPribadi as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->buku->judul }}</td>
                            <td>{{ $item->buku->penulis }}</td>
                            <td>{{ $item->buku->rak->rak }}</td>
                            <td>{{ $item->buku->rak->baris }}</td>
                            <td>
                                <button wire:click="hapus({{ $item->id }})" class="btn btn-sm btn-danger">Hapus</button>
                                <button wire:click="keranjang({{ $item->id }})" class="btn btn-sm btn-primary">Keranjang</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
