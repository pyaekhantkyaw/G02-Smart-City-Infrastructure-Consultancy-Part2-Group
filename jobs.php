<?php
// ==========================================
// URBANSYNC - DYNAMIC JOBS PAGE
// ==========================================

$jobs = [];
$databaseError = false;
$searchTerm = trim($_GET['search'] ?? '');
$conn = null;

try {

    // Load database settings from the same folder
    $settingsPath = __DIR__ . '/settings.php';

    if (!file_exists($settingsPath)) {
        throw new RuntimeException(
            'settings.php is missing. Place it in the same folder as jobs.php.'
        );
    }

    $config = require $settingsPath;

    // Connect to MySQL
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database']
    );

    $conn->set_charset("utf8mb4");

    // Retrieve ALL jobs initially
    $sql = "SELECT
                job_reference,
                job_category,
                job_title,
                salary,
                reports_to,
                job_description,
                responsibilities,
                essential_requirements,
                preferable_requirements,
                location,
                closing_date,
                status
            FROM jobs";

    // Only filter when the user enters a search keyword
    if ($searchTerm !== '') {
        $sql .= " WHERE job_title LIKE ?
                  OR job_reference LIKE ?
                  OR job_category LIKE ?";
    }

    $sql .= " ORDER BY job_title ASC";

    $stmt = $conn->prepare($sql);

    // Bind search parameters
    if ($searchTerm !== '') {
        $keyword = "%" . $searchTerm . "%";
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    // Store retrieved jobs
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    $stmt->close();

} catch (Throwable $error) {

    $databaseError = true;

    // Display the actual error during development
    echo "<div style='background:#ffe0e0; padding:15px; margin:15px; color:#900;'>";
    echo "<strong>Database Error:</strong> ";
    echo htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "</div>";

    error_log("Jobs page error: " . $error->getMessage());
}

// Group retrieved jobs by category for the sidebar.
// The three original categories are retained even when one has no jobs.
$jobCategories = [
    'Transport' => [],
    'Energy' => [],
    'Urban Services' => []
];

foreach ($jobs as $job) {
    $category = trim((string)($job['job_category'] ?? ''));

    if ($category === '') {
        $category = 'Uncategorized';
    }

    // Add any new category found in the database automatically.
    if (!array_key_exists($category, $jobCategories)) {
        $jobCategories[$category] = [];
    }

    $jobCategories[$category][] = $job;
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

// Prevent HTML injection
function jobs_escape($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Display job descriptions as paragraphs
function jobs_render_paragraphs($text)
{
    $lines = preg_split(
        '/\r\n|\r|\n/',
        trim((string)$text)
    );

    $html = '';

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line !== '') {
            $html .= '<p>' . jobs_escape($line) . '</p>';
        }
    }

    return $html ?: '<p>Not specified.</p>';
}

// Display responsibilities and requirements as lists
function jobs_render_list($text)
{
    $lines = preg_split(
        '/\r\n|\r|\n/',
        trim((string)$text)
    );

    $items = [];

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line !== '') {

            // Remove existing bullet points or numbering
            $line = preg_replace(
                '/^(?:[-*•]\s*|\d+[.)]\s*)/',
                '',
                $line
            );

            $items[] = $line;
        }
    }

    if (empty($items)) {
        return '<p>Not specified.</p>';
    }

    $html = '<ul>';

    foreach ($items as $item) {
        $html .= '<li>' . jobs_escape($item) . '</li>';
    }

    $html .= '</ul>';

    return $html;
}

