<?php
// admin/reject.php
require_once __DIR__.'/_auth.php';
$id = intval($_POST['id'] ?? 0);
$comment = $_POST['comment'] ?? null;

if (empty($id)) {
    $_SESSION['error'] = 'Неверный ID заявки';
    header('Location: dashboard.php');
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $submission = $stmt->fetch();

    if ($submission && $submission['status'] === 'APPROVED') {
        $existingTeacher = $pdo->prepare("SELECT id FROM teacher_pages WHERE fio = :fio AND position = :position AND organization = :organization");
        $existingTeacher->execute([
            ':fio' => $submission['fio'],
            ':position' => $submission['position'],
            ':organization' => $submission['organization']
        ]);
        $teacher = $existingTeacher->fetch();

        if ($teacher) {
            $deleteSocials = $pdo->prepare("DELETE FROM social_links WHERE teacher_id = :id");
            $deleteSocials->execute([':id' => $teacher['id']]);

            $deleteTeacher = $pdo->prepare("DELETE FROM teacher_pages WHERE id = :id");
            $deleteTeacher->execute([':id' => $teacher['id']]);
        }
    }

    $up = $pdo->prepare("UPDATE submissions SET status = 'REJECTED', admin_comment = :c, reviewed_at = NOW() WHERE id = :id");
    $up->execute([':c' => $comment, ':id' => $id]);

    $pdo->commit();
    $_SESSION['success'] = 'Заявка отклонена' . ($submission && $submission['status'] === 'APPROVED' ? ' и преподаватель удален' : '');
} catch (Exception $ex) {
    $pdo->rollBack();
    $_SESSION['error'] = "Ошибка: " . $ex->getMessage();
}

header('Location: dashboard.php');
?>