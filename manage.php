<?php
/** HR manager: list, filter, sort, update status, and delete EOIs by job reference. */
declare(strict_types=1);
require_once __DIR__ . '/manager_functions.php';
managerStartSession();
managerRequireLogin();

// Allowed values only: do not concatenate arbitrary input into SQL ORDER BY.
$sortColumns = [
    'eoi' => 'EOInumber',
    'date' => 'applied_at',
    'job' => 'job_reference',
    'first' => 'first_name',
    'last' => 'last_name',
    'status' => 'status',
];
$statuses = ['New', 'Current', 'Final'];

// Changes must always use POST and a valid token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!managerValidCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid manager form token. Reload manage.php and try again.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        $pdo = managerDatabase();
        if ($action === 'update_status') {
            $eoiNumber = filter_var($_POST['eoi_number'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $status = $_POST['status'] ?? null;
            if ($eoiNumber === false || !is_string($status) || !in_array($status, $statuses, true)) {
                managerSetMessage('error', 'Choose a valid EOI number and status.');
            } else {
                $stmt = $pdo->prepare('UPDATE eoi SET status = :status WHERE EOInumber = :eoi_number');
                $stmt->execute(['status' => $status, 'eoi_number' => $eoiNumber]);
                // A zero affected-row count may also mean the status was already the same.
                $check = $pdo->prepare('SELECT status FROM eoi WHERE EOInumber = :eoi_number');
                $check->execute(['eoi_number' => $eoiNumber]);
                $savedStatus = $check->fetchColumn();
                managerSetMessage(
                    $savedStatus === $status ? 'success' : 'error',
                    $savedStatus === $status
                        ? "EOI #{$eoiNumber} is now {$status}."
                        : "EOI #{$eoiNumber} was not found or could not be updated."
                );
            }
        } elseif ($action === 'delete_job') {
            $reference = $_POST['job_reference'] ?? null;
            $reference = is_string($reference) ? strtoupper(trim($reference)) : '';
            if (!preg_match('/^[A-Z0-9]{5}$/D', $reference)) {
                managerSetMessage('error', 'Enter a valid five-character job reference.');
            } else {
                // The user already visited the confirmation screen. Server still checks CSRF.
                $stmt = $pdo->prepare('DELETE FROM eoi WHERE job_reference = :job_reference');
                $stmt->execute(['job_reference' => $reference]);
                $removed = $stmt->rowCount();
                managerSetMessage('success', "Deleted {$removed} EOI record(s) for {$reference}.");
            }
        } else {
            managerSetMessage('error', 'Unknown manager operation.');
        }
    } catch (PDOException $exception) {
        error_log('UrbanSync manage action database error: ' . $exception->getMessage());
        managerSetMessage('error', 'Database operation failed. Please verify your local MySQL setup.');
    }

    // Post/Redirect/Get: refresh never repeats an UPDATE or DELETE operation.
    header('Location: manage.php', true, 303);
    exit;
}

// GET parameters control what to show; they never change database data.
$jobReference = is_string($_GET['job_reference'] ?? null)
    ? strtoupper(trim($_GET['job_reference'])) : '';
$firstName = is_string($_GET['first_name'] ?? null)
    ? trim($_GET['first_name']) : '';
$lastName = is_string($_GET['last_name'] ?? null)
    ? trim($_GET['last_name']) : '';
$sortKey = is_string($_GET['sort'] ?? null) && isset($sortColumns[$_GET['sort']])
    ? $_GET['sort'] : 'eoi';
$direction = is_string($_GET['direction'] ?? null) && strtoupper($_GET['direction']) === 'ASC'
    ? 'ASC' : 'DESC';
$confirmDelete = is_string($_GET['confirm_delete'] ?? null)
    ? strtoupper(trim($_GET['confirm_delete'])) : '';

$pageError = '';
$rows = [];
$toDeleteCount = null;

