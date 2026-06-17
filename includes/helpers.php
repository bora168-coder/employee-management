<?php

function supported_locales(): array {
    return ['en', 'km'];
}

function normalize_locale(?string $locale): string {
    $locale = strtolower(trim((string) $locale));
    return in_array($locale, supported_locales(), true) ? $locale : 'en';
}

function current_locale(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_GET['lang'])) {
        $locale = normalize_locale($_GET['lang']);
        $_SESSION['ui_lang'] = $locale;
        setcookie('govlink_lang', $locale, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => false,
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (getenv('APP_ENV') === 'production'),
            'samesite' => 'Lax',
        ]);
    }

    if (empty($_SESSION['ui_lang']) && !empty($_COOKIE['govlink_lang'])) {
        $_SESSION['ui_lang'] = normalize_locale($_COOKIE['govlink_lang']);
    }

    return normalize_locale($_SESSION['ui_lang'] ?? ($_COOKIE['govlink_lang'] ?? 'en'));
}

function ui_text(string $key): string {
    static $strings = [
        'en' => [
            'app_name' => 'GovLink Pro EMS',
            'gov_admin_portal' => 'Gov Admin Portal',
            'portal' => 'Portal',
            'dashboard' => 'Dashboard',
            'employees' => 'Employees',
            'add_employee' => 'Add Employee',
            'verification' => 'Verification',
            'user_management' => 'User Management',
            'deployment' => 'Deployment',
            'logout' => 'Logout',
            'search_placeholder' => 'Search records, documents, or reports...',
            'language_toggle' => 'Khmer',
            'language_toggle_title' => 'Switch to Khmer',
            'generate_report' => 'Generate Report',
            'sign_in' => 'Sign In',
            'sign_in_message' => 'Sign in to manage employee records, verification queues, and administrative operations.',
            'username_or_email' => 'Username or email',
            'password' => 'Password',
            'show_password' => 'Show password',
            'hide_password' => 'Hide password',
            'session_expired' => 'Your session expired. Please sign in again.',
            'too_many_attempts' => 'Too many failed attempts. Please wait a few minutes before trying again.',
            'invalid_token' => 'Invalid security token. Please try again.',
            'required_credentials' => 'Username and password are required.',
            'invalid_credentials' => 'Invalid username or password. Please try again.',
            'total_employees' => 'Total Employees',
            'active_employees' => 'Active Employees',
            'pending_verification' => 'Pending Verification',
            'expiring_documents' => 'Expiring Documents',
            'current_registry_size' => 'Current registry size',
            'active_rate' => 'active rate',
            'requires_review' => 'Requires review',
            'within_30_days' => 'Within 30 days',
            'employees_by_department' => 'Employees by Department',
            'live_data' => 'Live Data',
            'verification_status' => 'Verification Status',
            'verified_records' => 'Verified Records',
            'recent_activities' => 'Recent Activities',
            'view_all' => 'View All',
            'pending_approvals' => 'Pending Approvals',
            'high_priority' => 'High Priority',
            'no_department_data' => 'No department data yet.',
            'no_recent_activity' => 'No recent employee activity.',
            'no_pending_records' => 'No pending verification records.',
            'employee_registry' => 'Employee Registry',
            'review' => 'Review',
            'subject' => 'Subject',
            'department' => 'Department',
            'action' => 'Action',
            'employee' => 'Employee',
            'status' => 'Status',
            'issue' => 'Issue',
            'back_to_list' => 'Back to List',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'profile' => 'Profile',
            'all_verified' => 'All employee records are currently verified.',
        ],
        'km' => [
            'app_name' => 'GovLink Pro EMS',
            'gov_admin_portal' => 'ផ្ទាំងគ្រប់គ្រងរដ្ឋបាល',
            'portal' => 'ផ្ទាំង',
            'dashboard' => 'ផ្ទាំងទិន្នន័យ',
            'employees' => 'បុគ្គលិក',
            'add_employee' => 'បន្ថែមបុគ្គលិក',
            'verification' => 'ផ្ទៀងផ្ទាត់',
            'user_management' => 'គ្រប់គ្រងអ្នកប្រើ',
            'deployment' => 'ដាក់ដំណើរការ',
            'logout' => 'ចាកចេញ',
            'search_placeholder' => 'ស្វែងរកកំណត់ត្រា ឯកសារ ឬរបាយការណ៍...',
            'language_toggle' => 'English',
            'language_toggle_title' => 'ប្ដូរទៅភាសាអង់គ្លេស',
            'generate_report' => 'បង្កើតរបាយការណ៍',
            'sign_in' => 'ចូលប្រើប្រាស់',
            'sign_in_message' => 'ចូលប្រើប្រាស់ដើម្បីគ្រប់គ្រងកំណត់ត្រាបុគ្គលិក បញ្ជីត្រួតពិនិត្យ និងការងាររដ្ឋបាល។',
            'username_or_email' => 'ឈ្មោះអ្នកប្រើ ឬអ៊ីមែល',
            'password' => 'ពាក្យសម្ងាត់',
            'show_password' => 'បង្ហាញពាក្យសម្ងាត់',
            'hide_password' => 'លាក់ពាក្យសម្ងាត់',
            'session_expired' => 'វគ្គប្រើប្រាស់របស់អ្នកបានផុតកំណត់។ សូមចូលម្តងទៀត។',
            'too_many_attempts' => 'បានព្យាយាមបរាជ័យច្រើនដងពេក។ សូមរង់ចាំពីរបីនាទីហើយសាកល្បងម្ដងទៀត។',
            'invalid_token' => 'សញ្ញាសុវត្ថិភាពមិនត្រឹមត្រូវ។ សូមសាកល្បងម្ដងទៀត។',
            'required_credentials' => 'ត្រូវការឈ្មោះអ្នកប្រើ និងពាក្យសម្ងាត់។',
            'invalid_credentials' => 'ឈ្មោះអ្នកប្រើ ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ។ សូមសាកល្បងម្ដងទៀត។',
            'total_employees' => 'បុគ្គលិកសរុប',
            'active_employees' => 'បុគ្គលិកសកម្ម',
            'pending_verification' => 'រង់ចាំផ្ទៀងផ្ទាត់',
            'expiring_documents' => 'ឯកសារជិតផុតកំណត់',
            'current_registry_size' => 'ទំហំបញ្ជីបច្ចុប្បន្ន',
            'active_rate' => 'អត្រាសកម្ម',
            'requires_review' => 'ត្រូវការត្រួតពិនិត្យ',
            'within_30_days' => 'ក្នុងរយៈពេល 30 ថ្ងៃ',
            'employees_by_department' => 'បុគ្គលិកតាមនាយកដ្ឋាន',
            'live_data' => 'ទិន្នន័យបច្ចុប្បន្ន',
            'verification_status' => 'ស្ថានភាពផ្ទៀងផ្ទាត់',
            'verified_records' => 'កំណត់ត្រាដែលបានផ្ទៀងផ្ទាត់',
            'recent_activities' => 'សកម្មភាពថ្មីៗ',
            'view_all' => 'មើលទាំងអស់',
            'pending_approvals' => 'ការអនុម័តដែលរង់ចាំ',
            'high_priority' => 'អាទិភាពខ្ពស់',
            'no_department_data' => 'មិនទាន់មានទិន្នន័យនាយកដ្ឋានទេ។',
            'no_recent_activity' => 'មិនទាន់មានសកម្មភាពបុគ្គលិកថ្មីៗទេ។',
            'no_pending_records' => 'មិនមានកំណត់ត្រារង់ចាំផ្ទៀងផ្ទាត់ទេ។',
            'employee_registry' => 'បញ្ជីបុគ្គលិក',
            'review' => 'ពិនិត្យ',
            'subject' => 'ប្រធានបទ',
            'department' => 'នាយកដ្ឋាន',
            'action' => 'សកម្មភាព',
            'employee' => 'បុគ្គលិក',
            'status' => 'ស្ថានភាព',
            'issue' => 'បញ្ហា',
            'back_to_list' => 'ត្រឡប់ទៅបញ្ជី',
            'edit' => 'កែសម្រួល',
            'delete' => 'លុប',
            'profile' => 'ព័ត៌មានលម្អិត',
            'all_verified' => 'កំណត់ត្រាបុគ្គលិកទាំងអស់បានផ្ទៀងផ្ទាត់រួចរាល់។',
        ],
    ];

    $locale = current_locale();
    return $strings[$locale][$key] ?? $strings['en'][$key] ?? $key;
}

function lang_url(string $path, ?string $lang = null, array $extra = []): string {
    $lang = normalize_locale($lang ?? current_locale());
    $parts = parse_url($path);
    $route = $parts['path'] ?? $path;
    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    foreach ($extra as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }

    $query['lang'] = $lang;
    $queryString = http_build_query($query);

    return $route . ($queryString !== '' ? '?' . $queryString : '');
}

function safe_photo_src(?string $path): ?string {
    if ($path === null || $path === '') {
        return null;
    }

    $norm = str_replace('\\', '/', ltrim($path, './'));
    if (strpos($norm, '..') !== false || strpos($norm, 'uploads/photos/') !== 0) {
        return null;
    }

    return file_exists($path) ? $path : null;
}

function status_badge_class(?string $status): string {
    $slug = strtolower((string) $status);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return 'badge badge-' . trim($slug, '-');
}

function initials(string $first, string $last = ''): string {
    $source = trim($first . ' ' . $last);
    if ($source === '') {
        return 'NA';
    }
    $parts = preg_split('/\s+/', $source);
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters ?: 'NA';
}
