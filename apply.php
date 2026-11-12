<?php
session_start();
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$errors = $_SESSION['application_errors'] ?? [];
$old = $_SESSION['application_old'] ?? [];
unset($_SESSION['application_errors'], $_SESSION['application_old']);

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function oldValue(string $key): string {
    global $old;
    return e(is_string($old[$key] ?? null) ? $old[$key] : '');
}
function isSelected(string $key, string $value): string {
    global $old;
    return ($old[$key] ?? '') === $value ? 'selected' : '';
}
function isChecked(string $key, string $value): string {
    global $old;
    $stored = $old[$key] ?? null;
    return (is_array($stored) ? in_array($value, $stored, true) : $stored === $value) ? 'checked' : '';
}

$jobs = [];
$jobsLoadError = false;
try {
    $config = require __DIR__ . '/settings.php';
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $jobs = $pdo->query(<<<'SQL'
SELECT job_reference, job_title
FROM jobs
WHERE status = 'Open'
  AND (closing_date IS NULL OR closing_date >= CURDATE())
ORDER BY job_title ASC
SQL)->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load available jobs: ' . $exception->getMessage());
    $jobsLoadError = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for a Position | UrbanSync Infrastructure Consultancy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .application-intro {
            text-align: center;
        }
        .application-form {
            max-width: 900px;
            margin: 0 auto;
        }
        .form-note {
            font-size: 0.9rem;
            color: #555555;
        }
    </style>
</head>
<body>
    <!-- ==================== HEADER ==================== -->
    <?php include 'header.inc'; ?>
    <!-- ==================== Audio ==================== -->
    <audio id="submit-sound" src="apply.wav" preload="auto"></audio>
    <!-- ==================== MAIN CONTENT ==================== -->
    <main>
        <!-- Page Introduction -->
        <section class="application-intro">
            <h1>Job Application</h1>
            <p>Complete the form below to apply for a position at UrbanSync Infrastructure Consultancy.</p>
            <p class="form-note">Fields marked with an asterisk (*) are required. Applicants must be at least 18 years old on the date they apply. Enter your date of birth in dd/mm/yyyy format.</p>
            <?php if ($errors !== []): ?>
                <div role="alert" class="validation-errors" style="max-width:900px; margin:1rem auto; padding:1rem; border:2px solid #b42318; border-radius:0.5rem;">
                    <h2 style="font-size:1.15rem;">Please correct the following:</h2>
                    <ul>
                        <?php foreach ($errors as $field => $message): ?>
                            <li><?= e($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>

        <!-- ==================== APPLICATION FORM ==================== -->
        <section>
            <?php if ($jobsLoadError): ?>
                <p role="alert">Job listings could not be loaded. Please check that MySQL is running and that the jobs table is available.</p>
            <?php elseif ($jobs === []): ?>
                <p role="status">There are no open jobs accepting applications at the moment.</p>
            <?php endif; ?>
            <form class="application-form" action="process_eoi.php" method="post" novalidate>
                <!-- HTML validation is disabled as for Part 2; PHP validates all inputs. -->
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <!-- ==================== JOB INFORMATION ==================== -->
                <fieldset>
                    <legend>Position Details</legend>
                    <div class="form-grid">
                        <div>
                            <label for="job-reference">Job Reference *</label>
                            <select id="job-reference" name="job-reference" <?= $jobs === [] ? 'disabled' : '' ?>>
                                <option value=""><?= $jobs === [] ? 'No open jobs available' : 'Select a job reference' ?></option>
                                <?php foreach ($jobs as $job): ?>
                                    <?php 
                                        $ref = $job['job_reference']; 
                                        $title = $job['job_title']; 
                                    ?>
                                    <option value="<?= e($ref) ?>" <?= isSelected('job-reference', $ref) ?>>
                                        <?= e($ref . ' - ' . $title) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- ==================== PERSONAL INFORMATION ==================== -->
                <fieldset>
                    <legend>Personal Information</legend>
                    <div class="form-grid">
                        <div>
                            <label for="first-name">First Name *</label>
                            <input type="text" id="first-name" name="first-name" maxlength="20" autocomplete="given-name" value="<?= oldValue('first-name') ?>">
                        </div>
                        <div>
                            <label for="last-name">Last Name *</label>
                            <input type="text" id="last-name" name="last-name" maxlength="20" autocomplete="family-name" value="<?= oldValue('last-name') ?>">
                        </div>
                        <div>
                            <label for="dob">Date of Birth *</label>
                            <input type="text" id="dob" name="dob" placeholder="dd/mm/yyyy" maxlength="10" inputmode="numeric" value="<?= oldValue('dob') ?>">
                        </div>
                    </div>

                    <!-- Gender -->
                    <fieldset>
                        <legend>Gender *</legend>
                        <div class="radio-group">
                            <label><input type="radio" name="gender" value="male" <?= isChecked('gender', 'male') ?>> Male</label>
                            <label><input type="radio" name="gender" value="female" <?= isChecked('gender', 'female') ?>> Female</label>
                            <label><input type="radio" name="gender" value="other" <?= isChecked('gender', 'other') ?>> Other</label>
                        </div>
                    </fieldset>
                </fieldset>

                <!-- ==================== ADDRESS INFORMATION ==================== -->
                <fieldset>
                    <legend>Contact and Address</legend>
                    <div class="form-grid">
                        <div>
                            <label for="street-address">Street Address *</label>
                            <input type="text" id="street-address" name="street-address" maxlength="40" autocomplete="street-address" placeholder="e.g. Unit 2/15 Main Street" value="<?= oldValue('street-address') ?>">
                            <small>Use 4–40 characters, including the street name (letters); apartment/unit numbers and common punctuation are allowed.</small>
                        </div>
                        <div>
                            <label for="suburb">Suburb / Town (optional)</label>
                            <input type="text" id="suburb" name="suburb" maxlength="40" autocomplete="address-level2" value="<?= oldValue('suburb') ?>">
                        </div>
                        <div>
                            <label for="state">State *</label>
                            <select id="state" name="state">
                                <option value="">Select a state</option>
                                <option value="VIC" <?= isSelected('state', 'VIC') ?>>VIC (Victoria)</option>
                                <option value="NSW" <?= isSelected('state', 'NSW') ?>>NSW (New South Wales)</option>
                                <option value="QLD" <?= isSelected('state', 'QLD') ?>>QLD (Queensland)</option>
                                <option value="NT" <?= isSelected('state', 'NT') ?>>NT (Northern Territory)</option>
                                <option value="WA" <?= isSelected('state', 'WA') ?>>WA (Western Australia)</option>
                                <option value="SA" <?= isSelected('state', 'SA') ?>>SA (South Australia)</option>
                                <option value="TAS" <?= isSelected('state', 'TAS') ?>>TAS (Tasmania)</option>
                                <option value="ACT" <?= isSelected('state', 'ACT') ?>>ACT (Australian Capital Territory)</option>
                            </select>
                        </div>
                        <div>
                            <label for="postcode">Postcode *</label>
                            <input type="text" id="postcode" name="postcode" maxlength="4" inputmode="numeric" autocomplete="postal-code" value="<?= oldValue('postcode') ?>">
                        </div>
                        <div>
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" autocomplete="email" value="<?= oldValue('email') ?>">
                        </div>
                        <div>
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" autocomplete="tel" value="<?= oldValue('phone') ?>">
                        </div>
                    </div>
                </fieldset>

                <!-- ==================== SKILLS ==================== -->
                <fieldset>
                    <legend>Skills *</legend>
                    <p>Select the skills that best match your experience.</p>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="skills[]" value="data-analysis" <?= isChecked('skills', 'data-analysis') ?>> Data Analysis</label>
                        <label><input type="checkbox" name="skills[]" value="power-bi" <?= isChecked('skills', 'power-bi') ?>> Power BI</label>
                        <label><input type="checkbox" name="skills[]" value="sql" <?= isChecked('skills', 'sql') ?>> SQL</label>
                        <label><input type="checkbox" name="skills[]" value="python" <?= isChecked('skills', 'python') ?>> Python</label>
                        <label><input type="checkbox" name="skills[]" value="project-management" <?= isChecked('skills', 'project-management') ?>> Project Management</label>
                    </div>
                </fieldset>

                <!-- ==================== OTHER SKILLS ==================== -->
                <fieldset>
                    <legend>Additional Information</legend>
                    <label for="other-skills">Other Skills</label>
                    <textarea id="other-skills" name="other-skills" rows="6" maxlength="2000" placeholder="Describe any other relevant skills, experience, or qualifications."><?= oldValue('other-skills') ?></textarea>
                </fieldset>

                <!-- ==================== FORM BUTTONS ==================== -->
                <div class="form-buttons">
                    <button type="submit" >Submit Application</button>
                    <button type="reset">Clear Form</button>
                </div>
            </form>
        </section>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <?php include 'footer.inc'; ?>
</body>
</html>