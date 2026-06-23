<?php

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
        $this->n = $n;
        $this->m = $m;
        $this->totalKolom = $n + $m + 1;
        $this->fungsiTujuan = $fungsiTujuan;
        $this->kendala = $kendala;
        $this->nilaiKanan = $nilaiKanan;
    }

    public function hitung() {
        $tabel = $this->buatTabelAwal();

        for ($i = 0; $i < $this->m; $i++) {
            $this->variabelDasar[$i] = 'S' . ($i + 1);
        }

        $this->tabelIterasi = [];

        while (true) {
            $kolPivot = $this->cariKolomPivot($tabel);
            $optimal = ($kolPivot === null);

            if (!$optimal) {
                $hasilRatio = $this->cariBarisPivot($tabel, $kolPivot);
                $barPivot = $hasilRatio['barisPivot'];
                $rasioValues = $hasilRatio['rasio'];

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

            $tabel = $this->lakukanPivot($tabel, $barPivot, $kolPivot);
            $this->variabelDasar[$barPivot] = $varMasuk;
        }

        $this->ekstrakSolusi($tabel);
        $this->optimal = true;

        return true;
    }

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

    public function getTabelIterasi()   { return $this->tabelIterasi; }
    public function getSolusi()         { return $this->solusi; }
    public function getNilaiZ()         { return $this->nilaiZ; }
    public function isOptimal()         { return $this->optimal; }
    public function getJumlahVariabel() { return $this->n; }
    public function getJumlahKendala()  { return $this->m; }
    public function getFungsiTujuan()   { return $this->fungsiTujuan; }
    public function getKendala()        { return $this->kendala; }
    public function getNilaiKanan()     { return $this->nilaiKanan; }
}
