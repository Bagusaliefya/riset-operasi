<?php

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

    public function hitung() {
        $this->titikPojok = $this->cariTitikPojok();

        if (empty($this->titikPojok)) return false;

        $maxZ = -INF;
        $bestPoint = null;

        foreach ($this->titikPojok as $point) {
            $z = $this->fungsiTujuan[0] * $point[0] + $this->fungsiTujuan[1] * $point[1];
            if ($z > $maxZ) {
                $maxZ = $z;
                $bestPoint = $point;
            }
        }

        $this->solusi = ['X1' => $bestPoint[0], 'X2' => $bestPoint[1]];
        $this->nilaiZ = $maxZ;

        return true;
    }

    private function cariTitikPojok() {
        $points = [[0, 0]];
        $unique = [];

        for ($i = 0; $i < $this->m; $i++) {
            $a1 = $this->kendala[$i][0];
            $a2 = $this->kendala[$i][1];
            $b  = $this->nilaiKanan[$i];

            if (abs($a1) > 1e-10) {
                $p = [$b / $a1, 0];
                if ($this->memenuhiSemua($p)) $points[] = $this->bulatkan($p);
            }

            if (abs($a2) > 1e-10) {
                $p = [0, $b / $a2];
                if ($this->memenuhiSemua($p)) $points[] = $this->bulatkan($p);
            }
        }

        for ($i = 0; $i < $this->m; $i++) {
            for ($j = $i + 1; $j < $this->m; $j++) {
                $p = $this->interseksi($i, $j);
                if ($p !== null && $this->memenuhiSemua($p)) {
                    $points[] = $this->bulatkan($p);
                }
            }
        }

        foreach ($points as $p) {
            $key = round($p[0], 6) . ',' . round($p[1], 6);
            if (!isset($unique[$key])) {
                if ($p[0] >= -1e-10 && $p[1] >= -1e-10) {
                    $unique[$key] = [round($p[0], 4), round($p[1], 4)];
                }
            }
        }

        usort($unique, function ($a, $b) {
            if (abs($a[0] - $b[0]) > 1e-10) return $a[0] - $b[0];
            return $a[1] - $b[1];
        });

        return array_values($unique);
    }

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
