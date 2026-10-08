<?php
/**
 * MonoTalk - действия админки
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/moderation.php';

requireAuth();
$currentUser = getCurrentUser();
if (empty($currentUser['verified'])) {
    header('Location: ' . BASE_URL . '403.php');
    exit;
}

$id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';
$redirect = $_GET['redirect'] ?? '/admin.php';
if (strpos($redirect, '://') !== false) $redirect = '/admin.php';

switch ($action) {
    case 'verify':
        $users = readData('users.json');
        foreach ($users as &$u) {
            if ((string)($u['id'] ?? '') === (string)$id) {
                $u['verified'] = empty($u['verified']);
                break;
            }
        }
        unset($u);
        writeData('users.json', $users);
        break;

    case 'ban':
        $users = readData('users.json');
        foreach ($users as &$u) {
            if ((string)($u['id'] ?? '') === (string)$id) {
                if (($u['status'] ?? 'active') === 'banned') {
                    $u['status'] = 'active';
                    $u['banned_at'] = null;
                } else {
                    $u['status'] = 'banned';
                    $u['banned_at'] = date('Y-m-d H:i:s');
                }
                break;
            }
        }
        unset($u);
        writeData('users.json', $users);
        break;

    case 'role':
        $users = readData('users.json');
        $roles = ['user', 'mod', 'admin'];
        foreach ($users as &$u) {
            if ((string)($u['id'] ?? '') === (string)$id) {
                $cur = $u['role'] ?? 'user';
                $u['role'] = $roles[(array_search($cur, $roles, true) + 1) % count($roles)];
                break;
            }
        }
        unset($u);
        writeData('users.json', $users);
        break;

    case 'delete_post':
        $posts = readData('posts.json');
        $posts = array_values(array_filter($posts, fn($p) => (string)($p['id'] ?? '') !== (string)$id));
        writeData('posts.json', $posts);
        $comments = readData('comments.json');
        $comments = array_values(array_filter($comments, fn($c) => (string)($c['post_id'] ?? '') !== (string)$id));
        writeData('comments.json', $comments);
        break;

    case 'delete_comment':
        $comments = readData('comments.json');
        $comments = array_values(array_filter($comments, fn($c) => (string)($c['id'] ?? '') !== (string)$id));
        writeData('comments.json', $comments);
        break;

    case 'delete_subreddit':
        $subs = readData('subreddits.json');
        $subs = array_values(array_filter($subs, fn($s) => (string)($s['id'] ?? '') !== (string)$id));
        writeData('subreddits.json', $subs);
        break;

    // ---- Действия по жалобам ----
    case 'report_resolve':
    case 'report_delete_post':
    case 'report_delete_comment':
    case 'report_strike':
        $reports = readData('reports.json');
        $report = null;
        foreach ($reports as &$r) {
            if ((string)($r['id'] ?? '') === (string)$id) {
                $report = &$r;
                break;
            }
        }
        unset($r);

        if ($report) {
            $report['status'] = 'resolved';
            $report['resolved_at'] = date('Y-m-d H:i:s');
            $report['resolved_by'] = (int)($currentUser['id'] ?? 0);

            $targetType = $report['target_type'] ?? '';
            $targetId = (int)($report['target_id'] ?? 0);
            $authorId = (int)($report['author_id'] ?? 0);
            $postId = (int)($report['post_id'] ?? 0);
            $reporterId = (int)($report['reporter_id'] ?? 0);

            if ($action === 'report_delete_post' && $targetType === 'post') {
                $posts = readData('posts.json');
                $posts = array_values(array_filter($posts, fn($p) => (int)($p['id'] ?? 0) !== $targetId));
                writeData('posts.json', $posts);
                $comments = readData('comments.json');
                $comments = array_values(array_filter($comments, fn($c) => (int)($c['post_id'] ?? 0) !== $targetId));
                writeData('comments.json', $comments);
                logModerationAction('report_post_deleted', ['report_id' => (int)$id, 'post_id' => $targetId, 'author_id' => $authorId]);
            } elseif ($action === 'report_delete_comment' && $targetType === 'comment') {
                $comments = readData('comments.json');
                $comments = array_values(array_filter($comments, fn($c) => (int)($c['id'] ?? 0) !== $targetId));
                writeData('comments.json', $comments);
                logModerationAction('report_comment_deleted', ['report_id' => (int)$id, 'comment_id' => $targetId, 'author_id' => $authorId]);
            } elseif ($action === 'report_strike' && $authorId > 0) {
                addUserStrike($authorId, 'Жалоба пользователей (жалоба #' . (int)$id . ')', 'medium', [
                    'report_id' => (int)$id,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                ]);
            }

            // Уведомляем жалобщика о результате
            if ($reporterId > 0) {
                $msg = 'Ваша жалоба рассмотрена и закрыта';
                if ($action === 'report_delete_post' || $action === 'report_delete_comment') {
                    $msg = 'Ваша жалоба рассмотрена: контент удалён';
                } elseif ($action === 'report_strike') {
                    $msg = 'Ваша жалоба рассмотрена: автору выдан страйк';
                }
                pushNotification($reporterId, 'report', $msg, $postId > 0 ? 'post.php?id=' . $postId : '');
            }
        }
        writeData('reports.json', $reports);
        break;
}

header('Location: ' . $redirect);
exit;
