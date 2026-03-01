<?php
// inc/functions.php
session_start();

function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}
function check_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function captcha_check($userValue, $captchaId = null) {
    global $pdo;
    
    $expTime = time() - 300;
    $userIP = $_SERVER['REMOTE_ADDR'];
    $userValue = strtolower(trim($userValue));
    
    if (empty($userValue)) {
        return false;
    }
    
    if ($captchaId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS count
            FROM captcha
            WHERE captcha_id = :id
              AND ip_address = :ip
              AND captcha_text = :text
              AND UNIX_TIMESTAMP(captcha_time) > :expTime
        ");
        $stmt->execute([
            ':id'      => $captchaId,
            ':ip'      => $userIP,
            ':text'    => $userValue,
            ':expTime' => $expTime,
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS count
            FROM captcha
            WHERE ip_address = :ip
              AND captcha_text = :text
              AND UNIX_TIMESTAMP(captcha_time) > :expTime
        ");
        $stmt->execute([
            ':ip'      => $userIP,
            ':text'    => $userValue,
            ':expTime' => $expTime,
        ]);
    }
    
    $count = (int)$stmt->fetchColumn();
    
    if ($captchaId) {
        $delStmt = $pdo->prepare("DELETE FROM captcha WHERE captcha_id = :id");
        $delStmt->execute([':id' => $captchaId]);
    }
    
    return $count === 1;
}

function captcha_html($formId = 'captcha') {
    $html = '<div class="captcha-container">';
    $html .= '<img src="captcha.php?v=' . time() . '" alt="CAPTCHA" id="' . $formId . '_image" style="border: 1px solid #ddd; border-radius: var(--radius);">';
    $html .= '<div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">';
    $html .= '<button type="button" onclick="refreshCaptcha(\'' . $formId . '\')" class="btn btn-outline btn-sm" title="Обновить капчу">';
    $html .= '<i class="fas fa-redo"></i> Обновить';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

function format_years($years) {
    if (!$years) return '';
    
    $years = (int)$years;
    $lastDigit = $years % 10;
    $lastTwoDigits = $years % 100;
    
    if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
        return $years . ' лет опыта';
    } elseif ($lastDigit == 1) {
        return $years . ' год опыта';
    } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
        return $years . ' года опыта';
    } else {
        return $years . ' лет опыта';
    }
}

function plural_form($number, $titles) {
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}