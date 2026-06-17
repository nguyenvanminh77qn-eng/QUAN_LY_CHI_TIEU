<?php
/**
 * Long-Polling endpoint — optimized for near-instant delivery.
 *
 * Strategy:
 *  - First check: immediate (0ms) on arrival
 *  - If nothing found: short sleep 250ms, then 500ms, then 1s intervals
 *  - Holds connection up to MAX_WAIT seconds total
 *  - Returns as soon as new messages appear → typical latency < 300ms
 *
 * Client sends: ?mode=thread|global  &last_id=Y  [&thread_id=X]
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../Core/functions.php';
require_once __DIR__ . '/../../Core/session.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();

/* Close session EARLY so other tabs are not blocked waiting for session lock */
session_write_close();

require_once __DIR__ . '/../../Core/connect.php';
require_once __DIR__ . '/../../Core/database.php';
require_once __DIR__ . '/../../Helpers/feedback_helper.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no'); // Prevent Nginx/proxy buffering

$userId = (int) getSession('id');
$role   = getSession('role') ?? 'user';

if (!$userId) {
    jsonResponse(false, 'Unauthorized');
}

$mode     = $_GET['mode']      ?? 'global';  // 'thread' or 'global'
$threadId = (int) ($_GET['thread_id'] ?? 0);
$lastId   = (int) ($_GET['last_id']   ?? 0);

// Maximum hold time (seconds). Keep below PHP max_execution_time.
define('MAX_WAIT_MS', 20000); // 20 seconds in ms

// Adaptive sleep schedule (milliseconds):
// Check immediately, then short intervals, then longer as time passes.
// This gives ~100-300ms typical latency when a message arrives.
$sleepSchedule = [
    0,    // immediate first check
    150,  // +150ms
    150,  // +150ms  (total ~300ms)
    200,  // +200ms  (total ~500ms)
    250,  // +250ms  (total ~750ms)
    250,  // +250ms  (total ~1000ms)
    500,  // +500ms  (total ~1.5s)
    500,  // +500ms  (total ~2s)
    // after that: 1s intervals
];
$defaultInterval = 1000; // ms for remaining time

set_time_limit(25);
ignore_user_abort(false);

$startMs   = microtime(true) * 1000;
$stepIndex = 0;

while (true) {
    // Abort if client disconnected
    if (connection_aborted()) {
        exit;
    }

    // ── Check for new messages ──
    if ($mode === 'thread' && $threadId > 0) {
        $newMsgs = getNewMessagesInThread($threadId, $lastId);
        if (!empty($newMsgs)) {
            foreach ($newMsgs as &$m) {
                $m['sender_hash'] = getUserHash((int)$m['sender_id']);
            }
            jsonResponse(true, '', [
                'mode'     => 'thread',
                'messages' => $newMsgs,
                'last_id'  => (int) end($newMsgs)['id'],
            ]);
        }
    } else {
        $newMsgs = getNewMessagesForUser($userId, $lastId);
        if (!empty($newMsgs)) {
            $unreadCount = getUnreadCount($userId, $role);
            $threadIds   = [];
            foreach ($newMsgs as &$m) {
                $m['sender_hash'] = getUserHash((int)$m['sender_id']);
                $rootId = $m['parent_id'] ? (int) $m['parent_id'] : (int) $m['id'];
                $threadIds[$rootId] = true;
            }
            jsonResponse(true, '', [
                'mode'         => 'global',
                'messages'     => $newMsgs,
                'last_id'      => (int) end($newMsgs)['id'],
                'unread_count' => $unreadCount,
                'thread_ids'   => array_keys($threadIds),
            ]);
        }
    }

    // ── Check timeout ──
    $elapsedMs = microtime(true) * 1000 - $startMs;
    if ($elapsedMs >= MAX_WAIT_MS) {
        break;
    }

    // ── Sleep for next interval ──
    $sleepMs = isset($sleepSchedule[$stepIndex])
        ? $sleepSchedule[$stepIndex]
        : $defaultInterval;

    $stepIndex++;

    // Don't sleep past the timeout
    $remainingMs = MAX_WAIT_MS - $elapsedMs;
    $sleepMs     = min($sleepMs, (int) $remainingMs);

    if ($sleepMs > 0) {
        usleep($sleepMs * 1000); // usleep takes microseconds
    }
}

// Timeout — no new messages in MAX_WAIT_MS
jsonResponse(true, '', [
    'timeout' => true,
    'mode'    => $mode,
    'last_id' => $lastId,
]);
