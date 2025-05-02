<?php
session_start();
$id_users = $_SESSION['id_users']; 
require "koneksi.php";

// PROSES POST: penambahan produk (dari produk_detail.php), update jumlah, atau hapus produk
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Proses penambahan produk dari produk_detail.php
    if (isset($_POST['masukkan_keranjang'])) {
        if (!isset($_POST['id_produk']) || !isset($_POST['jumlah'])) {
            die("Error: Data tidak lengkap untuk penambahan produk!");
        }
        $id_produk = $_POST['id_produk'];
        $jumlah_produk = (int) $_POST['jumlah'];
        if ($jumlah_produk < 1) {
            $jumlah_produk = 1;
        }
        // Tangkap ukuran yang dikirim
        $ukuran = isset($_POST['ukuran_sepatu']) ? $_POST['ukuran_sepatu'] : '';

        // Cek apakah produk dengan ukuran yang sama sudah ada di keranjang
        $cekKeranjang = mysqli_query($koneksi, "SELECT * FROM keranjang WHERE id_produk='$id_produk' AND ukuran='$ukuran'");
        if (mysqli_num_rows($cekKeranjang) > 0) {
            // Update jumlah jika produk dengan ukuran yang sama sudah ada
            $update = mysqli_query($koneksi, "UPDATE keranjang SET jumlah_produk = jumlah_produk + $jumlah_produk WHERE id_produk='$id_produk' AND ukuran='$ukuran'");
            if (!$update) {
                die("Error: Gagal update produk di keranjang! " . mysqli_error($koneksi));
            }
        } else {
            // Insert produk baru ke keranjang dengan ukuran
            $insert = mysqli_query($koneksi, "INSERT INTO keranjang (id_users, id_produk, jumlah_produk, ukuran) VALUES ('$id_users', '$id_produk', '$jumlah_produk', '$ukuran')");
            if (!$insert) {
                die("Error: Gagal menambahkan produk ke keranjang! " . mysqli_error($koneksi));
            }
        }
        header("Location: produk_detail.php?nama=".$_POST['nama_produk']."&status=success");
        exit();
    }
    // 2. Proses update jumlah produk (tombol tambah atau kurang) di halaman keranjang
    elseif (isset($_POST['tambah']) || isset($_POST['kurang'])) {
        if (!isset($_POST['id_keranjang']) || !isset($_POST['jumlah'])) {
            die("Error: Data tidak lengkap untuk update jumlah!");
        }
        $id_keranjang = $_POST['id_keranjang'];
        $jumlah = (int) $_POST['jumlah'];

        if (isset($_POST['tambah'])) {
            $jumlah++;
        } elseif (isset($_POST['kurang']) && $jumlah > 1) {
            $jumlah--;
        }
        $update = mysqli_query($koneksi, "UPDATE keranjang SET jumlah_produk='$jumlah' WHERE id_keranjang='$id_keranjang'");
        if (!$update) {
            die("Error: Gagal update jumlah produk! " . mysqli_error($koneksi));
        }
    }
    // 3. Proses hapus produk dari keranjang
    elseif (isset($_POST['hapus'])) {
        if (!isset($_POST['id_keranjang'])) {
            die("Error: Data tidak lengkap untuk penghapusan!");
        }
        $id_keranjang = $_POST['id_keranjang'];
        $delete = mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang='$id_keranjang'");
        if (!$delete) {
            die("Error: Gagal menghapus produk! " . mysqli_error($koneksi));
        }
    }

    header("Location: keranjang.php");
    exit();
}

// Mengambil data produk dari keranjang untuk ditampilkan (termasuk kolom ukuran)
$queryKeranjang = mysqli_query($koneksi, 
    "SELECT keranjang.id_keranjang, produk.id_produk, produk.nama, produk.harga, produk.foto, keranjang.jumlah_produk, keranjang.ukuran 
     FROM keranjang 
     JOIN produk ON keranjang.id_produk = produk.id_produk
     WHERE keranjang.id_users = '$id_users'"
);

// Simpan semua baris dalam array agar bisa diproses ulang (untuk perhitungan ringkasan)
$keranjangItems = [];
while ($row = mysqli_fetch_array($queryKeranjang)) {
    $keranjangItems[] = $row;
}

// Hitung total harga dari semua produk
$totalHarga = 0;
foreach ($keranjangItems as $item) {
    $subtotal = $item['harga'] * $item['jumlah_produk'];
    $totalHarga += $subtotal;
}

