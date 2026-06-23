document.addEventListener('DOMContentLoaded', function () {

    const btnGenerate      = document.getElementById('btnGenerate');
    const btnBack          = document.getElementById('btnBack');
    const langkahAwal      = document.getElementById('langkahAwal');
    const formContainer    = document.getElementById('formContainer');
    const objectiveCont    = document.getElementById('objectiveContainer');
    const constraintsCont  = document.getElementById('constraintsContainer');
    const hiddenMethod     = document.getElementById('hiddenMethod');
    const hiddenNumVars    = document.getElementById('hiddenNumVars');
    const hiddenNumConst   = document.getElementById('hiddenNumConstraints');
    const numVarsInput     = document.getElementById('num_vars');
    const numConstInput    = document.getElementById('num_constraints');
    const problemForm      = document.getElementById('problemForm');
    const varInputGroup    = document.getElementById('varInputGroup');
    const varHelp          = document.getElementById('varHelp');
    const step1Desc        = document.getElementById('step1Desc');

    const methodRadios     = document.querySelectorAll('input[name="method"]');

    function getMethod() {
        return document.querySelector('input[name="method"]:checked').value;
    }

    methodRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            objectiveCont.innerHTML = '';
            constraintsCont.innerHTML = '';
            if (formContainer.style.display === 'block') {
                backToStep1();
            }
            if (this.value === 'grafik') {
                numVarsInput.value = 2;
                numVarsInput.disabled = true;
                numConstInput.value = 2;
                varInputGroup.classList.add('opacity-50');
                varHelp.innerHTML = '<i class="bi bi-info-circle"></i> Metode Grafik hanya untuk 2 variabel (X\u2081, X\u2082)';
                step1Desc.textContent = 'Metode Grafik \u2014 masukkan jumlah kendala (variabel tetap 2)';
            } else {
                numVarsInput.value = 2;
                numVarsInput.disabled = false;
                varInputGroup.classList.remove('opacity-50');
                varHelp.innerHTML = '<i class="bi bi-info-circle"></i> Contoh: X\u2081, X\u2082 = 2 variabel';
                step1Desc.textContent = 'Masukkan jumlah variabel keputusan dan jumlah kendala';
            }
        });
    });

    function generateForm() {
        const method = getMethod();
        let n, m;

        if (method === 'grafik') {
            n = 2;
            numVarsInput.value = 2;
        } else {
            n = parseInt(numVarsInput.value);
        }
        m = parseInt(numConstInput.value);

        if (method === 'simpleks' && (isNaN(n) || n < 1)) {
            alert('Jumlah variabel keputusan minimal 1.');
            numVarsInput.focus();
            return;
        }
        if (isNaN(m) || m < 1) {
            alert('Jumlah kendala minimal 1.');
            numConstInput.focus();
            return;
        }
        if (n > 10 || m > 10) {
            alert('Maksimal 10 variabel dan 10 kendala.');
            return;
        }

        hiddenMethod.value = method;
        hiddenNumVars.value = n;
        hiddenNumConst.value = m;

        generateObjective(n);
        generateConstraints(n, m);

        problemForm.action = (method === 'grafik') ? 'process_grafik.php' : 'process.php';

        langkahAwal.style.display = 'none';
        formContainer.style.display = 'block';
    }

    function generateObjective(n) {
        let html = '';
        html += '<div class="col-auto"><span class="fw-bold fs-5">Z =</span></div>';

        for (let i = 0; i < n; i++) {
            html += '<div class="col">';
            html += '<div class="input-group">';
            html += '<input type="number" step="any" class="form-control text-center" ';
            html += 'name="objective[]" value="0" required>';
            html += '<span class="input-group-text">X<sub>' + (i + 1) + '</sub></span>';
            html += '</div>';
            html += '</div>';
            if (i < n - 1) {
                html += '<div class="col-auto"><span class="fs-5">+</span></div>';
            }
        }

        objectiveCont.innerHTML = html;
    }

    function generateConstraints(n, m) {
        let html = '';

        for (let i = 0; i < m; i++) {
            html += '<div class="card bg-light mb-3">';
            html += '<div class="card-body py-3">';
            html += '<div class="row g-2 align-items-center">';
            html += '<div class="col-auto"><span class="fw-semibold">' + (i + 1) + '.</span></div>';

            for (let j = 0; j < n; j++) {
                html += '<div class="col">';
                html += '<div class="input-group input-group-sm">';
                html += '<input type="number" step="any" class="form-control text-center" ';
                html += 'name="constraint[' + i + '][]" value="0" required>';
                html += '<span class="input-group-text">X<sub>' + (j + 1) + '</sub></span>';
                html += '</div>';
                html += '</div>';
                if (j < n - 1) {
                    html += '<div class="col-auto"><span>+</span></div>';
                }
            }

            html += '<div class="col-auto"><span class="fw-bold">\u2264</span></div>';
            html += '<div class="col-2">';
            html += '<input type="number" step="any" class="form-control text-center" ';
            html += 'name="rhs[]" value="0" required>';
            html += '</div>';

            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        constraintsCont.innerHTML = html;
    }

    function backToStep1() {
        formContainer.style.display = 'none';
        langkahAwal.style.display = 'block';
    }

    btnGenerate.addEventListener('click', generateForm);
    btnBack.addEventListener('click', backToStep1);

    numVarsInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            generateForm();
        }
    });

    numConstInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            generateForm();
        }
    });

});
