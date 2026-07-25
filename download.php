<?php
require_once __DIR__ . '/includes/init.php';
require_login();

/** Demo seed rows in database.sql reference these paths; serve minimal placeholders when files were never uploaded. */
function seed_document_placeholder(string $filePath): ?array
{
    $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 300 144]/Parent 2 0 R>>endobj\nxref\n0 4\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n0\n%%EOF\n";
    $map = [
        'transcript_alex_johnson.pdf'   => ['body' => $pdf, 'mime' => 'application/pdf'],
        'passport_alex_johnson.pdf'     => ['body' => $pdf, 'mime' => 'application/pdf'],
        'recommendation_alex.pdf'       => ['body' => $pdf, 'mime' => 'application/pdf'],
        'transcript_maria_garcia.pdf'   => ['body' => $pdf, 'mime' => 'application/pdf'],
        'passport_maria_garcia.pdf'     => ['body' => $pdf, 'mime' => 'application/pdf'],
        'transcript_li_wei.pdf'         => ['body' => $pdf, 'mime' => 'application/pdf'],
        'transcript_david_okonkwo.pdf'  => ['body' => $pdf, 'mime' => 'application/pdf'],
        'statement_sophie_draft.docx'   => ['body' => $pdf, 'mime' => 'application/pdf'],
    ];
    return $map[$filePath] ?? null;
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT d.*, s.user_id AS student_user_id
     FROM documents d
     JOIN students s ON s.id = d.student_id
     WHERE d.id = ?'
);
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Document not found.');
}

$user = current_user();
$allowed = false;
if ($user['role'] === 'admin') {
    $allowed = true;
} elseif ($user['role'] === 'student' && (int) $doc['student_user_id'] === (int) $user['id']) {
    $allowed = true;
} elseif ($user['role'] === 'coordinator') {
    $coord = coordinator_profile((int) $user['id']);
    if ($coord) {
        $uniId = (int) $coord['university_id'];
        $scope = db()->prepare(
            'SELECT 1 FROM documents d
             JOIN students s ON s.id = d.student_id
             WHERE d.id = ?
             AND (s.university_id = ? OR d.application_id IN (
                 SELECT id FROM applications WHERE home_university_id = ? OR host_university_id = ?
             ))'
        );
        $scope->execute([$id, $uniId, $uniId, $uniId]);
        $allowed = (bool) $scope->fetch();
    }
}

if (!$allowed) {
    http_response_code(403);
    die('Access denied.');
}

$path = UPLOAD_DIR . $doc['file_path'];
if (!is_file($path)) {
    $placeholder = seed_document_placeholder($doc['file_path']);
    if ($placeholder === null) {
        http_response_code(404);
        die('File not found on server. Upload a new document to test downloads.');
    }
    header('Content-Type: ' . $placeholder['mime']);
    header('Content-Disposition: inline; filename="' . basename($doc['file_name']) . '"');
    header('Content-Length: ' . strlen($placeholder['body']));
    echo $placeholder['body'];
    exit;
}

$mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
    'pdf'  => 'application/pdf',
    'jpg', 'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($doc['file_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
