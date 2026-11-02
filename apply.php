<?php
// apply.php
session_start();
$pageTitle = "Apply for Internship - UR Internship Portal";

require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$colleges = [];
$schools = [];

if ($pdo) {
    try {
        // Fetch colleges from database for Step 1
        $colleges = $pdo->query("SELECT * FROM colleges ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        // Fetch active placement host schools from admin/schools.php table for Step 3
        $schools = $pdo->query("SELECT id, school_name, province, district, cell, village FROM schools WHERE is_active = 1 ORDER BY school_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error loading form options from database.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college     = trim($_POST['college'] ?? '');
    $acadSchool  = trim($_POST['school'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $combination = trim($_POST['combination'] ?? '');
    $regNumber   = trim($_POST['registration_number'] ?? '');
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $hostSchool  = trim($_POST['placement_school'] ?? '');

    if (empty($college) || empty($acadSchool) || empty($department) || empty($combination) || empty($regNumber) || empty($fullName) || empty($hostSchool)) {
        $error = "Please complete all required fields across the steps.";
    } elseif ($pdo) {
        try {
            $trackingCode = "UR-INT-" . strtoupper(bin2hex(random_bytes(3)));
            
            $stmt = $pdo->prepare("
                INSERT INTO applications (user_id, registration_number, college, department, combination, placement_school, tracking_code, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Approved')
            ");
            $deptCombined = $acadSchool . ' / ' . $department;
            $stmt->execute([$_SESSION['user_id'], $regNumber, $college, $deptCombined, $combination, $hostSchool, $trackingCode]);

            header("Location: dashboard.php?applied=success");
            exit();
        } catch (PDOException $e) {
            $error = "Submission failed: " . htmlspecialchars($e->getMessage());
        }
    }
}

include_once __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #F3F6FA; margin: 0; padding: 0; color: #0F1B2D; }
        .dash-header { background-color: #0F1B2D; color: #FFFFFF; padding: 1rem 2rem; border-bottom: 4px solid #E8A317; }
        .dash-nav-wrapper { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .dash-header h1 { margin: 0; font-size: 1.25rem; color: #60A5FA; }
        .dash-nav-links { display: flex; gap: 1.5rem; align-items: center; list-style: none; margin: 0; padding: 0; }
        .dash-nav-links a { color: #CBD5E1; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s; }
        .dash-nav-links a:hover { color: #E8A317; }
        .btn-logout { background-color: #DC2626 !important; color: #FFFFFF !important; padding: 0.4rem 0.9rem; border-radius: 4px; }
        .btn-amber { background-color: #E8A317; color: #0F1B2D; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; border: none; cursor: pointer; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #334155; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

<!-- Standalone Dashboard Header -->
<header class="dash-header">
    <div class="dash-nav-wrapper">
        <h1>🎓 UR Student Portal</h1>
        <ul class="dash-nav-links">
            <li><a href="dashboard.php">Dashboard & Status</a></li>
            <li><a href="logout.php" class="btn-logout">Log Out</a></li>
        </ul>
    </div>
</header>

<main id="main-content" class="container" style="padding: 3rem 1rem; min-height: 60vh; max-width: 900px; margin: 0 auto;">
    <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
        <h1>Year 4 Internship Placement Stepper</h1>
        <p>Complete all four steps to register your placement preference.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background-color: #f8d7da; color: #842029; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid #f5c2c7;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif;?>

    <!-- Visual Stepper Progress Bar -->
    <div class="stepper-bar" style="display: flex; justify-content: space-between; margin-bottom: 2rem; gap: 0.5rem;">
        <div class="step-item active" id="indicator-1" style="flex: 1; text-align: center;">
            <div class="step-circle" style="background: #12356B; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: bold;">1</div>
            <div class="step-label" style="font-size: 0.85rem; font-weight: 600;">Academic Details</div>
        </div>
        <div class="step-item" id="indicator-2" style="flex: 1; text-align: center;">
            <div class="step-circle" style="background: #e2e8f0; color: #64748B; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: bold;">2</div>
            <div class="step-label" style="font-size: 0.85rem; font-weight: 600;">Student ID</div>
        </div>
        <div class="step-item" id="indicator-3" style="flex: 1; text-align: center;">
            <div class="step-circle" style="background: #e2e8f0; color: #64748B; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: bold;">3</div>
            <div class="step-label" style="font-size: 0.85rem; font-weight: 600;">Choose School</div>
        </div>
        <div class="step-item" id="indicator-4" style="flex: 1; text-align: center;">
            <div class="step-circle" style="background: #e2e8f0; color: #64748B; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: bold;">4</div>
            <div class="step-label" style="font-size: 0.85rem; font-weight: 600;">Review & Submit</div>
        </div>
    </div>

    <form action="apply.php" method="POST" id="stepperForm" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2.5rem;">
        
        <!-- STEP 1: Academic Hierarchy -->
        <div class="form-step active" id="step-1">
            <h2 style="font-size: 1.25rem; color: #12356B; margin-bottom: 1.5rem;">Step 1: Academic Unit & Combination</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                <div>
                    <label for="college" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">1. Select College *</label>
                    <select id="college" name="college" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 0.95rem;">
                        <option value="">-- Choose College --</option>
                        <?php foreach ($colleges as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="school" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">2. Select School *</label>
                    <select id="school" name="school" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 0.95rem;">
                        <option value="">-- Choose College First --</option>
                    </select>
                </div>
                <div>
                    <label for="department" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">3. Select Department *</label>
                    <select id="department" name="department" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 0.95rem;">
                        <option value="">-- Choose School First --</option>
                    </select>
                </div>
                <div>
                    <label for="combination" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">4. Select Combination / Program *</label>
                    <select id="combination" name="combination" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 0.95rem;">
                        <option value="">-- Choose Department First --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- STEP 2: Student Identification -->
        <div class="form-step" id="step-2" style="display:none;">
            <h2 style="font-size: 1.25rem; color: #12356B; margin-bottom: 1.5rem;">Step 2: Registration & Identity</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label for="registration_number" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Registration Number *</label>
                    <input type="text" id="registration_number" name="registration_number" value="225018998" required placeholder="e.g. 219001234" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 1rem;">
                </div>
                <div>
                    <label for="full_name" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="UTUJE Alice" required placeholder="First Name and Last Name" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 1rem;">
                </div>
                <div>
                    <label for="email" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">University Email *</label>
                    <input type="email" id="email" name="email" value="utujealice00@gmail.com" required placeholder="student@stud.ur.ac.rw" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 1rem;">
                </div>
                <div>
                    <label for="phone" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" value="0793272252" required placeholder="078XXXXXXXX" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 1rem;">
                </div>
            </div>
        </div>

        <!-- STEP 3: Host School Choice (from `schools` table managed via admin/schools.php) -->
        <div class="form-step" id="step-3" style="display:none;">
            <h2 style="font-size: 1.25rem; color: #12356B; margin-bottom: 1.5rem;">Step 3: Select Host Internship School</h2>
            <div>
                <label for="placement_school" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Eligible Secondary School Placement *</label>
                <select id="placement_school" name="placement_school" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; font-size: 1rem;">
                    <option value="">-- Choose Host Placement School --</option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?= htmlspecialchars($s['school_name']) ?>">
                            <?= htmlspecialchars($s['school_name']) ?> — <?= htmlspecialchars($s['district']) ?> District, <?= htmlspecialchars($s['province']) ?> Province (Cell: <?= htmlspecialchars($s['cell']) ?>, Village: <?= htmlspecialchars($s['village']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- STEP 4: Review Details -->
        <div class="form-step" id="step-4" style="display:none;">
            <h2 style="font-size: 1.25rem; color: #12356B; margin-bottom: 1.5rem;">Step 4: Review and Submit</h2>
            <div style="background: #f8fafc; padding: 1.5rem; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; flex-direction: column; gap: 0.75rem;" id="reviewSummary">
                <!-- Dynamic review populated via JS -->
            </div>
        </div>

        <!-- Stepper Navigation Buttons with Cancel Option -->
        <div style="display: flex; justify-content: space-between; margin-top: 2rem; border-top: 1px solid #cbd5e1; padding-top: 1.5rem;">
            <div>
                <button type="button" id="prevBtn" class="btn-outline" style="display: none;" onclick="changeStep(-1)">Previous</button>
                <a href="dashboard.php" class="btn-outline">Cancel / Back to Dashboard</a>
            </div>
            <div>
                <button type="button" id="nextBtn" class="btn-amber" onclick="changeStep(1)">Next Step</button>
                <button type="submit" id="submitBtn" class="btn-amber" style="display: none;">Submit Application</button>
            </div>
        </div>
    </form>
</main>

<script>
// Dynamic Cascading AJAX Event Listeners
document.getElementById('college').addEventListener('change', function() {
    const collegeId = this.options[this.selectedIndex].getAttribute('data-id');
    const schoolSelect = document.getElementById('school');
    const deptSelect = document.getElementById('department');
    const combSelect = document.getElementById('combination');

    schoolSelect.innerHTML = '<option value="">-- Loading Schools... --</option>';
    deptSelect.innerHTML = '<option value="">-- Choose School First --</option>';
    combSelect.innerHTML = '<option value="">-- Choose Department First --</option>';

    if (collegeId) {
        fetch('get-academic-data.php?action=get_schools&college_id=' + collegeId)
            .then(res => res.json())
            .then(data => {
                schoolSelect.innerHTML = '<option value="">-- Choose School --</option>';
                data.forEach(sch => {
                    schoolSelect.innerHTML += `<option value="${sch.name}" data-id="${sch.id}">${sch.name}</option>`;
                });
            });
    }
});

document.getElementById('school').addEventListener('change', function() {
    const schoolId = this.options[this.selectedIndex].getAttribute('data-id');
    const deptSelect = document.getElementById('department');
    const combSelect = document.getElementById('combination');

    deptSelect.innerHTML = '<option value="">-- Loading Departments... --</option>';
    combSelect.innerHTML = '<option value="">-- Choose Department First --</option>';

    if (schoolId) {
        fetch('get-academic-data.php?action=get_departments&school_id=' + schoolId)
            .then(res => res.json())
            .then(data => {
                deptSelect.innerHTML = '<option value="">-- Choose Department --</option>';
                data.forEach(dept => {
                    deptSelect.innerHTML += `<option value="${dept.name}" data-id="${dept.id}">${dept.name}</option>`;
                });
            });
    }
});

document.getElementById('department').addEventListener('change', function() {
    const deptId = this.options[this.selectedIndex].getAttribute('data-id');
    const combSelect = document.getElementById('combination');
    combSelect.innerHTML = '<option value="">-- Loading Combinations... --</option>';

    if (deptId) {
        fetch('get-academic-data.php?action=get_combinations&department_id=' + deptId)
            .then(res => res.json())
            .then(data => {
                combSelect.innerHTML = '<option value="">-- Choose Combination / Program --</option>';
                data.forEach(comb => {
                    combSelect.innerHTML += `<option value="${comb.name}">${comb.name}</option>`;
                });
            });
    }
});

// Multi-Step Navigation & Stepper Controller
let currentStep = 1;
function changeStep(direction) {
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step-item');
        
    if (direction === 1) {
        const activeFields = steps[currentStep - 1].querySelectorAll('input[required], select[required]');
        for (let field of activeFields) {
            if (!field.value.trim()) {
                field.reportValidity();
                return;
            }
        }
    }

    steps[currentStep - 1].style.display = 'none';
    indicators[currentStep - 1].classList.remove('active');

    currentStep += direction;
    steps[currentStep - 1].style.display = 'block';
    indicators[currentStep - 1].classList.add('active');

    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-flex';
    document.getElementById('nextBtn').style.display = currentStep === 4 ? 'none' : 'inline-flex';
    document.getElementById('submitBtn').style.display = currentStep === 4 ? 'inline-flex' : 'none';

    if (currentStep === 4) {
        document.getElementById('reviewSummary').innerHTML = `
            <div><strong>College:</strong> ${document.getElementById('college').value}</div>
            <div><strong>School:</strong> ${document.getElementById('school').value}</div>
            <div><strong>Department:</strong> ${document.getElementById('department').value}</div>
            <div><strong>Combination / Program:</strong> <span style="color: #12356B; font-weight:700;">${document.getElementById('combination').value}</span></div>
            <div><strong>Registration Number:</strong> ${document.getElementById('registration_number').value}</div>
            <div><strong>Student Name:</strong> ${document.getElementById('full_name').value}</div>
            <div><strong>Email & Phone:</strong> ${document.getElementById('email').value} | ${document.getElementById('phone').value}</div>
            <div><strong>Selected Host Placement School:</strong> <span style="color: #198754; font-weight:700;">${document.getElementById('placement_school').value}</span></div>
        `;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Handle form submission via button click if needed
const submitBtn = document.getElementById('submitBtn');
const stepperForm = document.getElementById('stepperForm');
if (submitBtn && stepperForm) {
    submitBtn.addEventListener('click', function(e) {
        stepperForm.submit();
    });
}
</script>
</body>
</html>