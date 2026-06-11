<?php

interface HitungBiaya {
    public function hitungTotal();
}

class User {
    protected $nama;
    protected $noHp;

    public function __construct($nama, $noHp) {
        if (empty($nama)) {
            throw new Exception("Nama tidak boleh kosong");
        }
        if (strlen($noHp) < 10) {
            throw new Exception("Nomor HP minimal 10 digit");
        }
        $this->nama = $nama;
        $this->noHp = $noHp;
    }

    public function getNama() {
        return $this->nama;
    }

    public function getStatus() {
        return "User";
    }
}

class Pelanggan extends User {
    private $poin = 0;

    public function getStatus() {
        return "Pelanggan";
    }

    public function getNoHp() {
        return $this->noHp;
    }
   
    public function tambahPoin($totalBayar) {
        $this->poin += floor($totalBayar / 10000);
    }

    public function getPoin() {
        return $this->poin;
    }
}

class Layanan {
    private $jenis;
    private $tarif = [
        "GoRide Reguler" => 2500,
        "GoRide Prioritas" => 3000,
        "GoCar" => 4500,
        "GoCar XL" => 6000,
        "GoFood" => 2000
    ];

    public function __construct($jenis) {
        $this->jenis = $jenis;
    }

    public function getTarif() {
        return $this->tarif[$this->jenis];
    }

    public function getJenisLayanan() {
        return $this->jenis;
    }
}

class Voucher {
    private $kode;
    private $voucher = [
        "HEMAT10" => 10,
        "HEMAT20" => 20,
        "HEMAT30" => 30
    ];

    public function __construct($kode) {
        $this->kode = $kode;
    }

    public function hitungDiskon($subtotal) {
        if(isset($this->voucher[$this->kode])) {
            return $subtotal * $this->voucher[$this->kode] / 100;
        }
        return 0;
    }
}

class Pembayaran {
    public function getMethod() {
        return "Pembayaran";
    }
    public function getAdmin() {
        return 0;
    }
}

class eWallet extends Pembayaran {
    public function getMethod() {
        return "eWallet";
    }
    public function getAdmin() {
        return 1000;
    }
}

class TransferBank extends Pembayaran {
    public function getMethod() {
        return "Transfer Bank";
    }
    public function getAdmin() {
        return 2500;
    }
}

class Cash extends Pembayaran {
    public function getMethod() {
        return "Cash";
    }
    public function getAdmin() {
        return 0;
    }
}

class Transaksi implements HitungBiaya {
    private $pelanggan;
    private $layanan;
    private $voucher;
    private $pembayaran;
    private $jarak;
    private static $totalTransaksi = 0;

    public function __construct($pelanggan, $layanan, $voucher, $pembayaran, $jarak) {
        if($jarak <= 0){
            throw new Exception("Jarak harus lebih dari 0");
        }
        $this->pelanggan = $pelanggan;
        $this->layanan = $layanan;
        $this->voucher = $voucher;
        $this->pembayaran = $pembayaran;
        $this->jarak = $jarak;
        self::$totalTransaksi++;
    }

    public function hitungSubtotal() {
        return $this->jarak * $this->layanan->getTarif();
    }

    public function hitungDiskonMember() {
        $subtotal = $this->hitungSubtotal();
        if($subtotal > 50000){
            return $subtotal * 0.05;
        }
        return 0;
    }

    public function hitungDiskonVoucher() {
        return $this->voucher->hitungDiskon($this->hitungSubtotal());
    }

    public function hitungBiayaAdmin() {
        return $this->pembayaran->getAdmin();
    }

    public function hitungTotal() {
        return $this->hitungSubtotal()
            - $this->hitungDiskonMember()
            - $this->hitungDiskonVoucher()
            + $this->hitungBiayaAdmin();
    }

    public static function getTotalTransaksi() {
        return self::$totalTransaksi;
    }
}

$hasil = "";

