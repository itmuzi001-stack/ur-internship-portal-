<?php
// Fetch admin-uploaded schools dynamically for the student selection dropdown
$schoolsStmt = $pdo->query("SELECT school_code, school_name, district FROM placement_schools ORDER BY district, school_name ASC");
$availableSchools = $schoolsStmt->fetchAll();
?>

<!-- Inside your Step 3 HTML Form (School Selection) -->
<div class="form-group">
    <label for="school_choice">Choose Host Secondary School:</label>
    <select name="school_choice" id="school_choice" required style="width: 100%; padding: 0.75rem; border: 1px solid #D9E0EA; border-radius: 6px;">
        <option value="">-- Select an Approved Placement School --</option>
        <?php if (count($availableSchools) > 0): ?>
            <?php foreach ($availableSchools as $school): ?>
                <option value="<?= htmlspecialchars($school['school_name']) ?>">
                    <?= htmlspecialchars($school['school_name']) ?> (District: <?= htmlspecialchars($school['district']) ?>)
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="" disabled>No placement schools uploaded yet. Please check back later.</option>
        <?php endif; ?>
    </select>
</div>