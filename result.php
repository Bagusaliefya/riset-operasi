<?php
session_start();

// Cek apakah ada hasil perhitungan
if (!isset($_SESSION['hasil'])) {
    header('Location: index.php');
    exit;
}

// Ambil hasil dari session
$hasil = $_SESSION['hasil'];

require_once 'includes/helpers.php';

// Ekstrak data untuk ditampilkan
$numVars        = $hasil['numVars'];
$numConstraints = $hasil['numConstraints'];
$fungsiTujuan   = $hasil['fungsiTujuan'];
$kendala        = $hasil['kendala'];
$nilaiKanan     = $hasil['nilaiKanan'];
$tabelIterasi   = $hasil['tabelIterasi'];
$solusi         = $hasil['solusi'];
$nilaiZ         = $hasil['nilaiZ'];
$optimal        = $hasil['optimal'];
$headerKolom    = $hasil['headerKolom'];
$berhasil       = $hasil['berhasil'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil - Metode Simpleks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            <div class="card mb-4 text-center">
                <div class="card-header bg-gradient-success text-white py-4">
                    <i class="bi bi-check2-circle fs-1 opacity-75 mb-2 d-block"></i>
                    <h3 class="fw-bold mb-1">Hasil Perhitungan</h3>
                    <p class="mb-0 small opacity-75">Metode Simpleks – Pemrograman Linier</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-gradient-dark text-white d-flex align-items-center gap-2 py-3">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <span class="fw-bold">Formulasi Masalah</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="sub-header">
                                <i class="bi bi-bullseye me-1"></i> Fungsi Tujuan
                            </div>
                            <div class="p-3 rounded" style="background: linear-gradient(135deg, #f0f4ff, #e8f0fe);">
                                <strong class="text-primary">Maks Z</strong> =
                                <?php for ($i = 0; $i < $numVars; $i++): ?>
                                    <strong><?= formatAngka($fungsiTujuan[$i]) ?></strong>X<sub><?= $i + 1 ?></sub>
                                    <?= ($i < $numVars - 1) ? ' + ' : '' ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="sub-header">
                                <i class="bi bi-signpost-2 me-1"></i> Kendala
                            </div>
                            <?php for ($i = 0; $i < $numConstraints; $i++): ?>
                                <div class="p-2 px-3 rounded mb-1" style="background: #f8fafc; border-left: 3px solid var(--primary);">
                                    <?php for ($j = 0; $j < $numVars; $j++): ?>
                                        <strong><?= formatAngka($kendala[$i][$j]) ?></strong>X<sub><?= $j + 1 ?></sub>
                                        <?= ($j < $numVars - 1) ? ' + ' : '' ?>
                                    <?php endfor; ?>
                                    &nbsp;≤&nbsp; <strong class="text-danger"><?= formatAngka($nilaiKanan[$i]) ?></strong>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$berhasil): ?>
                <!-- TAMPILAN: Masalah unbounded (tidak ada solusi optimal) -->
                <div class="card mb-4">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                        <h5 class="fw-bold">Masalah Tidak Terbatas</h5>
                        <p class="text-muted">Masalah Pemrograman Linier ini <strong>unbounded</strong> dan tidak memiliki solusi optimal.</p>
                    </div>
                </div>
            <?php else: ?>

                <!-- TAMPILAN: Tabel iterasi simpleks -->
                <?php foreach ($tabelIterasi as $index => $iter): ?>
                    <?php
                    $tabel         = $iter['tabel'];
                    $kolPivot      = $iter['kolomPivot'];
                    $barPivot      = $iter['barisPivot'];
                    $elemenPivot   = $iter['elemenPivot'];
                    $varMasuk      = $iter['variabelMasuk'];
                    $varKeluar     = $iter['variabelKeluar'];
                    $rasio         = $iter['rasio'];
                    $varDasar      = $iter['variabelDasar'];
                    $isOptimal     = $iter['optimal'];
                    $isUnbounded   = isset($iter['unbounded']) ? $iter['unbounded'] : false;

                    $labelIterasi = $index == 0 ? 'Tabel Awal' : "Iterasi ke-{$index}";
                    $headerClass  = $isOptimal ? 'bg-gradient-success' : 'bg-gradient-primary';
                    $headerIcon   = $isOptimal ? 'bi-check2-circle' : 'bi-arrow-repeat';
                    ?>

                    <div class="card card-iteration mb-4">
                        <div class="card-header <?= $headerClass ?> text-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="<?= $headerIcon ?> fs-5"></i>
                                <span class="iteration-number"><?= $labelIterasi ?></span>
                            </div>
                            <?php if ($isOptimal): ?>
                                <span class="badge-optimal">
                                    <i class="bi bi-star-fill me-1"></i> OPTIMAL
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-simplex mb-0">
                                    <thead>
                                        <tr>
                                            <th class="vd-header">VD</th>
                                            <?php foreach ($headerKolom as $h): ?>
                                                <th class="text-center <?= ($h == 'NK') ? 'rhs-header' : '' ?>">
                                                    <?= $h ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 0; $i <= $numConstraints; $i++): ?>
                                            <tr>
                                                <td class="fw-bold vd-cell">
                                                    <?php if ($i < $numConstraints): ?>
                                                        <?= htmlspecialchars($varDasar[$i]) ?>
                                                    <?php else: ?>
                                                        Z
                                                    <?php endif; ?>
                                                </td>
                                                <?php for ($j = 0; $j < count($headerKolom); $j++): ?>
                                                    <?php
                                                    $nilai = $tabel[$i][$j];
                                                    $cellClass = '';

                                                    if ($kolPivot !== null && $j == $kolPivot) {
                                                        $cellClass .= ' col-pivot';
                                                    }
                                                    if ($barPivot !== null && $i == $barPivot) {
                                                        $cellClass .= ' row-pivot';
                                                    }
                                                    if ($kolPivot !== null && $barPivot !== null
                                                        && $i == $barPivot && $j == $kolPivot
                                                    ) {
                                                        $cellClass .= ' elem-pivot';
                                                    }
                                                    if ($i == $numConstraints) {
                                                        $cellClass .= ' row-z';
                                                    }
                                                    ?>
                                                    <td class="text-center <?= trim($cellClass) ?>">
                                                        <?= formatAngka($nilai) ?>
                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (!$isOptimal && !$isUnbounded): ?>
                                <!-- TAMPILAN: Informasi pivot untuk iterasi ini -->
                                <div class="pivot-info-box mt-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-info-circle text-primary"></i>
                                                <span class="fw-bold">Informasi Pivot</span>
                                            </div>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex justify-content-between px-2 py-1 rounded" style="background: rgba(79,70,229,0.06);">
                                                    <span class="info-label">Variabel Masuk</span>
                                                    <span class="info-value text-primary"><?= $varMasuk ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between px-2 py-1 rounded" style="background: rgba(239,68,68,0.06);">
                                                    <span class="info-label">Variabel Keluar</span>
                                                    <span class="info-value text-danger"><?= $varKeluar ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between px-2 py-1 rounded" style="background: rgba(245,158,11,0.06);">
                                                    <span class="info-label">Elemen Pivot</span>
                                                    <span class="info-value text-warning"><?= formatAngka($elemenPivot) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-bar-chart text-primary"></i>
                                                <span class="fw-bold">Rasio Test</span>
                                            </div>
                                            <table class="table ratio-table mb-0">
                                                <?php foreach ($rasio as $r): ?>
                                                    <tr class="<?= ($r['baris'] == $barPivot) ? 'ratio-min' : '' ?>">
                                                        <td style="width:40px;"><?= htmlspecialchars($varDasar[$r['baris']]) ?>:</td>
                                                        <td>
                                                            <?= formatAngka($r['pembilang']) ?>
                                                            <span class="text-muted">/</span>
                                                            <?= formatAngka($r['penyebut']) ?>
                                                            <?php if ($r['rasio'] !== null): ?>
                                                                <span class="text-muted">=</span>
                                                                <strong><?= formatAngka($r['rasio']) ?></strong>
                                                            <?php else: ?>
                                                                <span class="text-muted">= —</span>
                                                                <small class="text-danger">(tidak memenuhi)</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php if ($r['baris'] == $barPivot): ?>
                                                            <td><i class="bi bi-check-circle-fill text-success"></i></td>
                                                        <?php else: ?>
                                                            <td></td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($isUnbounded): ?>
                                <div class="alert alert-custom alert-warning mt-3 mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                    <span>Tidak Terbatas (Unbounded) – semua nilai kolom pivot ≤ 0.</span>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                <?php endforeach; ?>

                <!-- TAMPILAN: Solusi optimal -->
                <div class="card mb-4">
                    <div class="card-header bg-gradient-success text-white d-flex align-items-center gap-2 py-3">
                        <i class="bi bi-trophy-fill fs-5"></i>
                        <span class="fw-bold">Solusi Optimal</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <?php foreach ($solusi as $var => $nilai): ?>
                                <div class="col-md-4 col-6">
                                    <div class="solution-card">
                                        <div class="var-name">
                                            <i class="bi bi-box text-primary opacity-50 me-1"></i>
                                            <?= $var ?>
                                        </div>
                                        <div class="var-value"><?= formatAngka($nilai) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-md-4 col-6">
                                <div class="solution-card z-card">
                                    <div class="var-name">
                                        <i class="bi bi-trophy text-success opacity-50 me-1"></i>
                                        Z Maksimum
                                    </div>
                                    <div class="var-value" style="color: var(--success-dark);"><?= formatAngka($nilaiZ) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <div class="text-center btn-back-container my-4">
                <a href="index.php" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali ke Input
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
