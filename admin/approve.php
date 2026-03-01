<?php
// admin/approve.php
require_once __DIR__.'/_auth.php';
$id = intval($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
$stmt->execute([':id'=>$id]);
$s = $stmt->fetch();
if (!$s) { 
    $_SESSION['error'] = 'Заявка не найдена';
    header('Location: dashboard.php'); 
    exit; 
}

$pdo->beginTransaction();
try {
    $existingTeacher = $pdo->prepare("SELECT id FROM teacher_pages WHERE fio = :fio AND position = :position AND organization = :organization");
    $existingTeacher->execute([
        ':fio' => $s['fio'],
        ':position' => $s['position'],
        ':organization' => $s['organization']
    ]);
    $teacher = $existingTeacher->fetch();

    if ($teacher) {
        $update = $pdo->prepare("UPDATE teacher_pages SET 
            fio = :fio, 
            birth_date = :bd, 
            photo_path = :pp, 
            position = :pos, 
            organization = :org, 
            work_experience_years = :we, 
            phone = :phone, 
            email = :email, 
            personal_site = :site, 
            about = :about,
            published_at = NOW() 
            WHERE id = :id");
        $update->execute([
            ':fio' => $s['fio'], 
            ':bd' => $s['birth_date'], 
            ':pp' => $s['photo_path'], 
            ':pos' => $s['position'],
            ':org' => $s['organization'], 
            ':we' => $s['work_experience_years'], 
            ':phone' => $s['phone'],
            ':email' => $s['email'], 
            ':site' => $s['personal_site'], 
            ':about' => $s['about'],
            ':id' => $teacher['id']
        ]);
        $teacher_id = $teacher['id'];

        $deleteSocials = $pdo->prepare("DELETE FROM social_links WHERE teacher_id = :id");
        $deleteSocials->execute([':id' => $teacher_id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO teacher_pages
            (fio, birth_date, photo_path, position, organization, work_experience_years, phone, email, personal_site, about, published_at)
            VALUES (:fio, :bd, :pp, :pos, :org, :we, :phone, :email, :site, :about, NOW())");
        $ins->execute([
            ':fio' => $s['fio'], 
            ':bd' => $s['birth_date'], 
            ':pp' => $s['photo_path'], 
            ':pos' => $s['position'],
            ':org' => $s['organization'], 
            ':we' => $s['work_experience_years'], 
            ':phone' => $s['phone'],
            ':email' => $s['email'], 
            ':site' => $s['personal_site'], 
            ':about' => $s['about']
        ]);
        $teacher_id = $pdo->lastInsertId();
    }

    $socials = json_decode($s['social_links'] ?? '[]', true);
    if (is_array($socials)) {
        $ins2 = $pdo->prepare("INSERT INTO social_links (teacher_id, type, url) VALUES (:t, :type, :url)");
        foreach ($socials as $sl) {
            if (!empty($sl['url'])) {
                $ins2->execute([':t' => $teacher_id, ':type' => $sl['type'] ?? '', ':url' => $sl['url']]);
            }
        }
    }

    $up = $pdo->prepare("UPDATE submissions SET status = 'APPROVED', reviewed_at = NOW() WHERE id = :id");
    $up->execute([':id' => $id]);

    $pdo->commit();
    $_SESSION['success'] = $teacher ? 'Данные преподавателя обновлены' : 'Заявка успешно утверждена';
} catch (Exception $ex) {
    $pdo->rollBack();
    $_SESSION['error'] = "Ошибка: " . $ex->getMessage();
}

header('Location: dashboard.php');
?>