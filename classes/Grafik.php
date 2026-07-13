<?php

/**
 * Kelas Grafik - Metode Grafis untuk Pemrograman Linier
 * 
 * Alur proses:
 * 1. Cari titik-titik pojok daerah layak (feasible region)
 * 2. Evaluasi fungsi tujuan di setiap titik pojok
 * 3. Pilih titik dengan nilai Z terbesar sebagai solusi optimal
 */
class Grafik {
    private $n = 2;
    private $m;
    private $fungsiTujuan;
    private $kendala;
    private $nilaiKanan;

    private $titikPojok = [];
    private $solusi = [];
    private $nilaiZ = 0;

    public function __construct($fungsiTujuan, $kendala, $nilaiKanan) {
        $this->m = count($kendala);
        $this->fungsiTujuan = $fungsiTujuan;
        $this->kendala = $kendala;
        $this->nilaiKanan = $nilaiKanan;
    }

    /**
     * PROSES UTAMA: Hitung solusi optimal metode grafis
     * 
     * Langkah-langkah:
     * 1. Cari semua titik pojok daerah layak
     * 2. Evaluasi Z = c1*X1 + c2*X2 di setiap titik
     * 3. Titik dengan Z terbesar = solusi optimal
     */
    public function hitung() {
        // Langkah 1: Cari titik pojok
        $this->titikPojok = $this->cariTitikPojok();

        // Jika tidak ada titik pojok, tidak ada solusi
        if (empty($this->titikPojok)) return false;

        // Langkah 2 & 3: Evaluasi fungsi tujuan di setiap titik pojok
        $maxZ = -INF;
        $bestPoint = null;

        foreach ($this->titikPojok as $point) {
            $z = $this->fungsiTujuan[0] * $point[0] + $this->fungsiTujuan[1] * $point[1];
            if ($z > $maxZ) {
                $maxZ = $z;
                $bestPoint = $point;
            }
        }

        // Simpan solusi optimal
        $this->solusi = ['X1' => $bestPoint[0], 'X2' => $bestPoint[1]];
        $this->nilaiZ = $maxZ;

        return true;
    }

    /**
     * PROSES: Cari titik-titik pojok daerah layak
     * 
     * Titik pojok diperoleh dari:
     * 1. Titik potong sumbu X/Y dengan tiap kendala
     * 2. Titik potong antar kendala (interseksi)
     * 3. Titik asal (0,0)
     * 
     * Hanya titik yang memenuhi semua kendala yang valid
     */
    private function cariTitikPojok() {
        $points = [[0, 0]]; // Titik asal selalu jadi kandidat
        $unique = [];

        // Langkah 1: Cari titik potong sumbu X dan Y dengan tiap kendala
        for ($i = 0; $i < $this->m; $i++) {
            $a1 = $this->kendala[$i][0];
            $a2 = $this->kendala[$i][1];
            $b  = $this->nilaiKanan[$i];

            // Titik potong dengan sumbu X2=0 (sumbu X1)
            if (abs($a1) > 1e-10) {
                $p = [$b / $a1, 0];
                if ($this->memenuhiSemua($p)) $points[] = $this->bulatkan($p);
            }

            // Titik potong dengan sumbu X1=0 (sumbu X2)
            if (abs($a2) > 1e-10) {
                $p = [0, $b / $a2];
                if ($this->memenuhiSemua($p)) $points[] = $this->bulatkan($p);
            }
        }

        // Langkah 2: Cari interseksi antar pasangan kendala
        for ($i = 0; $i < $this->m; $i++) {
            for ($j = $i + 1; $j < $this->m; $j++) {
                $p = $this->interseksi($i, $j);
                if ($p !== null && $this->memenuhiSemua($p)) {
                    $points[] = $this->bulatkan($p);
                }
            }
        }

        // Langkah 3: Hapus duplikat dan filter titik negatif
        foreach ($points as $p) {
            $key = round($p[0], 6) . ',' . round($p[1], 6);
            if (!isset($unique[$key])) {
                if ($p[0] >= -1e-10 && $p[1] >= -1e-10) {
                    $unique[$key] = [round($p[0], 4), round($p[1], 4)];
                }
            }
        }

        // Urutkan titik berdasarkan X1, lalu X2
        usort($unique, function ($a, $b) {
            if (abs($a[0] - $b[0]) > 1e-10) return $a[0] - $b[0];
            return $a[1] - $b[1];
        });

        return array_values($unique);
    }

    /**
     * PROSES: Cari titik interseksi dua garis kendala
     * 
     * Menggunakan Cramer's rule untuk menyelesaikan sistem linear:
     * a1*X1 + b1*X2 = c1
     * a2*X1 + b2*X2 = c2
     */
    private function interseksi($i, $j) {
        $a1 = $this->kendala[$i][0];
        $b1 = $this->kendala[$i][1];
        $c1 = $this->nilaiKanan[$i];
        $a2 = $this->kendala[$j][0];
        $b2 = $this->kendala[$j][1];
        $c2 = $this->nilaiKanan[$j];

        $det = $a1 * $b2 - $a2 * $b1;
        if (abs($det) < 1e-10) return null;

        return [($c1 * $b2 - $c2 * $b1) / $det, ($a1 * $c2 - $a2 * $c1) / $det];
    }

    /**
     * PROSES: Validasi titik terhadap semua kendala
     * 
     * Sebuah titik valid jika:
     * 1. X1 >= 0 dan X2 >= 0 (non-negatif)
     * 2. Memenuhi semua kendala: a1*X1 + a2*X2 <= b
     */
    private function memenuhiSemua($point) {
        $x1 = $point[0];
        $x2 = $point[1];
        if ($x1 < -1e-10 || $x2 < -1e-10) return false;

        for ($i = 0; $i < $this->m; $i++) {
            $val = $this->kendala[$i][0] * $x1 + $this->kendala[$i][1] * $x2;
            if ($val > $this->nilaiKanan[$i] + 1e-10) return false;
        }
        return true;
    }

    private function bulatkan($p) {
        return [round($p[0], 10), round($p[1], 10)];
    }

    /**
     * PROSES: Hitung batas maksimum sumbu untuk scaling grafik
     * 
     * Mencari nilai maksimum X1 atau X2 dari semua kendala
     * Ditambah 15% untuk margin visual
     */
    public function getMax($col) {
        $max = 0;
        for ($i = 0; $i < $this->m; $i++) {
            if ($this->kendala[$i][$col] > 0) {
                $val = $this->nilaiKanan[$i] / $this->kendala[$i][$col];
                if ($val > $max) $max = $val;
            }
        }
        return $max * 1.15;
    }

    public function getTitikPojok() { return $this->titikPojok; }
    public function getSolusi()     { return $this->solusi; }
    public function getNilaiZ()     { return $this->nilaiZ; }
}
