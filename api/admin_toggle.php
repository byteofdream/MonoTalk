<?php
/**
 * MonoTalk - действия админки
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

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
}

header('Location: ' . $redirect);
exit;
