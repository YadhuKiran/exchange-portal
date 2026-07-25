<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? 'Document');
    if (empty($_FILES['file']['name'])) { flash('error', 'Please select a file.'); redirect('/student/document-upload.php'); }
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
    if (!in_array($ext, $allowed)) { flash('error', 'Invalid file type.'); redirect('/student/document-upload.php'); }
    $file_name = 'doc_'.$stuId.'_'.time().'.'.$ext;
    $file_path = $file_name;
    $file_size = $_FILES['file']['size'];
    move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'/../uploads/'.$file_name);
    db()->prepare('INSERT INTO documents (student_id, title, file_name, file_path, file_size, status) VALUES (?,?,?,?,?,?)')
        ->execute([$stuId, $title, $file_name, $file_path, $file_size, 'pending']);
    log_activity('document.uploaded', "Document uploaded: $file_name", 'document', (int) db()->lastInsertId());
    flash('success', 'Document uploaded.');
    redirect('/student/documents.php');
}

$pageTitle = 'Upload Document';
$activeNav = 'documents';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900">Upload Document</h2><p class="text-xs text-slate-500">PDF, DOC, JPG, PNG accepted</p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Document Title</label>
                <input name="title" required value="<?= e($_GET['title'] ?? 'Document') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Transcript, Passport Copy"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">File</label>
                <input type="file" name="file" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition-all"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Upload</button>
                <a href="<?= url('/student/documents.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
