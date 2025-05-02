<?php
session_start();
require "koneksi.php";
if (!isset($_SESSION['id_users'])) {
    header("Location: login.php");
    exit;
}

$id_users = $_SESSION['id_users'];

// Tangkap checkbox yang dipilih (id_keranjang)
$dipilih = $_POST['pilih_keranjang'] ?? [];

// Bangun daftar produk yang akan dibayar
$produkDibeli = [];
if (!empty($dipilih) && isset($_POST['produk'])) {
    foreach ($_POST['produk'] as $id_produk => $jumlah) {
        // cari record keranjang
        $rK = mysqli_query($koneksi,
            "SELECT id_keranjang, ukuran
               FROM keranjang
              WHERE id_users='$id_users' AND id_produk='$id_produk'
              LIMIT 1"
        );
        if ($dK = mysqli_fetch_assoc($rK)) {
            if (in_array($dK['id_keranjang'], $dipilih)) {
                // ambil detail produk
                $rP = mysqli_query($koneksi,
                    "SELECT * FROM produk WHERE id_produk='$id_produk'"
                );
                $p = mysqli_fetch_assoc($rP);
                $p['jumlah'] = $jumlah;
                $p['ukuran'] = $dK['ukuran'];
                $produkDibeli[] = $p;
            }
        }
    }
}

// ambil data user
$userQ = mysqli_query($koneksi, "SELECT * FROM users WHERE id_users='$id_users'");
$user  = mysqli_fetch_assoc($userQ);

// hitung subtotal & diskon
$subtotal = array_sum(array_map(fn($it)=> $it['harga']*$it['jumlah'], $produkDibeli));
$threshold = 1000000;
$percent   = 10;
$diskon    = $subtotal > $threshold ? $subtotal*$percent/100 : 0;
$totalBeforeOngkir = $subtotal - $diskon;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembayaran</title>
  <link rel="stylesheet" href="bootstrap/bootstrap/css/bootstrap.min.css">
</head>
<body>
<?php require "navbar.php"; ?>

<div class="container my-5">
  <h3>Pembayaran</h3>
  <form action="proses_pembayaran.php" method="post">
    <!-- Data penerima … sama seperti sebelumnya … -->

    <h5>Pesanan Anda</h5>
    <?php foreach($produkDibeli as $it): ?>
      <div>
        <strong><?= htmlspecialchars($it['nama']) ?></strong><br>
        Jumlah: <?= $it['jumlah'] ?><br>
        Ukuran: <?= htmlspecialchars($it['ukuran']) ?><br>
        Harga satuan: Rp <?= number_format($it['harga'],0,',','.') ?><br>
      </div>
      <!-- kirim hanya produk terpilih -->
      <input type="hidden" name="produk[<?= $it['id_produk'] ?>]" value="<?= $it['jumlah'] ?>">
      <input type="hidden" name="ukuran[<?= $it['id_produk'] ?>]" value="<?= htmlspecialchars($it['ukuran']) ?>">
    <?php endforeach; ?>

    <!-- ringkasan harga -->
    <p>Subtotal: Rp <?= number_format($subtotal,0,',','.') ?></p>
    <p>Diskon (<?= $percent ?>%): –Rp <?= number_format($diskon,0,',','.') ?></p>
    <p>Total sebelum ongkir: Rp <?= number_format($totalBeforeOngkir,0,',','.') ?></p>

    <!-- … lanjut pilihan ongkir & tombol … -->
  </form>
</div>

<?php require "footer.php"; ?>
</body>
</html>
