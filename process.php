<?php
session_start();

unset($_SESSION['hasil'], $_SESSION['hasilGrafik'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$numVars        = (int)($_POST['num_vars'] ?? 0);
$numConstraints = (int)($_POST['num_constraints'] ?? 0);
$method         = $_POST['method'] ?? 'simpleks';
$objective      = [];
foreach (($_POST['objective'] ?? []) as $v) $objective[] = (float)str_replace(',', '.', $v);

$constraints = [];
foreach (($_POST['constraint'] ?? []) as $row) {
    $b = [];
    foreach ($row as $v) $b[] = (float)str_replace(',', '.', $v);
    $constraints[] = $b;
}

$nilaiKanan = [];
foreach (($_POST['rhs'] ?? []) as $v) $nilaiKanan[] = (float)str_replace(',', '.', $v);

if ($method === 'grafik') {
    if ($numVars != 2 || count($objective) != 2) {
        $_SESSION['error'] = 'Metode Grafik hanya mendukung 2 variabel.';
        header('Location: index.php');
        exit;
    }

    require_once 'classes/Grafik.php';
    $g = new Grafik($objective, $constraints, $nilaiKanan);
    $ok = $g->hitung();

    $_SESSION['hasilGrafik'] = [
        'berhasil' => $ok, 'fungsiTujuan' => $objective, 'kendala' => $constraints,
        'nilaiKanan' => $nilaiKanan, 'titikPojok' => $g->getTitikPojok(),
        'solusi' => $g->getSolusi(), 'nilaiZ' => $g->getNilaiZ(),
        'maxX1' => $g->getMax(0), 'maxX2' => $g->getMax(1),
    ];
    header('Location: result_grafik.php');
} else {
    if (count($objective) != $numVars || count($constraints) != $numConstraints || count($nilaiKanan) != $numConstraints) {
        $_SESSION['error'] = 'Data tidak lengkap.';
        header('Location: index.php');
        exit;
    }

    require_once 'classes/Simplex.php';
    $s = new Simplex($numVars, $numConstraints, $objective, $constraints, $nilaiKanan);
    $ok = $s->hitung();

    $_SESSION['hasil'] = [
        'berhasil' => $ok, 'numVars' => $numVars, 'numConstraints' => $numConstraints,
        'fungsiTujuan' => $objective, 'kendala' => $constraints, 'nilaiKanan' => $nilaiKanan,
        'tabelIterasi' => $s->getTabelIterasi(), 'solusi' => $s->getSolusi(),
        'nilaiZ' => $s->getNilaiZ(), 'optimal' => $s->isOptimal(), 'headerKolom' => $s->getHeaderKolom(),
    ];
    header('Location: result.php');
}
exit;
