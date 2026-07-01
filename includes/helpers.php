<?php
function formatAngka($nilai) {
    if (abs($nilai - round($nilai)) < 1e-6)
        return number_format(round($nilai), 0, ',', '');
    return number_format($nilai, 2, ',', '');
}
