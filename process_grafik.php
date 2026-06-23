<?php
session_start();

unset($_SESSION['hasilGrafik']);
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$numVars        = isset($_POST['num_vars'])        ? (int)$_POST['num_vars']      : 0;
$numConstraints = isset($_POST['num_constraints']) ? (int)$_POST['num_constraints'] : 0;
$objective      = isset($_POST['objective'])       ? $_POST['objective']           : [];
$constraints    = isset($_POST['constraint'])       ? $_POST['constraint']          : [];
$rhs            = isset($_POST['rhs'])              ? $_POST['rhs']                 : [];

if ($numVars != 2) {
    $_SESSION['error'] = 'Metode Grafik hanya mendukung 2 variabel keputusan.';
    header('Location: index.php');
    exit;
}

if ($numConstraints < 1) {
    $_SESSION['error'] = 'Jumlah kendala minimal 1.';
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

if (count($fungsiTujuan) != 2) {
    $_SESSION['error'] = 'Koefisien fungsi tujuan harus 2 buah.';
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

require_once 'classes/Grafik.php';

$grafik = new Grafik($fungsiTujuan, $kendala, $nilaiKanan);
$berhasil = $grafik->hitung();

$_SESSION['hasilGrafik'] = [
    'berhasil'     => $berhasil,
    'fungsiTujuan' => $fungsiTujuan,
    'kendala'      => $kendala,
    'nilaiKanan'   => $nilaiKanan,
    'titikPojok'   => $grafik->getTitikPojok(),
    'solusi'       => $grafik->getSolusi(),
    'nilaiZ'       => $grafik->getNilaiZ(),
    'maxX1'        => $grafik->getMaxX1(),
    'maxX2'        => $grafik->getMaxX2(),
];

header('Location: result_grafik.php');
exit;
