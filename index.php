<?php
session_start();
unset($_SESSION['hasil']);
unset($_SESSION['hasilGrafik']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Simpleks & Grafik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <div class="card">
                <div class="card-header bg-gradient-primary text-white text-center py-4">
                    <div class="mb-2">
                        <i class="bi bi-graph-up-arrow fs-1 opacity-75"></i>
                    </div>
                    <h3 class="mb-1 fw-bold">Riset Operasi</h3>
                    <p class="mb-0 small opacity-75">
                        <i class="bi bi-dot"></i> Pemrograman Linier – Maksimasi dengan Kendala ≤
                    </p>
                </div>
                <div class="card-body p-4 p-md-5">

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-custom alert-warning d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center mb-4">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="method" id="methodSimpleks" value="simpleks" checked>
                            <label class="btn btn-outline-primary btn-lg px-4" for="methodSimpleks">
                                <i class="bi bi-table me-1"></i> Simpleks
                            </label>
                            <input type="radio" class="btn-check" name="method" id="methodGrafik" value="grafik">
                            <label class="btn btn-outline-primary btn-lg px-4" for="methodGrafik">
                                <i class="bi bi-bar-chart me-1"></i> Grafik
                            </label>
                        </div>
                    </div>

                    <div id="langkahAwal">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-pill me-2 px-3 py-2">1</span>
                            <h5 class="mb-0 fw-bold">Tentukan Ukuran Masalah</h5>
                        </div>
                        <p class="text-muted small ms-4 mb-3" id="step1Desc">
                            Masukkan jumlah variabel keputusan dan jumlah kendala
                        </p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6" id="varInputGroup">
                                <label for="num_vars" class="form-label">
                                    <i class="bi bi-box-seam me-1 text-primary"></i>
                                    Jumlah Variabel Keputusan
                                </label>
                                <input type="number" id="num_vars" class="form-control form-control-lg"
                                       min="1" max="10" value="2" placeholder="Contoh: 2">
                                <div class="form-text" id="varHelp">
                                    <i class="bi bi-info-circle"></i> Contoh: X₁, X₂ = 2 variabel
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="num_constraints" class="form-label">
                                    <i class="bi bi-list-check me-1 text-primary"></i>
                                    Jumlah Kendala
                                </label>
                                <input type="number" id="num_constraints" class="form-control form-control-lg"
                                       min="1" max="10" value="2" placeholder="Contoh: 2">
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Contoh: 2 buah kendala
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button id="btnGenerate" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>
                                Generate Form Input
                            </button>
                        </div>
                    </div>

                    <div id="formContainer" style="display: none;">
                        <hr class="my-4 opacity-25">

                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary rounded-pill me-2 px-3 py-2">2</span>
                            <h5 class="mb-0 fw-bold">Isi Koefisien</h5>
                        </div>

                        <form id="problemForm" method="POST" action="process.php" novalidate>
                            <input type="hidden" name="method" id="hiddenMethod">
                            <input type="hidden" name="num_vars" id="hiddenNumVars">
                            <input type="hidden" name="num_constraints" id="hiddenNumConstraints">

                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-bullseye text-primary fs-5"></i>
                                    <h6 class="fw-bold mb-0">Fungsi Tujuan <span class="fw-normal text-muted">(Maksimasi)</span></h6>
                                </div>
                                <div class="p-3" style="background: linear-gradient(135deg, #f0f4ff, #e8f0fe); border-radius: var(--radius-sm);">
                                    <div class="row g-2 align-items-center" id="objectiveContainer">
                                    </div>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Masukkan koefisien untuk setiap variabel keputusan
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-signpost-2 text-primary fs-5"></i>
                                    <h6 class="fw-bold mb-0">Kendala <span class="fw-normal text-muted">(≤)</span></h6>
                                </div>
                                <div id="constraintsContainer">
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Masukkan koefisien kendala dan nilai kanan (RHS)
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 pt-3 border-top">
                                <button type="button" id="btnBack" class="btn btn-outline-secondary order-2 order-md-1">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="submit" class="btn btn-success btn-lg px-5 order-1 order-md-2">
                                    <i class="bi bi-calculator me-2"></i>
                                    Hitung Solusi Optimal
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
                <div class="card-footer text-center py-3">
                    <i class="bi bi-journal-code me-1"></i>
                    Tugas Riset Operasi
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
