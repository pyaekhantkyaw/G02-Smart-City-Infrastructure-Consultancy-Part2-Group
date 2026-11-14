<?php
// ==========================================
// URBANSYNC - DYNAMIC ABOUT PAGE
// ==========================================

$members = [];
$databaseError = false;
$conn = null;

try {
    // Load database settings from the same folder as about.php.
    $settingsPath = __DIR__ . '/settings.php';

    if (!file_exists($settingsPath)) {
        throw new RuntimeException('settings.php is missing. Place it in the same folder as about.php.');
    }

    $config = require $settingsPath;

    if (!is_array($config)) {
        throw new RuntimeException('The database settings are invalid.');
    }

    foreach (['host', 'user', 'password', 'database'] as $key) {
        if (!array_key_exists($key, $config)) {
            throw new RuntimeException('A required database setting is missing.');
        }
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database']
    );
    $conn->set_charset('utf8mb4');

    // Retrieve member contributions and fun facts from the About table.
    $sql = "SELECT
                member_id,
                member_name,
                student_id,
                linkedin_url,
                project1_contribution,
                project2_contribution,
                quote_mm,
                quote_translation,
                dream_job,
                coding_snack,
                hometown
            FROM `about`
            ORDER BY display_order ASC, member_name ASC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    $result->free();

} catch (Throwable $error) {
    $databaseError = true;
    error_log('UrbanSync about page database error: ' . $error->getMessage());
}

// Escape database content before displaying it in HTML.
function about_escape($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Our Team | UrbanSync Infrastructure Consultancy</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .about-intro {
            text-align: center;
        }
        .group-info {
            background-color: #eaf4fb;
            border-left: 5px solid #1976d2;
        }
        .member-id {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- ==================== HEADER ==================== -->
    <?php include 'includes/header.inc'; ?>

    <!-- ==================== MAIN CONTENT ==================== -->
    <main>
        <!-- ==================== INTRODUCTION ==================== -->
        <section class="about-intro">
            <h1>About Our Team</h1>
            <p>Meet the team behind the UrbanSync Infrastructure Consultancy website project.</p>
        </section>

        <!-- ==================== GROUP INFORMATION ==================== -->
        <section class="group-info">
            <h2>Project Development Group</h2>
            <ul>
                <li>
                    <strong>Group Name:</strong> WebSec - Urban Tech Innovators
                    <ul>
                        <li><strong>Session:</strong> AUG 2026 (Swinburne University of Technology, Australia)</li>
                        <li><strong>Course:</strong> COS10026 - Web Technology Project</li>
                        <li><strong>Class Day:</strong> Monday &amp; Thursday</li>
                        <li><strong>Class Time:</strong> 10:30 AM – 12:30 PM &amp; 2:00 PM – 4:00 PM</li>
                        <li><strong>Industry Allocation:</strong> G02 – Smart City Infrastructure Consultancy</li>
                        <li><strong>Lecturer:</strong> PAWANI T.RASARATNAM</li>
                    </ul>
                </li>
            </ul>
        </section>

        <!-- ==================== TEAM CONTRIBUTIONS ==================== -->
        <section>
            <h2>Team Contributions &amp; Quotes</h2>

            <?php if ($databaseError): ?>
                <p class="database-message">
                    Team contributions cannot be displayed right now. Please check the database connection.
                </p>
            <?php elseif (empty($members)): ?>
                <p>No team member records are currently available.</p>
            <?php else: ?>
                <dl class="team-contributions">
                    <?php foreach ($members as $member): ?>
                        <div class="team-card">
                            <dt>
                                <?php if (!empty($member['linkedin_url'])): ?>
                                    <a href="<?= about_escape($member['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer">
                                        <strong><?= about_escape($member['member_name']) ?></strong>
                                    </a>
                                <?php else: ?>
                                    <strong><?= about_escape($member['member_name']) ?></strong>
                                <?php endif; ?>
                            </dt>
                            <dd>
                                <span class="member-id">Student ID: <?= about_escape($member['student_id']) ?></span>

                                <p>
                                    <strong>Project 1 Contribution:</strong>
                                    <?= nl2br(about_escape($member['project1_contribution'])) ?>
                                </p>

                                <p>
                                    <strong>Project 2 Contribution:</strong>
                                    <?= nl2br(about_escape($member['project2_contribution'])) ?>
                                </p>

                                <?php if (!empty($member['quote_mm'])): ?>
                                    <blockquote>“<?= about_escape($member['quote_mm']) ?>”</blockquote>
                                <?php endif; ?>

                                <?php if (!empty($member['quote_translation'])): ?>
                                    <p><strong>English Translation:</strong> “<?= about_escape($member['quote_translation']) ?>”</p>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>

        <!-- ==================== GROUP PHOTO ==================== -->
        <section>
            <h2>Our Group</h2>
            <figure class="group-photo">
                <img src="images/group_photo.jpg" alt="The Urban Tech Innovators group members" title="Left to Right: Min Khant Kyaw, Pyae Khant Kyaw, Han Lin Aung, Oakkar Aung">
                <figcaption>The Urban Tech Innovators team working on the Smart City Infrastructure Consultancy project.</figcaption>
            </figure>
        </section>

        <!-- ==================== FUN FACTS ==================== -->
        <section>
            <h2>Team Fun Facts</h2>
            <?php if ($databaseError): ?>
                <p>Team fun facts cannot be displayed because the database is unavailable.</p>
            <?php elseif (!empty($members)): ?>
                <table class="fun-facts-table">
                    <caption>A few fun facts about our project team</caption>
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Dream Job</th>
                            <th scope="col">Coding Snack</th>
                            <th scope="col">Hometown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <th scope="row"><?= about_escape($member['member_name']) ?></th>
                                <td><?= about_escape($member['dream_job'] ?: 'Not specified') ?></td>
                                <td><?= about_escape($member['coding_snack'] ?: 'Not specified') ?></td>
                                <td><?= about_escape($member['hometown'] ?: 'Not specified') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No team fun facts are currently available.</p>
            <?php endif; ?>
        </section>

        <!-- ==================== TEAM MESSAGE ==================== -->
        <section>
            <h2>Working Together</h2>
            <p>Our team worked together by dividing the website into different development areas while maintaining a common visual style and navigation structure.</p>
            <p>Through collaboration, we were able to combine individual technical skills into one consistent smart-city recruitment website.</p>
        </section>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <?php include 'includes/footer.inc'; ?>

    <?php
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    ?>
</body>
</html>
