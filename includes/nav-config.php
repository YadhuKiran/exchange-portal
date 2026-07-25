<?php

function get_enterprise_nav(string $role): array
{
    return match ($role) {
        'admin' => [
            ['group' => 'Overview', 'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/admin/index.php'],
                ['key' => 'analytics', 'label' => 'Analytics', 'href' => '/admin/analytics.php'],
            ]],
            ['group' => 'Mobility', 'items' => [
                ['key' => 'applications', 'label' => 'Applications', 'href' => '/admin/applications.php', 'badge' => 'applications'],
                ['key' => 'enrollments', 'label' => 'Enrollments', 'href' => '/admin/enrollments.php', 'badge' => 'enrollments'],
            ]],
            ['group' => 'Compliance', 'items' => [
                ['key' => 'documents', 'label' => 'Documents', 'href' => '/admin/documents.php', 'badge' => 'documents'],
                ['key' => 'passports', 'label' => 'Passports', 'href' => '/admin/passports.php', 'badge' => 'passports'],
                ['key' => 'visas', 'label' => 'Visas', 'href' => '/admin/visas.php', 'badge' => 'visas'],
                ['key' => 'transcripts', 'label' => 'Transcripts', 'href' => '/admin/transcripts.php'],
            ]],
            ['group' => 'Directory', 'items' => [
                ['key' => 'students', 'label' => 'Students', 'href' => '/admin/students.php'],
                ['key' => 'coordinators', 'label' => 'Coordinators', 'href' => '/admin/coordinators.php'],
                ['key' => 'universities', 'label' => 'Universities', 'href' => '/admin/universities.php'],
            ]],
            ['group' => 'Academics', 'items' => [
                ['key' => 'courses', 'label' => 'Courses', 'href' => '/admin/courses.php'],
            ]],
            ['group' => 'Insights', 'items' => [
                ['key' => 'reviews', 'label' => 'Testimonials', 'href' => '/admin/reviews.php'],
                ['key' => 'reports', 'label' => 'Reports Center', 'href' => '/admin/reports/index.php'],
                ['key' => 'activity', 'label' => 'Activity Logs', 'href' => '/admin/activity-logs.php'],
            ]],
            ['group' => 'Administration', 'items' => [
                ['key' => 'admins', 'label' => 'Admins', 'href' => '/admin/admins.php'],
                ['key' => 'contact-messages', 'label' => 'Contact Messages', 'href' => '/admin/contact-messages.php'],
                ['key' => 'settings', 'label' => 'Settings', 'href' => '/admin/settings.php'],
            ]],
            ['group' => 'Account', 'items' => [
                ['key' => 'profile', 'label' => 'Profile', 'href' => '/admin/profile.php'],
                ['key' => 'notifications', 'label' => 'Notifications', 'href' => '/admin/notifications.php'],
            ]],
        ],
        'coordinator' => [
            ['group' => 'Overview', 'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/coordinator/index.php'],
                ['key' => 'uni-dashboard', 'label' => 'University Overview', 'href' => '/university/index.php'],
            ]],
            ['group' => 'Mobility', 'items' => [
                ['key' => 'applications', 'label' => 'Applications', 'href' => '/coordinator/applications.php', 'badge' => 'applications'],
                ['key' => 'enrollments', 'label' => 'Enrollments', 'href' => '/coordinator/enrollments.php', 'badge' => 'enrollments'],
            ]],
            ['group' => 'Compliance', 'items' => [
                ['key' => 'documents', 'label' => 'Documents', 'href' => '/coordinator/documents.php', 'badge' => 'documents'],
                ['key' => 'passports', 'label' => 'Passports', 'href' => '/coordinator/passports.php'],
                ['key' => 'visas', 'label' => 'Visas', 'href' => '/coordinator/visas.php'],
                ['key' => 'transcripts', 'label' => 'Transcripts', 'href' => '/coordinator/transcripts.php'],
            ]],
            ['group' => 'Directory', 'items' => [
                ['key' => 'students', 'label' => 'Students', 'href' => '/coordinator/students.php'],
            ]],
            ['group' => 'Academics', 'items' => [
                ['key' => 'courses', 'label' => 'Courses', 'href' => '/coordinator/courses.php'],
            ]],
            ['group' => 'Insights', 'items' => [
                ['key' => 'reports', 'label' => 'Reports Center', 'href' => '/coordinator/reports/index.php'],
                ['key' => 'activity', 'label' => 'Activity Logs', 'href' => '/coordinator/activity-logs.php'],
            ]],
            ['group' => 'Account', 'items' => [
                ['key' => 'profile', 'label' => 'Profile', 'href' => '/coordinator/profile.php'],
                ['key' => 'notifications', 'label' => 'Notifications', 'href' => '/coordinator/notifications.php'],
            ]],
        ],
        'student' => [
            ['group' => 'Overview', 'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/student/index.php'],
                ['key' => 'coordinator', 'label' => 'My Coordinator', 'href' => '/student/coordinator.php'],
            ]],
            ['group' => 'My Mobility', 'items' => [
                ['key' => 'applications', 'label' => 'Applications', 'href' => '/student/applications.php'],
                ['key' => 'enrollments', 'label' => 'My Enrollments', 'href' => '/student/enrollments.php'],
            ]],
            ['group' => 'Compliance', 'items' => [
                ['key' => 'documents', 'label' => 'Documents', 'href' => '/student/documents.php'],
                ['key' => 'passport', 'label' => 'Passport', 'href' => '/student/passport.php'],
                ['key' => 'visas', 'label' => 'Visas', 'href' => '/student/visas.php'],
                ['key' => 'transcripts', 'label' => 'Transcripts', 'href' => '/student/transcripts.php'],
            ]],
            ['group' => 'Academics', 'items' => [
                ['key' => 'courses', 'label' => 'Course Catalog', 'href' => '/student/courses.php'],
            ]],
            ['group' => 'Account', 'items' => [
                ['key' => 'profile', 'label' => 'Profile', 'href' => '/student/profile.php'],
                ['key' => 'notifications', 'label' => 'Notifications', 'href' => '/student/notifications.php'],
            ]],
        ],
        default => [],
    };
}

function flatten_nav_for_mobile(array $groups): array
{
    $flat = [];
    foreach ($groups as $g) {
        foreach ($g['items'] as $item) {
            $flat[] = [$item['key'], $item['label'], $item['href']];
        }
    }
    return $flat;
}
