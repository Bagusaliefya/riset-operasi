<?php

/**
 * Kelas Simplex - Algoritma Simpleks untuk Pemrograman Linier
 * 
 * Alur proses:
 * 1. Inisialisasi tabel awal (tabel simpleks)
 * 2. Cari kolom pivot (variabel masuk) - nilai paling negatif di baris Z
 * 3. Cari baris pivot (variabel keluar) - ratio test terkecil
 * 4. Lakukan pivot (operasi baris elementer)
 * 5. Ulangi sampai optimal (tidak ada nilai negatif di baris Z)
 */
class Simplex {
    private $n;
    private $m;
    private $totalKolom;

    private $fungsiTujuan;
    private $kendala;
    private $nilaiKanan;

    private $tabelIterasi = [];
    private $variabelDasar = [];
    private $solusi = [];
    private $nilaiZ = 0;
    private $optimal = false;

    public function __construct($n, $m, $fungsiTujuan, $kendala, $nilaiKanan) {
        $this->n = $n; // jumlah variabel keputusan
        $this->m = $m; // jumlah kendala
        $this->totalKolom = $n + $m + 1; // total kolom = variabel + slack + RHS
        $this->fungsiTujuan = $fungsiTujuan;
        $this->kendala = $kendala;
        $this->nilaiKanan = $nilaiKanan;
    }

    /**
     * PROSES UTAMA: Algoritma Simpleks
     * 
     * Langkah-langkah:
     * 1. Buat tabel awal dari data input
     * 2. Inisialisasi variabel dasar (slack variables)
     * 3. Loop sampai optimal:
     *    a. Cari kolom pivot (variabel masuk)
     *    b. Cari baris pivot (variabel keluar) dengan ratio test
     *    c. Jika unbounded (tidak ada baris pivot), return false
     *    d. Simpan state iterasi untuk ditampilkan
     *    e. Lakukan operasi pivot
     * 4. Ekstrak solusi dari tabel akhir
     */
    public function hitung() {
        // Langkah 1: Buat tabel awal
        $tabel = $this->buatTabelAwal();

        // Langkah 2: Inisialisasi variabel dasar (S1, S2, ...)
        for ($i = 0; $i < $this->m; $i++) {
            $this->variabelDasar[$i] = 'S' . ($i + 1);
        }

        $this->tabelIterasi = [];

        // Langkah 3: Loop algoritma simpleks
        while (true) {
            // 3a: Cari kolom pivot (variabel masuk)
            $kolPivot = $this->cariKolomPivot($tabel);
            $optimal = ($kolPivot === null);

            if (!$optimal) {
                // 3b: Cari baris pivot (variabel keluar) dengan ratio test
                $hasilRatio = $this->cariBarisPivot($tabel, $kolPivot);
                $barPivot = $hasilRatio['barisPivot'];
                $rasioValues = $hasilRatio['rasio'];

                // 3c: Jika tidak ada baris pivot, masalah unbounded
                if ($barPivot === null) {
                    $this->tabelIterasi[] = [
                        'tabel'          => $tabel,
                        'optimal'        => false,
                        'unbounded'      => true,
                        'variabelDasar'  => $this->variabelDasar,
                    ];
                    return false;
                }

                $elemenPivot  = $tabel[$barPivot][$kolPivot];
                $varMasuk     = $this->getNamaKolom($kolPivot);
                $varKeluar    = $this->variabelDasar[$barPivot];
            } else {
                $barPivot    = null;
                $rasioValues = [];
                $elemenPivot = null;
                $varMasuk    = null;
                $varKeluar   = null;
            }

            // 3d: Simpan state iterasi (untuk ditampilkan di result.php)
            $this->tabelIterasi[] = [
                'tabel'          => $tabel,
                'optimal'        => $optimal,
                'kolomPivot'     => $kolPivot,
                'barisPivot'     => $barPivot,
                'elemenPivot'    => $elemenPivot,
                'variabelMasuk'  => $varMasuk,
                'variabelKeluar' => $varKeluar,
                'rasio'          => $rasioValues,
                'variabelDasar'  => $this->variabelDasar,
            ];

            if ($optimal) break;

            // 3e: Lakukan operasi pivot
            $tabel = $this->lakukanPivot($tabel, $barPivot, $kolPivot);
            $this->variabelDasar[$barPivot] = $varMasuk;
        }

        // Langkah 4: Ekstrak solusi dari tabel akhir
        $this->ekstrakSolusi($tabel);
        $this->optimal = true;

        return true;
    }

    /**
     * PROSES: Buat tabel simpleks awal
     * 
     * Struktur tabel:
     * - Baris 0 s/d m-1: Koefisien kendala + slack variables + RHS
     * - Baris m: Koefisien fungsi tujuan (negatif karena maksimasi)
     */
    private function buatTabelAwal() {
        $tabel = [];

        for ($i = 0; $i < $this->m; $i++) {
            $baris = [];

            for ($j = 0; $j < $this->n; $j++) {
                $baris[] = (float)$this->kendala[$i][$j];
            }

            for ($j = 0; $j < $this->m; $j++) {
                $baris[] = ($j == $i) ? 1.0 : 0.0;
            }

            $baris[] = (float)$this->nilaiKanan[$i];
            $tabel[] = $baris;
        }

        $barisZ = [];

        for ($j = 0; $j < $this->n; $j++) {
            $barisZ[] = -(float)$this->fungsiTujuan[$j];
        }

        for ($j = 0; $j < $this->m; $j++) {
            $barisZ[] = 0.0;
        }

        $barisZ[] = 0.0;
        $tabel[] = $barisZ;

        return $tabel;
    }

