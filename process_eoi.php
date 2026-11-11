<?php
/** Server-side form handling and MySQL insertion. */
declare(strict_types=1);
session_start();
require_once __DIR__ . '/application_validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apply.php', true, 303);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($submittedToken) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    http_response_code(403);
    exit('Invalid form session. Please return to the application page and try again.');
}

[$data, $errors] = validateApplication($_POST);

if ($errors !== []) {
    $_SESSION['application_errors'] = $errors;
    // Never store token, credentials or unrecognised fields in old form data.
    $_SESSION['application_old'] = array_intersect_key($data, array_flip([
        'job-reference', 'first-name', 'last-name', 'dob', 'gender',
        'street-address', 'suburb', 'state', 'postcode', 'email', 'phone',
        'skills', 'other-skills'
    ]));
    header('Location: apply.php', true, 303);
    exit;
}

$config = require __DIR__ . '/settings.php';
try {
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Required by the assignment: initialise the table if it doesn't already exist.
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS eoi (
    EOInumber INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_reference VARCHAR(5) NOT NULL,
    first_name VARCHAR(20) NOT NULL,
    last_name VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    street_address VARCHAR(40) NOT NULL,
    suburb VARCHAR(40) DEFAULT NULL,
    state ENUM('VIC','NSW','QLD','NT','WA','SA','TAS','ACT') NOT NULL,
    postcode CHAR(4) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(12) NOT NULL,
    skills TEXT NOT NULL,
    other_skills TEXT,
    status ENUM('New','Current','Final') NOT NULL DEFAULT 'New',
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_reference (job_reference),
    INDEX idx_applicant_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    // Use the same cleaned values for duplicate detection and the actual INSERT.
    $applicationValues = [
        'job_reference' => $data['job-reference'],
        'first_name' => $data['first-name'],
        'last_name' => $data['last-name'],
        'dob' => $data['dob_sql'],
        'gender' => $data['gender'],
        'street_address' => $data['street-address'],
        'suburb' => $data['suburb'] !== '' ? $data['suburb'] : null,
        'state' => $data['state'],
        'postcode' => $data['postcode'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'skills' => implode(', ', $data['skills']),
        'other_skills' => $data['other-skills'] !== '' ? $data['other-skills'] : null,
    ];

    // Exact-application duplicate check (excluding EOI number, status, and timestamp).
    // <=> is MySQL's NULL-safe equality for optional fields.
    $duplicateStmt = $pdo->prepare(<<<'SQL'
SELECT EOInumber FROM eoi
WHERE job_reference = :job_reference
  AND first_name = :first_name
  AND last_name = :last_name
  AND dob = :dob
  AND gender = :gender
  AND street_address = :street_address
  AND suburb <=> :suburb
  AND state = :state
  AND postcode = :postcode
  AND email = :email
  AND phone = :phone
  AND skills = :skills
  AND other_skills <=> :other_skills
LIMIT 1
SQL);
    $duplicateStmt->execute($applicationValues);

    if ($duplicateStmt->fetchColumn() !== false) {
        $_SESSION['application_errors'] = [
            'duplicate' => 'An application with the same details has already been submitted.'
        ];
        $_SESSION['application_old'] = array_intersect_key($data, array_flip([
            'job-reference', 'first-name', 'last-name', 'dob', 'gender',
            'street-address', 'suburb', 'state', 'postcode', 'email', 'phone',
            'skills', 'other-skills'
        ]));
        header('Location: apply.php', true, 303);
        exit;
    }

    $stmt = $pdo->prepare(<<<'SQL'
INSERT INTO eoi (
    job_reference, first_name, last_name, dob, gender,
    street_address, suburb, state, postcode, email, phone, skills, other_skills
) VALUES (
    :job_reference, :first_name, :last_name, :dob, :gender,
    :street_address, :suburb, :state, :postcode, :email, :phone, :skills, :other_skills
)
SQL);
    $stmt->execute($applicationValues);
    $eoiNumber = $pdo->lastInsertId();
    // Use a fresh CSRF token after submission.
    unset($_SESSION['csrf_token'], $_SESSION['application_errors'], $_SESSION['application_old']);
} catch (PDOException $exception) {
    // Do not print SQL credentials or exception details to the user.
    error_log('UrbanSync application database error: ' . $exception->getMessage());
    http_response_code(500);
    exit('The application could not be saved. Check your local MySQL setup or contact the site administrator.');
}

function escapeOutput(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Received | UrbanSync</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main style="max-width: 700px; margin: 5rem auto; padding: 1.5rem;">
    <h1>Application submitted successfully</h1>
    <p>Thank you, <?= escapeOutput($data['first-name']) ?>. Your Expression of Interest has been saved.</p>
    <p><strong>Your EOI number: <?= escapeOutput((string) $eoiNumber) ?></strong></p>
    <p>The application's initial status is <strong>New</strong>.</p>
    <p><a href="jobs.php">Return to job listings</a></p>
</main>
</body>
</html>
