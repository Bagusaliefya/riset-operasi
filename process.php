<?php
session_start();

unset($_SESSION['hasil']);
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$numVars        = isset($_POST['num_vars'])        ? (int)$_POST['num_vars']               : 0;
$numConstraints = isset($_POST['num_constraints']) ? (int)$_POST['num_constraints']          : 0;
$objective      = isset($_POST['objective'])       ? $_POST['objective']                    : [];
$constraints    = isset($_POST['constraint'])       ? $_POST['constraint']                   : [];
$rhs            = isset($_POST['rhs'])              ? $_POST['rhs']                          : [];

if ($numVars < 1 || $numConstraints < 1) {
    $_SESSION['error'] = 'Jumlah variabel dan kendala minimal 1.';
    header('Location: index.php');
    exit;
}

$fungsiTujuan = [];
foreach ($objective as $val) {
    $fungsiTujuan[] = (float)str_replace(',', '.', $val);
}

$kendala = [];
foreach ($constraints as $row) {
    $baris = [];
    foreach ($row as $val) {
        $baris[] = (float)str_replace(',', '.', $val);
    }
    $kendala[] = $baris;
}

$nilaiKanan = [];
foreach ($rhs as $val) {
    $nilaiKanan[] = (float)str_replace(',', '.', $val);
}

if (count($fungsiTujuan) != $numVars) {
    $_SESSION['error'] = 'Jumlah koefisien fungsi tujuan tidak sesuai.';
    header('Location: index.php');
    exit;
}

if (count($kendala) != $numConstraints) {
    $_SESSION['error'] = 'Jumlah baris kendala tidak sesuai.';
    header('Location: index.php');
    exit;
}

if (count($nilaiKanan) != $numConstraints) {
    $_SESSION['error'] = 'Jumlah nilai kanan tidak sesuai.';
    header('Location: index.php');
    exit;
}

require_once 'classes/Simplex.php';

$simplex = new Simplex($numVars, $numConstraints, $fungsiTujuan, $kendala, $nilaiKanan);
$berhasil = $simplex->hitung();

$_SESSION['hasil'] = [
    'berhasil'       => $berhasil,
    'numVars'        => $numVars,
    'numConstraints' => $numConstraints,
    'fungsiTujuan'   => $fungsiTujuan,
    'kendala'        => $kendala,
    'nilaiKanan'     => $nilaiKanan,
    'tabelIterasi'   => $simplex->getTabelIterasi(),
    'solusi'         => $simplex->getSolusi(),
    'nilaiZ'         => $simplex->getNilaiZ(),
    'optimal'        => $simplex->isOptimal(),
    'headerKolom'    => $simplex->getHeaderKolom(),
];

header('Location: result.php');
exit;