try {
    $pdo = managerDatabase();
    $where = [];
    $values = [];

    if ($jobReference !== '') {
        $where[] = 'job_reference = :job_reference';
        $values['job_reference'] = $jobReference;
    }
    if ($firstName !== '') {
        $where[] = 'first_name LIKE :first_name';
        $values['first_name'] = '%' . $firstName . '%';
    }
    if ($lastName !== '') {
        $where[] = 'last_name LIKE :last_name';
        $values['last_name'] = '%' . $lastName . '%';
    }

    $sql = 'SELECT EOInumber, job_reference, first_name, last_name, dob, gender,
                   street_address, suburb, state, postcode, email, phone,
                   skills, other_skills, status, applied_at FROM eoi';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . $sortColumns[$sortKey] . ' ' . $direction . ', EOInumber DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $rows = $stmt->fetchAll();

    // Two-step deletion: show how many records will be affected before POST.
    if ($confirmDelete !== '' && preg_match('/^[A-Z0-9]{5}$/D', $confirmDelete)) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM eoi WHERE job_reference = :job_reference');
        $countStmt->execute(['job_reference' => $confirmDelete]);
        $toDeleteCount = (int) $countStmt->fetchColumn();
    }
} catch (PDOException $exception) {
    error_log('UrbanSync manage list database error: ' . $exception->getMessage());
    $pageError = 'Could not load applications. Check the settings.php connection and eoi table.';
}
$flash = managerTakeMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications | UrbanSync HR</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hr-main { max-width: 1260px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .hr-top { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .hr-top form { margin:0; }
        .hr-panel { padding:1.25rem; border:1px solid #cbd5e1; border-radius:10px;
            background:#fff; margin:1.25rem 0; }
        .hr-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:.9rem; }
        .hr-field label { display:block; font-weight:600; margin-bottom:.35rem; }
        .hr-field input, .hr-field select, .hr-inline select { box-sizing:border-box; width:100%;
            padding:.55rem; border:1px solid #94a3b8; border-radius:5px; background:#fff; }
        .hr-actions { display:flex; flex-wrap:wrap; gap:.7rem; align-items:center; margin-top:1rem; }
        .hr-button { padding:.6rem 1rem; border:0; border-radius:5px; font-weight:700;
            color:#fff; background:#174b68; cursor:pointer; }
        .hr-button.danger { background:#a32236; }
        .hr-link { padding:.55rem .85rem; border:1px solid #94a3b8; border-radius:5px; }
        .hr-table-scroll { overflow-x:auto; }
        .hr-table { width:100%; min-width:930px; border-collapse:collapse; font-size:.94rem; }
        .hr-table th, .hr-table td { border-bottom:1px solid #cbd5e1; padding:.7rem;
            text-align:left; vertical-align:top; }
        .hr-table th { background:#eef2f7; white-space:nowrap; }
        .hr-inline { display:flex; align-items:center; gap:.4rem; }
        .hr-inline select { min-width:90px; }
        .hr-inline button { white-space:nowrap; }
        .hr-details { padding:.6rem 0; max-width:420px; }
        .hr-details dl { display:grid; grid-template-columns:120px 1fr; gap:.35rem .65rem; }
        .hr-details dt { font-weight:700; }
        .hr-details dd { margin:0; overflow-wrap:anywhere; white-space:pre-wrap; }
        .hr-success { background:#e8f6ed; color:#166534; border:1px solid #166534; padding:1rem; }
        .hr-error { background:#fff1f2; color:#881337; border:1px solid #be123c; padding:1rem; }
        .hr-warning { background:#fff7e4; border:1px solid #b45309; padding:1rem; }
        .hr-panel p { overflow-wrap:anywhere; }
        .hr-muted { color:#475569; }
    </style>
</head>
<body>
<?php if (is_file(__DIR__ . '/includes/header.inc')) { include __DIR__ . '/includes/header.inc'; } ?>
<main class="hr-main">
    <div class="hr-top">
        <div>
            <h1>Manage Applications</h1>
            <p>Signed in as <strong><?= managerEscape($_SESSION['manager_username'] ?? 'Manager') ?></strong></p>
        </div>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= managerEscape(managerCsrfToken()) ?>">
            <button class="hr-button" type="submit">Log out</button>
        </form>
    </div>

    <?php if ($flash !== null): ?>
        <p role="status" class="<?= ($flash['type'] ?? '') === 'success' ? 'hr-success' : 'hr-error' ?>">
            <?= managerEscape($flash['text'] ?? '') ?>
        </p>
    <?php endif; ?>
    <?php if ($pageError !== ''): ?>
        <p role="alert" class="hr-error"><?= managerEscape($pageError) ?></p>
    <?php endif; ?>

    <section class="hr-panel" aria-labelledby="filter-heading">
        <h2 id="filter-heading">Search and sort EOIs</h2>
        <form action="manage.php" method="get">
            <div class="hr-grid">
                <div class="hr-field">
                    <label for="job_reference">Job reference</label>
                    <input id="job_reference" name="job_reference" maxlength="5" placeholder="e.g. TRS01" value="<?= managerEscape($jobReference) ?>">
                </div>
                <div class="hr-field">
                    <label for="first_name">Applicant first name</label>
                    <input id="first_name" name="first_name" value="<?= managerEscape($firstName) ?>">
                </div>
                <div class="hr-field">
                    <label for="last_name">Applicant last name</label>
                    <input id="last_name" name="last_name" value="<?= managerEscape($lastName) ?>">
                </div>
                <div class="hr-field">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <?php foreach (['eoi'=>'EOI number','date'=>'Application date','job'=>'Job reference','first'=>'First name','last'=>'Last name','status'=>'Status'] as $key=>$label): ?>
                            <option value="<?= managerEscape($key) ?>" <?= $sortKey === $key ? 'selected' : '' ?>><?= managerEscape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="hr-field">
                    <label for="direction">Direction</label>
                    <select id="direction" name="direction">
                        <option value="DESC" <?= $direction === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        <option value="ASC" <?= $direction === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                    </select>
                </div>
            </div>
            <div class="hr-actions">
                <button type="submit" class="hr-button">Search / Sort</button>
                <a class="hr-link" href="manage.php">Show all EOIs</a>
            </div>
        </form>
    </section>

    <section class="hr-panel" aria-labelledby="delete-heading">
        <h2 id="delete-heading">Delete all EOIs for a job reference</h2>
        <p class="hr-muted">Use this only for records you intend to remove. A second confirmation is required.</p>
        <form action="manage.php" method="get" class="hr-actions">
            <label for="confirm_delete">Job reference</label>
            <input id="confirm_delete" name="confirm_delete" maxlength="5" placeholder="TRS01" value="<?= managerEscape($confirmDelete) ?>">
            <button type="submit" class="hr-button danger">Review deletion</button>
        </form>
        <?php if ($confirmDelete !== ''): ?>
            <?php if ($toDeleteCount === null): ?>
                <p class="hr-error" role="alert">Invalid reference. Enter five letters/numbers.</p>
            <?php else: ?>
                <div class="hr-warning" role="group" aria-label="Delete confirmation">
                    <p><strong>Confirm deletion:</strong> <?= $toDeleteCount ?> application(s) currently match
                    <strong><?= managerEscape($confirmDelete) ?></strong>. This cannot be undone.</p>
                    <?php if ($toDeleteCount > 0): ?>
                        <form action="manage.php" method="post" class="hr-actions">
                            <input type="hidden" name="csrf_token" value="<?= managerEscape(managerCsrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_job">
                            <input type="hidden" name="job_reference" value="<?= managerEscape($confirmDelete) ?>">
                            <button type="submit" class="hr-button danger">Yes, delete matching EOIs</button>
                            <a class="hr-link" href="manage.php">Cancel</a>
                        </form>
                    <?php else: ?>
                        <p>No matching EOIs to delete.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="hr-panel" aria-labelledby="results-heading">
        <h2 id="results-heading">EOI results (<?= count($rows) ?>)</h2>
        <?php if ($rows === []): ?>
            <p><?= $pageError === '' ? 'No applications match the current filters.' : 'Results unavailable.' ?></p>
        <?php else: ?>
            <div class="hr-table-scroll">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th scope="col">EOI #</th>
                            <th scope="col">Job reference</th>
                            <th scope="col">Applicant</th>
                            <th scope="col">Email / Phone</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Full details</th>
                            <th scope="col">Status / Update</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= managerEscape($row['EOInumber']) ?></td>
                            <td><?= managerEscape($row['job_reference']) ?></td>
                            <td><?= managerEscape($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td>
                                <?= managerEscape($row['email']) ?><br>
                                <?= managerEscape($row['phone']) ?>
                            </td>
                            <td><?= managerEscape($row['applied_at']) ?></td>
                            <td>
                                <details class="hr-details">
                                    <summary>View application</summary>
                                    <dl>
                                        <dt>Date of birth</dt><dd><?= managerEscape($row['dob']) ?></dd>
                                        <dt>Gender</dt><dd><?= managerEscape($row['gender']) ?></dd>
                                        <dt>Street address</dt><dd><?= managerEscape($row['street_address']) ?></dd>
                                        <dt>Suburb</dt><dd><?= managerEscape($row['suburb']) ?></dd>
                                        <dt>State</dt><dd><?= managerEscape($row['state']) ?></dd>
                                        <dt>Postcode</dt><dd><?= managerEscape($row['postcode']) ?></dd>
                                        <dt>Skills</dt><dd><?= managerEscape($row['skills']) ?></dd>
                                        <dt>Other skills</dt><dd><?= managerEscape($row['other_skills']) ?></dd>
                                    </dl>
                                </details>
                            </td>
                            <td>
                                <form action="manage.php" method="post" class="hr-inline">
                                    <input type="hidden" name="csrf_token" value="<?= managerEscape(managerCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="eoi_number" value="<?= managerEscape($row['EOInumber']) ?>">
                                    <label for="status-<?= managerEscape($row['EOInumber']) ?>" class="hr-muted">Status</label>
                                    <select id="status-<?= managerEscape($row['EOInumber']) ?>" name="status">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= managerEscape($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= managerEscape($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="hr-button">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php if (is_file(__DIR__ . '/includes/footer.inc')) { include __DIR__ . '/includes/footer.inc'; } ?>
</body>
</html>