// Pengaturan diskon
$discountThreshold = 1000000; // threshold untuk mendapat diskon
$discountPercentage = 10;    // diskon 10%
$discount = 0;
if ($totalHarga > $discountThreshold) {
    $discount = ($totalHarga * $discountPercentage) / 100;
}
$totalBayar = $totalHarga - $discount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="bootstrap/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="fontawesome/fontawesome/css/all.min.css">
    <style>
        .cart-container { 
            max-width: 900px; 
            margin: auto; 
        }
        .cart-item { 
            border-radius: 10px; 
            background-color: #f8f9fa; 
            padding: 15px; 
            margin-bottom: 15px; 
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1); 
        }
        .cart-img { 
            width: 100px; 
            height: auto; 
            border-radius: 5px; 
        }
        .qty-btn { 
            width: 35px; 
            height: 35px; 
            padding: 5px; 
        }
        .delete-btn:hover { 
            color: darkred; 
        }
        .summary { 
            background: #fff; 
            padding: 15px; 
            border-radius: 10px; 
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .checkout-btn { 
            background: limegreen; 
            color: white; 
        }
    </style>
</head>
<body>
<?php require "navbar.php"; ?>

<div class="container py-5">
    <h2 class="mb-4">Keranjang Belanja</h2>

<form action="pembayaran.php" method="post">
    <div class="row">
        <!-- Daftar Produk -->
        <div class="col-md-8">
                <?php foreach ($keranjangItems as $produk) { ?>
                <div class="cart-item d-flex align-items-center justify-content-between">
                    <input type="checkbox" name="pilih_keranjang[]" value="<?= $produk['id_keranjang'] ?>" class="form-check-input me-2" style="transform: scale(1.3);">
                    <img src="image/<?php echo $produk['foto']; ?>" class="img-fluid rounded" width="150" alt="sepatu">
                    <div class="ms-3">
                        <p class="mb-1 fw-bold">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></p>
                        <p class="mb-1"><?php echo $produk['nama']; ?></p>
                        <p class="mb-1">Ukuran: <?php echo $produk['ukuran']; ?></p>
                        <form method="post" class="d-flex align-items-center">
                            <!-- Tombol kurang -->
                            <button type="submit" name="kurang" class="btn btn-light qty-btn">-</button>
                            <!-- Jumlah Produk -->
                            <input type="text" value="<?php echo $produk['jumlah_produk']; ?>" class="form-control text-center mx-2" style="width: 50px;" readonly>
                            <!-- Tombol tambah -->
                            <button type="submit" name="tambah" class="btn btn-light qty-btn">+</button>
                        </form>
                    </div>
                    <!-- Tombol Hapus -->
                    <form method="post">
                        <input type="hidden" name="id_keranjang" value="<?php echo $produk['id_keranjang']; ?>">
                        <button type="submit" name="hapus" class="btn delete-btn"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                    </form>
                </div>
                <?php } ?>

                
        </div>

        <!-- Ringkasan Belanja -->
        <div class="col-md-4">
            <div class="summary p-3">
                <h4>Ringkasan Belanja</h4>
                <p>Sub-Total: Rp <span id="subtotal"><?php echo number_format($totalHarga, 0, ',', '.'); ?></span></p>
                <?php if ($discount > 0) { ?>
                    <p>Diskon (<?php echo $discountPercentage; ?>%): -Rp <?php echo number_format($discount, 0, ',', '.'); ?></p>
                <?php } ?>
                <h5>Total Bayar: Rp <span id="total"><?php echo number_format($totalBayar, 0, ',', '.'); ?></span></h5>

                <?php foreach ($keranjangItems as $produk) { ?>
                    <input type="hidden" name="produk[<?= $produk['id_keranjang'] ?>][id_produk]" value="<?= $produk['id_produk'] ?>">
                    <input type="hidden" name="produk[<?= $produk['id_keranjang'] ?>][jumlah]" value="<?= $produk['jumlah_produk'] ?>">
                    <input type="hidden" name="produk[<?= $produk['id_keranjang'] ?>][ukuran]" value="<?= $produk['ukuran'] ?>">
                <?php } ?>
                <button type="submit" name="beli_sekarang" class="btn btn-success w-100">Beli Sekarang</button>
    
            </div>
        </div>
    </div>

</form>
</div>

<?php require "footer.php"; ?>
<script src="bootstrap/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="fontawesome/fontawesome/js/all.min.js"></script>
<script>
    // Perbarui tampilan subtotal dan total (meski sudah terisi di PHP)
    document.getElementById("subtotal").innerText = "<?php echo number_format($totalHarga, 0, ',', '.'); ?>";
    document.getElementById("total").innerText = "<?php echo number_format($totalBayar, 0, ',', '.'); ?>";
</script>
</body>
</html>

