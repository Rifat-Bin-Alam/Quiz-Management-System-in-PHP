<?php
// includes/security.php - Security Helpers, Output Escaping & Password Migration

require_once __DIR__ . '/auth.php';

/**
 * Safe HTML escaping helper
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize integer IDs
 */
function validate_id($id): ?int {
    $filtered = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return ($filtered !== false) ? (int)$filtered : null;
}

/**
 * Verify input password against stored hash/plaintext with transparent self-healing rehash
 *
 * @param mysqli $conn
 * @param string $table (admin, teacher, student)
 * @param string $id_col (admin_id, teacher_id, student_id)
 * @param int $id_val
 * @param string $input_pass
 * @param string $stored_pass
 * @return bool
 */
function verify_and_rehash_password(mysqli $conn, string $table, string $id_col, int $id_val, string $input_pass, string $stored_pass): bool {
    // 1. Check if stored password matches modern password_hash
    if (password_verify($input_pass, $stored_pass)) {
        // If it needs rehashing to a newer cost or algorithm
        if (password_needs_rehash($stored_pass, PASSWORD_DEFAULT)) {
            $new_hash = password_hash($input_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE `$table` SET `password` = ? WHERE `$id_col` = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_hash, $id_val);
                $stmt->execute();
                $stmt->close();
            }
        }
        return true;
    }

    // 2. Compatibility check for legacy plaintext passwords from initial DB dump
    if ($input_pass === $stored_pass) {
        // Plaintext matched! Immediately migrate/upgrade to bcrypt hash
        $new_hash = password_hash($input_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE `$table` SET `password` = ? WHERE `$id_col` = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_hash, $id_val);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }

    return false;
}

/**
 * Server-side security guard: Block Admin Create, Edit, or Delete actions
 * Administrators have oversight/view access and teacher approval only.
 *
 * @param string $action_label
 */
function block_admin_action(string $action_label = 'Record modification'): void {
    if (is_admin()) {
        http_response_code(403);
        set_flash('error', "Action Restricted: {$action_label} is disabled for administrator accounts per security policy.");
        header("Location: admin_dashboard.php");
        exit();
    }
}
