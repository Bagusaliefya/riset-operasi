<?php
session_start();

if (!isset($_SESSION['hasilGrafik'])) {
    header('Location: index.php');
    exit;
}

$hasil = $_SESSION['hasilGrafik'];

require_once 'includes/helpers.php';

$fungsiTujuan = $hasil['fungsiTujuan'];
$kendala      = $hasil['kendala'];
$nilaiKanan   = $hasil['nilaiKanan'];
$titikPojok   = $hasil['titikPojok'];
$solusi       = $hasil['solusi'];
$nilaiZ       = $hasil['nilaiZ'];
$maxX1        = $hasil['maxX1'];
$maxX2        = $hasil['maxX2'];
$berhasil     = $hasil['berhasil'];
$numConst     = count($kendala);

$graphData = [
    'constraints' => [],
    'cornerPoints' => [],
    'optimal' => ['x1' => $solusi['X1'], 'x2' => $solusi['X2'], 'z' => $nilaiZ],
    'maxX1' => $maxX1,
    'maxX2' => $maxX2,
    'objCoeffs' => $fungsiTujuan,
];

for ($i = 0; $i < $numConst; $i++) {
    $graphData['constraints'][] = [
        'a1' => $kendala[$i][0],
        'a2' => $kendala[$i][1],
        'rhs' => $nilaiKanan[$i],
    ];
}

foreach ($titikPojok as $p) {
    $z = $fungsiTujuan[0] * $p[0] + $fungsiTujuan[1] * $p[1];
    $graphData['cornerPoints'][] = [
        'x1' => $p[0],
        'x2' => $p[1],
        'z' => $z,
        'isOptimal' => (abs($p[0] - $solusi['X1']) < 1e-6 && abs($p[1] - $solusi['X2']) < 1e-6),
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil - Metode Grafik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="card mb-4 text-center">
                <div class="card-header bg-gradient-success text-white py-4">
                    <i class="bi bi-bar-chart-fill fs-1 opacity-75 mb-2 d-block"></i>
                    <h3 class="fw-bold mb-1">Hasil Perhitungan – Metode Grafik</h3>
                    <p class="mb-0 small opacity-75">Pemrograman Linier – Maksimasi dengan Kendala ≤</p>
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
                            <div class="sub-header"><i class="bi bi-bullseye me-1"></i> Fungsi Tujuan</div>
                            <div class="p-3 rounded" style="background: linear-gradient(135deg, #f0f4ff, #e8f0fe);">
                                <strong class="text-primary">Maks Z</strong> =
                                <strong><?= formatAngka($fungsiTujuan[0]) ?></strong>X<sub>1</sub> +
                                <strong><?= formatAngka($fungsiTujuan[1]) ?></strong>X<sub>2</sub>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="sub-header"><i class="bi bi-signpost-2 me-1"></i> Kendala</div>
                            <?php for ($i = 0; $i < $numConst; $i++): ?>
                                <div class="p-2 px-3 rounded mb-1" style="background: #f8fafc; border-left: 3px solid var(--primary);">
                                    <strong><?= formatAngka($kendala[$i][0]) ?></strong>X<sub>1</sub> +
                                    <strong><?= formatAngka($kendala[$i][1]) ?></strong>X<sub>2</sub>
                                    &nbsp;≤&nbsp; <strong class="text-danger"><?= formatAngka($nilaiKanan[$i]) ?></strong>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$berhasil): ?>
                <div class="card mb-4">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                        <h5 class="fw-bold">Tidak Ada Solusi</h5>
                        <p class="text-muted">Masalah tidak memiliki solusi yang memenuhi semua kendala.</p>
                    </div>
                </div>
            <?php else: ?>

            <div class="card mb-4">
                <div class="card-header bg-gradient-info text-white d-flex align-items-center gap-2 py-3">
                    <i class="bi bi-bar-chart fs-5"></i>
                    <span class="fw-bold">Grafik</span>
                </div>
                <div class="card-body text-center">
                    <canvas id="graphCanvas" width="700" height="550"
                            style="max-width:100%; height:auto; border:1px solid #e2e8f0; border-radius:var(--radius-sm);">
                    </canvas>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-gradient-dark text-white d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-geo-alt fs-5"></i>
                            <span class="fw-bold">Titik Pojok</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">Titik</th>
                                            <th class="text-center">X₁</th>
                                            <th class="text-center">X₂</th>
                                            <th class="text-center">Z = <?= formatAngka($fungsiTujuan[0]) ?>X₁ + <?= formatAngka($fungsiTujuan[1]) ?>X₂</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($titikPojok as $idx => $p):
                                            $z = $fungsiTujuan[0] * $p[0] + $fungsiTujuan[1] * $p[1];
                                            $isOpt = (abs($p[0] - $solusi['X1']) < 1e-6 && abs($p[1] - $solusi['X2']) < 1e-6);
                                        ?>
                                        <tr class="<?= $isOpt ? 'table-success fw-bold' : '' ?>">
                                            <td class="text-center fw-bold"><?= chr(65 + $idx) ?></td>
                                            <td class="text-center"><?= formatAngka($p[0]) ?></td>
                                            <td class="text-center"><?= formatAngka($p[1]) ?></td>
                                            <td class="text-center"><?= formatAngka($z) ?></td>
                                            <td class="text-center">
                                                <?php if ($isOpt): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>OPTIMAL</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-gradient-success text-white d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-trophy-fill fs-5"></i>
                            <span class="fw-bold">Solusi Optimal</span>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="solution-card mb-2">
                                <div class="var-name">X₁</div>
                                <div class="var-value"><?= formatAngka($solusi['X1']) ?></div>
                            </div>
                            <div class="solution-card mb-2">
                                <div class="var-name">X₂</div>
                                <div class="var-value"><?= formatAngka($solusi['X2']) ?></div>
                            </div>
                            <div class="solution-card z-card">
                                <div class="var-name">Z Maksimum</div>
                                <div class="var-value" style="color:var(--success-dark)"><?= formatAngka($nilaiZ) ?></div>
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

<script>
const graphData = <?= json_encode($graphData) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/grafik.js"></script>
</body>
</html>
