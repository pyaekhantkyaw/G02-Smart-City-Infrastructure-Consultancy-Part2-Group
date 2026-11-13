<?php
/** Server-side validation for the UrbanSync job application. */
declare(strict_types=1);

const APPLICATION_TIMEZONE = 'Asia/Kuala_Lumpur';
const MINIMUM_APPLICANT_AGE = 18;

//start with clean text 
function postedText(array $post, string $name): string
{
    $value = $post[$name] ?? '';
    return is_string($value) ? trim(stripslashes($value)) : '';
}

// Count Unicode characters without requiring the mbstring extension.
function charLength(string $value): int
{
    // Count Unicode characters without requiring the mbstring extension.
    $length = preg_match_all('/./us', $value);
    return $length === false ? PHP_INT_MAX : $length;
}

/**
 * Returns [validated form values, field-name => error-message].
 * Assumes inputs are untrusted; never returns HTML-escaped values for storage.
 */
function validateApplication(array $post, ?DateTimeImmutable $today = null): array
{
    $today ??= new DateTimeImmutable('today', new DateTimeZone(APPLICATION_TIMEZONE));
    $errors = [];
    $names = [
        'job-reference', 'first-name', 'last-name', 'dob', 'gender',
        'street-address', 'suburb', 'state', 'postcode', 'email', 'phone',
        'other-skills'
    ];
    $data = [];
    foreach ($names as $name) {
        $data[$name] = postedText($post, $name);
    }

    $data['job-reference'] = strtoupper($data['job-reference']);
    if (!in_array($data['job-reference'], ['TRS01', 'EGS02'], true)) {
        $errors['job-reference'] = 'Choose a valid job reference (TRS01 or EGS02).';
    }

    foreach (['first-name' => 'First name', 'last-name' => 'Last name'] as $field => $label) {
        $name = trim($data[$field]);
        $data[$field] = $name;
        if (!preg_match('/^[\p{L}\x{2019}\'\- ]{1,40}$/uD', $name)) {
            $errors[$field] = "$label must contain 1–40 letters only. May include spaces, hyphens or apostrophes.";
        }
    }

    // Exact calendar-day validation: no impossible dates, future birthdays or under-18s.
    $dob = $data['dob'];
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/D', $dob)) {
        $errors['dob'] = 'Enter your date of birth in dd/mm/yyyy format.';
    } else {
        $birthDate = DateTimeImmutable::createFromFormat('!d/m/Y', $dob, new DateTimeZone(APPLICATION_TIMEZONE));
        if ($birthDate === false || $birthDate->format('d/m/Y') !== $dob) {
            $errors['dob'] = 'Enter a real calendar date (e.g. 29/02 on a leap year only).';
        } elseif ($birthDate > $today) {
            $errors['dob'] = 'Date of birth cannot be in the future.';
        } elseif ($birthDate->diff($today)->y < MINIMUM_APPLICANT_AGE) {
            $errors['dob'] = 'Applicants must be at least 18 years old on the application date.';
        } elseif ($birthDate->diff($today)->y > 120) {
            $errors['dob'] = 'Please enter a realistic date of birth.';
        } else {
            $data['dob_sql'] = $birthDate->format('Y-m-d');
        }
    }

    if (!in_array($data['gender'], ['male', 'female', 'other'], true)) {
        $errors['gender'] = 'Select a gender option.';
    }

    $street = $data['street-address'];
    // Accept everyday Australian addresses, e.g. "Unit 5/12 King's Road" or "PO Box 123".
    // Require a meaningful street name rather than only digits/punctuation.
    if (charLength($street) < 4 || charLength($street) > 40 ||
        !preg_match('/^[\p{L}\p{M}\p{N} .,#\x{2019}\'\/&()\-]+$/uD', $street) ||
        !preg_match('/\p{L}/u', $street)) {
        $errors['street-address'] = 'Street address must be 4–40 characters, include letters, and use normal address characters.';
    }

    $suburb = $data['suburb'];
    // Suburb/town was optional in the original form. Validate its format if provided.
    if ($suburb !== '' &&
        (charLength($suburb) > 40 || !preg_match('/^[\p{L}\p{M} .\x{2019}\'\-]+$/uD', $suburb) ||
         !preg_match('/\p{L}/u', $suburb))) {
        $errors['suburb'] = 'Suburb/town must be at most 40 characters and contain letters, spaces, hyphens or apostrophes.';
    }

    if (!in_array($data['state'], ['VIC', 'NSW', 'QLD', 'NT', 'WA', 'SA', 'TAS', 'ACT'], true)) {
        $errors['state'] = 'Select a valid Australian state or territory.';
    }
    if (!preg_match('/^[0-9]{4}$/D', $data['postcode'])) {
        $errors['postcode'] = 'Postcode must contain exactly four digits.';
    } else {
        // Cross-check Austrlian postcode starting digits against the selected state/territory.
        $firstDigit = $data['postcode'][0];
        $validMapping = [
            'VIC' => ['3', '8'],
            'NSW' => ['1', '2'],
            'QLD' => ['4', '9'],
            'NT'  => ['0'],
            'WA'  => ['6'],
            'SA'  => ['5'],
            'TAS' => ['7'],
            'ACT' => ['0'],
        ];
        if (!in_array($firstDigit, $validMapping[$data['state']], true)) {
            $errors['postcode'] = 'Postcode does not match the selected state or territory.';
        }
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 254) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9]{8,12}$/D', $data['phone'])) {
        $errors['phone'] = 'Phone number must contain 8–12 digits.';
    }

    $validSkills = ['data-analysis', 'power-bi', 'sql', 'python', 'project-management'];
    $submittedSkills = $post['skills'] ?? [];
    if (!is_array($submittedSkills) ||
        count($submittedSkills) === 0 ||
        count(array_filter($submittedSkills, static fn($s) => is_string($s) && in_array($s, $validSkills, true))) !== count($submittedSkills)) {
        $errors['skills'] = 'Select at least one recognised skill.';
        $data['skills'] = [];
    } else {
        $data['skills'] = array_values(array_unique($submittedSkills));
    }

    if (charLength($data['other-skills']) > 2000) {
        $errors['other-skills'] = 'Other skills must be 2,000 characters or fewer.';
    }

    return [$data, $errors];
}
