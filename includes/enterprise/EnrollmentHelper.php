<?php

function enrollment_requires_approved_app(): bool
{
    return (bool) setting('enrollment_requires_approved_application', true);
}

function student_has_approved_app_for_university(int $studentId, int $universityId): bool
{
    $stmt = db()->prepare(
        "SELECT id FROM applications WHERE student_id=? AND host_university_id=? AND status='approved' LIMIT 1"
    );
    $stmt->execute([$studentId, $universityId]);
    return (bool) $stmt->fetch();
}

function course_enrollment_count(int $courseId): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM enrollments WHERE course_id=? AND status IN ('pending','approved')"
    );
    $stmt->execute([$courseId]);
    return (int) $stmt->fetchColumn();
}