// Format closing date
function jobs_format_date($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return 'Not specified';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return 'Not specified';
    }

    return date('d F Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Career Opportunities | UrbanSync</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .jobs-intro {
            text-align: center;
        }
        .job-reference {
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>

</head>

<body>

    <!-- Header -->
    <?php include 'header.inc'; ?>

    <main>

        <!-- Page Introduction -->
        <section class="jobs-intro">

            <h1>Career Opportunities</h1>

            <p>
                Join UrbanSync Infrastructure Consultancy and help
                create smarter, more connected, and sustainable cities.
            </p>

        </section>


        <!-- Search Jobs (matches the home page search layout) -->
        <section id="search">

            <h2>Search Jobs and Services</h2>

            <form action="jobs.php" method="get">

                <label for="search-box">
                    Search:
                </label>

                <input
                    type="search"
                    id="search-box"
                    name="search"
                    placeholder="Enter a job or service"
                    value="<?= jobs_escape($searchTerm) ?>"
                >

                <button type="submit">
                    Search
                </button>

            </form>

            <?php if ($searchTerm !== ''): ?>
                <p><a href="jobs.php">Clear Search</a></p>
            <?php endif; ?>

        </section>


        <!-- Job Listings -->
        <div class="job-list">

            <div class="job-column">

                <h2>Available Positions</h2>


                <?php if ($databaseError): ?>

                    <p class="database-message">
                        Sorry, job vacancies cannot be displayed right now.
                        Please check your database connection.
                    </p>


                <?php elseif (empty($jobs)): ?>

                    <?php if ($searchTerm !== ''): ?>

                        <p>
                            No jobs matched your search for
                            <strong>
                                <?= jobs_escape($searchTerm) ?>
                            </strong>.
                        </p>

                    <?php else: ?>

                        <p>
                            No job vacancies are currently available.
                        </p>

                    <?php endif; ?>


                <?php else: ?>


                    <!-- Display Every Job -->
                    <?php foreach ($jobs as $job): ?>

                        <section
                            class="job-section"
                            id="job-<?= jobs_escape($job['job_reference']) ?>"
                        >

                            <article class="job-card">

                                <!-- Job Information -->
                                <header>

                                    <p class="job-reference">

                                        Job Reference:

                                        <strong>
                                            <?= jobs_escape($job['job_reference']) ?>
                                        </strong>

                                    </p>


                                    <h2>
                                        <?= jobs_escape($job['job_title']) ?>
                                    </h2>


                                    <p>

                                        <strong>Salary:</strong>

                                        <?= jobs_escape(
                                            $job['salary'] ?: 'Not specified'
                                        ) ?>

                                    </p>


                                    <p>

                                        <strong>Reports to:</strong>

                                        <?= jobs_escape(
                                            $job['reports_to'] ?: 'Not specified'
                                        ) ?>

                                    </p>


                                    <?php if (!empty($job['location'])): ?>

                                        <p>

                                            <strong>Location:</strong>

                                            <?= jobs_escape($job['location']) ?>

                                        </p>

                                    <?php endif; ?>

                                </header>


                                <!-- About the Role -->
                                <section>

                                    <h3>About the Role</h3>

                                    <?= jobs_render_paragraphs(
                                        $job['job_description']
                                    ) ?>

                                </section>


                                <!-- Responsibilities -->
                                <section>

                                    <h3>Key Responsibilities</h3>

                                    <?= jobs_render_list(
                                        $job['responsibilities']
                                    ) ?>

                                </section>


                                <!-- Essential Requirements -->
                                <section>

                                    <h3>Essential Requirements</h3>

                                    <?= jobs_render_list(
                                        $job['essential_requirements']
                                    ) ?>

                                </section>


                                <!-- Preferable Requirements -->
                                <section>

                                    <h3>Preferable Requirements</h3>

                                    <?= jobs_render_list(
                                        $job['preferable_requirements']
                                    ) ?>

                                </section>


                                <!-- How to Apply -->
                                <section>

                                    <h3>How to Apply</h3>

                                    <p>

                                        Submit your application with the
                                        job reference

                                        <strong>
                                            <?= jobs_escape($job['job_reference']) ?>
                                        </strong>.

                                    </p>


                                    <p class="apply-button">

                                        <a href="apply.php?job_ref=<?= urlencode($job['job_reference']) ?>">

                                            Apply Now

                                        </a>

                                    </p>

                                </section>


                                <!-- Closing Date -->
                                <?php if (!empty($job['closing_date'])): ?>

                                    <p>

                                        <strong>
                                            Application Closing Date:
                                        </strong>

                                        <?= jobs_escape(
                                            jobs_format_date($job['closing_date'])
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </article>

                        </section>

                    <?php endforeach; ?>


                <?php endif; ?>

            </div>


            <!-- Available Jobs Sidebar -->
            <aside class="job-aside" style="
        max-height: 75vh;
        float: right; width: 25%; 
        padding-left: 15px; 
        padding-top: 15px; 
        margin-top: 35px;
        top: 100px; position: 
        sticky; margin-right: 20px; 
        overflow-y: auto; 
        scrollbar-width: thin;
        ">

                <h3>Available Jobs</h3>

                <?php if ($databaseError): ?>

                    <p>Jobs are currently unavailable.</p>

                <?php else: ?>

                    <?php foreach ($jobCategories as $category => $categoryJobs): ?>

                        <details>

                            <summary><?= jobs_escape($category) ?></summary>

                            <?php if (!empty($categoryJobs)): ?>

                                <ul>
                                    <?php foreach ($categoryJobs as $job): ?>
                                        <li>
                                            <a href="#job-<?= jobs_escape($job['job_reference']) ?>">
                                                <?= jobs_escape($job['job_title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                            <?php elseif ($searchTerm !== ''): ?>

                                <p>No matching jobs in this category.</p>

                            <?php else: ?>

                                <ul>
                                    <li>Currently Unavailable</li>
                                </ul>

                            <?php endif; ?>

                        </details>

                    <?php endforeach; ?>

                <?php endif; ?>

            </aside>

        </div>


        <!-- Final Call to Action -->
        <section class="go-apply-section">

            <h2>Find Your Next Opportunity</h2>

            <p>
                Interested in joining UrbanSync?
                Review our available positions and submit
                your application today.
            </p>

            <p>
                <a href="apply.php">
                    Go to Application Form
                </a>
            </p>

        </section>

    </main>


    <!-- Footer -->
    <?php include 'footer.inc'; ?>


</body>

</html>

<?php

// Close database connection
if ($conn instanceof mysqli) {
    $conn->close();
}

?>
