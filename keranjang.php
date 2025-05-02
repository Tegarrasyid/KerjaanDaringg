<?php
session_start();
$id_users = $_SESSION['id_users'];
require "koneksi.php";

// PROSES POST: penambahan produk, update jumlah, atau hapus
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // … (logika tambah/update/hapus seperti semula) …
    header("Location: keranjang.php");
    exit();
}

// Ambil data keranjang
$queryKeranjang = mysqli_query($koneksi,
    "SELECT k.id_keranjang, p.id_produk, p.nama, p.harga, p.foto, k.jumlah_produk, k.ukuran
       FROM keranjang k
       JOIN produk p ON k.id_produk = p.id_produk
      WHERE k.id_users = '$id_users'"
);
$keranjangItems = [];
while ($row = mysqli_fetch_assoc($queryKeranjang)) {
    $keranjangItems[] = $row;
}

// Hitung total & diskon
$totalHarga = array_sum(array_map(fn($i)=> $i['harga']*$i['jumlah_produk'], $keranjangItems));
$discountThreshold = 1000000;
$discountPercentage = 10;
$discount = $totalHarga > $discountThreshold
    ? $totalHarga * $discountPercentage / 100
    : 0;
$totalBayar = $totalHarga - $discount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Keranjang Belanja</title>
  <link rel="stylesheet" href="bootstrap/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="fontawesome/fontawesome/css/all.min.css">
  <style>
    .cart-item{border-radius:10px;background:#f8f9fa;padding:15px;margin-bottom:15px;}
    .cart-img{width:100px;border-radius:5px;}
    .qty-btn{width:35px;height:35px;}
  </style>
</head>
<body>
<?php require "navbar.php"; ?>

<div class="container py-5">
  <h2 class="mb-4">Keranjang Belanja</h2>

  <!-- MULAI FORM: kirim ke pembayaran.php -->
  <form action="pembayaran.php" method="post">
    <div class="row">
      <div class="col-md-8">
        <?php foreach ($keranjangItems as $item): ?>
          <div class="cart-item d-flex align-items-center">
            <!-- checkbox pilih produk -->
            <input type="checkbox"
                   name="pilih_keranjang[]"
                   value="<?= $item['id_keranjang'] ?>"
                   class="form-check-input me-2"
                   style="transform:scale(1.3);">
            <img src="image/<?= $item['foto'] ?>"
                 class="cart-img me-3" alt="">
            <div class="flex-grow-1">
              <p class="fw-bold mb-1">
                Rp <?= number_format($item['harga'],0,',','.') ?>
              </p>
              <p class="mb-1"><?= htmlspecialchars($item['nama']) ?></p>
              <p class="mb-1">Ukuran: <?= htmlspecialchars($item['ukuran']) ?></p>
              <!-- tombol + / – -->
              <form method="post" class="d-flex align-items-center">
                <input type="hidden" name="id_keranjang" value="<?= $item['id_keranjang'] ?>">
                <input type="hidden" name="jumlah" value="<?= $item['jumlah_produk'] ?>">
                <button name="kurang" class="btn btn-light qty-btn">–</button>
                <span class="mx-2"><?= $item['jumlah_produk'] ?></span>
                <button name="tambah" class="btn btn-light qty-btn">+</button>
              </form>
            </div>
            <!-- hapus -->
            <form method="post" class="ms-3">
              <input type="hidden" name="id_keranjang" value="<?= $item['id_keranjang'] ?>">
              <button name="hapus" class="btn text-danger">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="col-md-4">
        <div class="p-3 bg-white rounded shadow-sm">
          <h4>Ringkasan</h4>
          <p>Subtotal: Rp <?= number_format($totalHarga,0,',','.') ?></p>
          <?php if($discount>0): ?>
            <p>Diskon (<?= $discountPercentage ?>%): –Rp <?= number_format($discount,0,',','.') ?></p>
          <?php endif; ?>
          <h5>Total: Rp <?= number_format($totalBayar,0,',','.') ?></h5>

          <!-- kirim data produk & ukuran untuk SEMUA,
               nanti di pembayaran akan disaring berdasarkan pilih_keranjang[] -->
          <?php
            // reset result pointer & ulangi fetch
            mysqli_data_seek($queryKeranjang, 0);
            while($p = mysqli_fetch_assoc($queryKeranjang)):
          ?>
            <input type="hidden"
                   name="produk[<?= $p['id_produk'] ?>]"
                   value="<?= $p['jumlah_produk'] ?>">
            <input type="hidden"
                   name="ukuran[<?= $p['id_produk'] ?>]"
                   value="<?= htmlspecialchars($p['ukuran']) ?>">
          <?php endwhile; ?>

          <button type="submit" class="btn btn-success w-100 mt-3">
            Beli Sekarang
          </button>
        </div>
      </div>
    </div>
  </form>
  <!-- AKHIR FORM -->
</div>

<?php require "footer.php"; ?>
</body>
</html>
