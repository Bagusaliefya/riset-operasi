<?php
session_start();

// Bersihkan data hasil sebelumnya
unset($_SESSION['hasil'], $_SESSION['hasilGrafik'], $_SESSION['error']);

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// PROSES 1: Parse input form dari POST
$numVars        = (int)($_POST['num_vars'] ?? 0);
$numConstraints = (int)($_POST['num_constraints'] ?? 0);
$method         = $_POST['method'] ?? 'simpleks';

// Parse koefisien fungsi tujuan
$objective      = [];
foreach (($_POST['objective'] ?? []) as $v) $objective[] = (float)str_replace(',', '.', $v);

// Parse koefisien kendala (matriks)
$constraints = [];
foreach (($_POST['constraint'] ?? []) as $row) {
    $b = [];
    foreach ($row as $v) $b[] = (float)str_replace(',', '.', $v);
    $constraints[] = $b;
}

// Parse nilai kanan (RHS) tiap kendala
$nilaiKanan = [];
foreach (($_POST['rhs'] ?? []) as $v) $nilaiKanan[] = (float)str_replace(',', '.', $v);

// PROSES 2: Dispatch ke metode yang dipilih
if ($method === 'grafik') {
    // Validasi: metode grafik hanya untuk 2 variabel
    if ($numVars != 2 || count($objective) != 2) {
        $_SESSION['error'] = 'Metode Grafik hanya mendukung 2 variabel.';
        header('Location: index.php');
        exit;
    }

    // Hitung menggunakan metode grafik
    require_once 'classes/Grafik.php';
    $g = new Grafik($objective, $constraints, $nilaiKanan);
    $ok = $g->hitung();

    // Simpan hasil ke session dan redirect ke result_grafik.php
    $_SESSION['hasilGrafik'] = [
        'berhasil' => $ok, 'fungsiTujuan' => $objective, 'kendala' => $constraints,
        'nilaiKanan' => $nilaiKanan, 'titikPojok' => $g->getTitikPojok(),
        'solusi' => $g->getSolusi(), 'nilaiZ' => $g->getNilaiZ(),
        'maxX1' => $g->getMax(0), 'maxX2' => $g->getMax(1),
    ];
    header('Location: result_grafik.php');
} else {
    // Validasi: data harus lengkap
    if (count($objective) != $numVars || count($constraints) != $numConstraints || count($nilaiKanan) != $numConstraints) {
        $_SESSION['error'] = 'Data tidak lengkap.';
        header('Location: index.php');
        exit;
    }

    // Hitung menggunakan metode simpleks
    require_once 'classes/Simplex.php';
    $s = new Simplex($numVars, $numConstraints, $objective, $constraints, $nilaiKanan);
    $ok = $s->hitung();

    // Simpan hasil ke session dan redirect ke result.php
    $_SESSION['hasil'] = [
        'berhasil' => $ok, 'numVars' => $numVars, 'numConstraints' => $numConstraints,
        'fungsiTujuan' => $objective, 'kendala' => $constraints, 'nilaiKanan' => $nilaiKanan,
        'tabelIterasi' => $s->getTabelIterasi(), 'solusi' => $s->getSolusi(),
        'nilaiZ' => $s->getNilaiZ(), 'optimal' => $s->isOptimal(), 'headerKolom' => $s->getHeaderKolom(),
    ];
    header('Location: result.php');
}
exit;