if(isset($_POST['submit'])){
    try {
        $pelanggan = new Pelanggan($_POST['nama'], $_POST['nohp']);
        $layanan = new Layanan($_POST['layanan']);
        $voucher = new Voucher($_POST['voucher']);

        switch($_POST['pembayaran']){
            case "eWallet":
                $pembayaran = new eWallet();
                break;
            case "Transfer Bank":
                $pembayaran = new TransferBank();
                break;
            default:
                $pembayaran = new Cash();
        }

        $transaksi = new Transaksi($pelanggan, $layanan, $voucher, $pembayaran, $_POST['jarak']);
        $total = $transaksi->hitungTotal();
        $pelanggan->tambahPoin($total);

        $hasil = "
        <div class='hasil'>
            <h2>Detail Transaksi</h2>
            <p><b>Nama:</b> {$pelanggan->getNama()}</p>
            <p><b>No Hp :</b> {$pelanggan->getNoHp()}</p>
            <p><b>Status:</b> {$pelanggan->getStatus()}</p>
            <p><b>Layanan:</b> ".$layanan->getJenisLayanan()."</p>
            <p><b>Subtotal:</b> Rp ".number_format($transaksi->hitungSubtotal(),0,",",".")."</p>
            <p><b>Diskon Member:</b> Rp ".number_format($transaksi->hitungDiskonMember(),0,",",".")."</p>
            <p><b>Diskon Voucher:</b> Rp ".number_format($transaksi->hitungDiskonVoucher(),0,",",".")."</p>
            <p><b>Biaya Admin:</b> Rp ".number_format($transaksi->hitungBiayaAdmin(),0,",",".")."</p>
            <hr style='border: 0; border-top: 1px dashed rgba(255,255,255,0.5); margin: 10px 0;'>
            <p style='font-size: 1.1rem;'><b>Total Bayar:</b> Rp ".number_format($total,0,",",".")."</p>
            <p><b>Poin Reward:</b> ".$pelanggan->getPoin()."</p>
        </div>";
    } catch(Exception $e){
        $hasil = "<div class='error'>".$e->getMessage()."</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Ojek Online</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="main-wrapper">

    <nav class="navbar">
        <h2>Ojek Online</h2>
        <img src="foto/logoojek.png"alt="Logo Ojek">
    </nav>

    <div class="main-content">

        <div class="profile">
            <img src="foto/fotosiganteng.jpg" alt="Foto Saya">
            <h3>Foto Saya</h3>
            
            <div class="app-description">
                <h4>Tentang Aplikasi</h4>
                <p>Aplikasi Ojek Online ini dirancang menggunakan konsep <b>OOP (Object-Oriented Programming)</b> PHP untuk mensimulasikan perhitungan total biaya perjalanan secara dinamis.</p>
                <p>Sistem secara otomatis menghitung tarif per KM berdasarkan jenis layanan, kalkulasi diskon member & voucher hemat, serta penyesuaian biaya admin sesuai metode pembayaran.</p>
            </div>
        </div>

        <div class="container">
            <h1>Ojek Online</h1>
            <form method="POST">
                <input type="text" name="nama" placeholder="Nama Pelanggan" required>
                <input type="text" name="nohp" placeholder="Nomor HP" required>
                <input type="number" name="jarak" placeholder="Jarak Tempuh (KM)" required>
                
                <select name="layanan">
                    <option>GoRide Reguler</option>
                    <option>GoRide Prioritas</option>
                    <option>GoCar</option>
                    <option>GoCar XL</option>
                    <option>GoFood</option>
                </select>

                <input type="text" name="voucher" placeholder="Pilih Voucher" required>

                <select name="pembayaran">
                    <option>Cash</option>
                    <option>eWallet</option>
                    <option>Transfer Bank</option>
                </select>

                <button type="submit" name="submit">Hitung Transaksi</button>
            </form>

            <?= $hasil ?>
        </div>

    </div>

</div>

</body>
</html>