    /**
     * PROSES: Cari kolom pivot (variabel masuk)
     * 
     * Aturan: Pilih kolom dengan nilai paling negatif di baris Z
     * Jika tidak ada nilai negatif, maka solusi sudah optimal
     */
    private function cariKolomPivot($tabel) {
        $barisZ = $tabel[$this->m];
        $kolPivot = null;
        $minVal = 0;

        for ($j = 0; $j < $this->totalKolom - 1; $j++) {
            if ($barisZ[$j] < $minVal - 1e-10) {
                $minVal = $barisZ[$j];
                $kolPivot = $j;
            }
        }

        return $kolPivot;
    }

    /**
     * PROSES: Cari baris pivot (variabel keluar) dengan ratio test
     * 
     * Aturan: Bagi RHS dengan koefisien kolom pivot (hanya jika > 0)
     * Pilih baris dengan rasio terkecil
     * Jika semua koefisien <= 0, masalah unbounded
     */
    private function cariBarisPivot($tabel, $kolPivot) {
        $barPivot = null;
        $minRatio = INF;
        $rasio = [];

        for ($i = 0; $i < $this->m; $i++) {
            $nilai = $tabel[$i][$kolPivot];
            $rhs = $tabel[$i][$this->totalKolom - 1];

            if ($nilai > 1e-10) {
                $ratio = $rhs / $nilai;
                $rasio[] = [
                    'baris'     => $i,
                    'rasio'     => $ratio,
                    'pembilang' => $rhs,
                    'penyebut'  => $nilai,
                ];
                if ($ratio < $minRatio) {
                    $minRatio = $ratio;
                    $barPivot = $i;
                }
            } else {
                $rasio[] = [
                    'baris'     => $i,
                    'rasio'     => null,
                    'pembilang' => $rhs,
                    'penyebut'  => $nilai,
                ];
            }
        }

        return ['barisPivot' => $barPivot, 'rasio' => $rasio];
    }

    /**
     * PROSES: Lakukan operasi pivot (operasi baris elementer)
     * 
     * Langkah:
     * 1. Bagi baris pivot dengan elemen pivot (sehingga elemen pivot jadi 1)
     * 2. Eliminasi elemen lain di kolom pivot (jadikan 0)
     * 3. Bulatkan hasil untuk stabilitas numerik
     */
    private function lakukanPivot($tabel, $barPivot, $kolPivot) {
        $totalKolom = $this->totalKolom;
        $tabelBaru = $tabel;
        $elemenPivot = $tabel[$barPivot][$kolPivot];

        for ($j = 0; $j < $totalKolom; $j++) {
            $tabelBaru[$barPivot][$j] = $tabel[$barPivot][$j] / $elemenPivot;
        }

        for ($i = 0; $i <= $this->m; $i++) {
            if ($i == $barPivot) continue;

            $faktor = $tabel[$i][$kolPivot];
            for ($j = 0; $j < $totalKolom; $j++) {
                $tabelBaru[$i][$j] = $tabel[$i][$j] - $faktor * $tabelBaru[$barPivot][$j];
            }
        }

        for ($i = 0; $i <= $this->m; $i++) {
            for ($j = 0; $j < $totalKolom; $j++) {
                $tabelBaru[$i][$j] = round($tabelBaru[$i][$j], 6);
            }
        }

        return $tabelBaru;
    }

    /**
     * PROSES: Ekstrak solusi dari tabel simpleks akhir
     * 
     * Cara kerja:
     * - Untuk setiap kolom, cek apakah ada satu nilai 1 dan sisanya 0
     * - Jika ya, kolom tersebut adalah variabel dasar
     * - Nilai RHS pada baris tersebut adalah nilai variabel
     */
    private function ekstrakSolusi($tabel) {
        for ($j = 0; $j < $this->n; $j++) {
            $this->solusi['X' . ($j + 1)] = 0;
        }

        for ($j = 0; $j < $this->totalKolom - 1; $j++) {
            $satuCount = 0;
            $nolCount  = 0;
            $posisiSatu = -1;

            for ($i = 0; $i < $this->m; $i++) {
                if (abs($tabel[$i][$j] - 1) < 1e-10) {
                    $satuCount++;
                    $posisiSatu = $i;
                } elseif (abs($tabel[$i][$j]) < 1e-10) {
                    $nolCount++;
                }
            }

            if ($satuCount == 1 && $nolCount == $this->m - 1) {
                $namaVar = $this->getNamaKolom($j);
                if (strpos($namaVar, 'X') === 0) {
                    $this->solusi[$namaVar] = round($tabel[$posisiSatu][$this->totalKolom - 1], 4);
                }
            }
        }

        $this->nilaiZ = round($tabel[$this->m][$this->totalKolom - 1], 4);
    }

    public function getNamaKolom($index) {
        if ($index < $this->n) {
            return 'X' . ($index + 1);
        } elseif ($index < $this->n + $this->m) {
            return 'S' . ($index - $this->n + 1);
        } elseif ($index == $this->totalKolom - 1) {
            return 'RHS';
        }
        return '';
    }

    public function getHeaderKolom() {
        $header = [];
        for ($j = 0; $j < $this->n; $j++) {
            $header[] = 'X' . ($j + 1);
        }
        for ($j = 0; $j < $this->m; $j++) {
            $header[] = 'S' . ($j + 1);
        }
        $header[] = 'NK';
        return $header;
    }

    public function getTabelIterasi() { return $this->tabelIterasi; }
    public function getSolusi()       { return $this->solusi; }
    public function getNilaiZ()       { return $this->nilaiZ; }
    public function isOptimal()       { return $this->optimal; }
}
