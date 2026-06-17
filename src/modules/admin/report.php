<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$role = getSession('role');
if ($role !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=auth&action=login.view");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_all') {
        $month = (int)($_POST['month'] ?? 0);
        $year  = (int)($_POST['year'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
            setMessage("Tháng hoặc năm không hợp lệ", "error");
            redirect("?template=admin&action=report");
        }

        require_once __DIR__ . '/../../app/Helpers/monthly_report.php';

        $report = new MonthlyReport();
        $results = $report->generateAndSendAll($month, $year);

        $successIds = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => $r['success']));
        $failIds    = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => !$r['success']));

        setMessage("Đã gửi báo cáo.", !empty($failIds) ? 'warning' : 'success');
        redirect("?template=admin&action=report&sent=1"
            . "&sid=" . implode(',', $successIds)
            . "&fid=" . implode(',', $failIds));
    }

    if ($action === 'send_selected') {
        $month = (int)($_POST['month'] ?? 0);
        $year  = (int)($_POST['year'] ?? 0);
        $userIds = $_POST['user_ids'] ?? [];

        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
            setMessage("Tháng hoặc năm không hợp lệ", "error");
            redirect("?template=admin&action=report");
        }

        if (empty($userIds) || !is_array($userIds)) {
            setMessage("Vui lòng chọn ít nhất một người dùng.", "error");
            redirect("?template=admin&action=report");
        }

        $userIds = array_map('intval', $userIds);

        require_once __DIR__ . '/../../app/Helpers/monthly_report.php';

        $report = new MonthlyReport();
        $results = $report->generateAndSendSelected($month, $year, $userIds);

        $successIds = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => $r['success']));
        $failIds    = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => !$r['success']));

        setMessage("Đã gửi báo cáo.", !empty($failIds) ? 'warning' : 'success');
        redirect("?template=admin&action=report&sent=1"
            . "&sid=" . implode(',', $successIds)
            . "&fid=" . implode(',', $failIds));
    }

    if ($action === 'send_one') {
        $month  = (int)($_POST['month'] ?? 0);
        $year   = (int)($_POST['year'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100 || !$userId) {
            setMessage("Thông tin không hợp lệ", "error");
            redirect("?template=admin&action=report");
        }

        require_once __DIR__ . '/../../app/Helpers/monthly_report.php';

        $report = new MonthlyReport();
        $results = $report->generateAndSendSelected($month, $year, [$userId]);

        $successIds = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => $r['success']));
        $failIds    = array_map(fn($r) => $r['user_id'], array_filter($results, fn($r) => !$r['success']));

        setMessage("Đã gửi báo cáo.", !empty($failIds) ? 'warning' : 'success');
        redirect("?template=admin&action=report&sent=1"
            . "&sid=" . implode(',', $successIds)
            . "&fid=" . implode(',', $failIds));
    }

    if ($action === 'preview') {
        $month  = (int)($_POST['month'] ?? 0);
        $year   = (int)($_POST['year'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100 || !$userId) {
            setMessage("Thông tin không hợp lệ", "error");
            redirect("?template=admin&action=report");
        }

        require_once __DIR__ . '/../../app/Helpers/monthly_report.php';

        try {
            $report = new MonthlyReport();
            $path = $report->generate($userId, $month, $year);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="report_' . $userId . '_' . $year . '_' . sprintf('%02d', $month) . '.pdf"');
            readfile($path);
            exit;
        } catch (Exception $e) {
            setMessage("Lỗi tạo báo cáo: " . $e->getMessage(), "error");
            redirect("?template=admin&action=report");
        }
    }

    redirect("?template=admin&action=report");
}
