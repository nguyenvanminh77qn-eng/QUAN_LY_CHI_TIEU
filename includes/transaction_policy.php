<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

const MAX_TRANSACTIONS_PER_USER = 500;
const MAX_TRANSACTIONS_PER_DAY = 20;
const TRANSACTION_RETENTION_DAYS = 365;
const SUSPICIOUS_BALANCE_RATIO = 0.5;
const DUPLICATE_WINDOW_MINUTES = 5;
const RATE_LIMIT_SECONDS = 3;
const NEGATIVE_BALANCE_LIMIT = -1000000;
const BUDGET_OVERAGE_LIMIT = 1.1; // Cho phép vượt tối đa 110% ngân sách

function validateTransaction($userId, $data, $editingId = null) {
    $errors = [];
    $warnings = [];

    $basicErrors = validateBasicFields($data);
    if (!empty($basicErrors)) {
        $errors = array_merge($errors, $basicErrors);
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
    }

    $price = (float) $data['price'];
    $type = $data['type'];

    if ($editingId === null) {
        $rateError = checkRateLimit($userId);
        if ($rateError !== null) {
            $errors['rate_limit'] = $rateError;
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    if ($editingId === null) {
        $limitError = checkTransactionLimits($userId);
        if ($limitError !== null) {
            $errors['limit'] = $limitError;
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    if ($type === 'expense') {
        $balanceError = checkBalanceSufficient($userId, $price, $editingId);
        if ($balanceError !== null) {
            $errors['balance'] = $balanceError;
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $budgetError = checkBudgetLimit($userId, (int)($data['category_id'] ?? 0), $price, $data['transaction_date'] ?? null, $editingId);
        if ($budgetError !== null) {
            $errors['budget'] = $budgetError;
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $monthlyError = checkMonthlyBudgetLimit($userId, $price, $data['transaction_date'] ?? null, $editingId);
        if ($monthlyError !== null) {
            $errors['monthly_budget'] = $monthlyError;
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    $suspiciousReasons = checkSuspicious(
        $userId,
        $price,
        $type,
        $data['category_id'] ?? null,
        $data['evidence_reference'] ?? '',
        $editingId
    );
    if (!empty($suspiciousReasons)) {
        $warnings['suspicious'] = $suspiciousReasons;
    }

    return ['valid' => true, 'errors' => [], 'warnings' => $warnings];
}

function validateBasicFields($data) {
    $errors = [];

    $price = isset($data['price']) ? trim((string) $data['price']) : '';
    if ($price === '') {
        $errors['price']['required'] = 'Số tiền không được để trống.';
    } elseif (!filter_var($price, FILTER_VALIDATE_FLOAT) || (float) $price <= 0) {
        $errors['price']['min'] = 'Số tiền phải lớn hơn 0.';
    }

    $categoryId = isset($data['category_id']) ? trim((string) $data['category_id']) : '';
    if ($categoryId === '' || !ctype_digit($categoryId)) {
        $errors['category']['required'] = 'Vui lòng chọn danh mục.';
    } else {
        $catExists = getOne("SELECT id FROM category WHERE id = :id", ['id' => (int) $categoryId]);
        if (!$catExists) {
            $errors['category']['invalid'] = 'Danh mục không tồn tại.';
        }
    }

    $transactionDate = isset($data['transaction_date']) ? trim((string) $data['transaction_date']) : '';
    if ($transactionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
        $errors['transactionDate']['invalid'] = 'Ngày giao dịch không hợp lệ.';
    } elseif ($transactionDate !== '' && strtotime($transactionDate) > strtotime(date('Y-m-d'))) {
        $errors['transactionDate']['future'] = 'Ngày giao dịch không được lớn hơn ngày hiện tại.';
    }

    $type = isset($data['type']) ? trim((string) $data['type']) : '';
    if (!in_array($type, ['income', 'expense'], true)) {
        $errors['type']['invalid'] = 'Loại giao dịch phải là Thu hoặc Chi.';
    }

    $description = isset($data['description']) ? (string) $data['description'] : '';
    if (mb_strlen($description, 'UTF-8') > 100) {
        $errors['description']['maxlength'] = 'Mô tả không được vượt quá 100 ký tự.';
    }

    return $errors;
}

function checkTransactionLimits($userId) {
    $totalCount = getOne(
        "SELECT COUNT(*) as cnt FROM transaction WHERE user_id = :id AND is_archived = 0",
        ['id' => $userId]
    );
    if ($totalCount && (int) $totalCount['cnt'] >= MAX_TRANSACTIONS_PER_USER) {
        return 'Bạn đã đạt giới hạn tối đa ' . MAX_TRANSACTIONS_PER_USER . ' giao dịch. Vui lòng xóa bớt giao dịch cũ.';
    }

    $today = date('Y-m-d');
    $dailyCount = getOne(
        "SELECT COUNT(*) as cnt FROM transaction WHERE user_id = :id AND DATE(create_at) = :today AND is_archived = 0",
        ['id' => $userId, 'today' => $today]
    );
    if ($dailyCount && (int) $dailyCount['cnt'] >= MAX_TRANSACTIONS_PER_DAY) {
        return 'Bạn đã đạt giới hạn ' . MAX_TRANSACTIONS_PER_DAY . ' giao dịch trong ngày hôm nay. Vui lòng thử lại vào ngày mai.';
    }

    return null;
}

function archiveExpiredTransactions($userId) {
    $cutoffDate = date('Y-m-d', strtotime('-' . TRANSACTION_RETENTION_DAYS . ' days'));

    $countResult = getOne(
        "SELECT COUNT(*) as cnt FROM transaction WHERE user_id = :id AND transaction_date < :cutoff AND is_archived = 0",
        ['id' => $userId, 'cutoff' => $cutoffDate]
    );
    $archivedCount = $countResult ? (int) $countResult['cnt'] : 0;

    if ($archivedCount > 0) {
        query(
            "UPDATE transaction SET is_archived = 1 WHERE user_id = :id AND transaction_date < :cutoff AND is_archived = 0",
            ['id' => $userId, 'cutoff' => $cutoffDate]
        );
    }

    return $archivedCount;
}

function getCurrentBalance($userId, $excludeTransactionId = null, $upToDate = null) {
    $conditions = ["user_id = :id", "is_archived = 0"];
    $params = ['id' => $userId];

    if ($excludeTransactionId !== null) {
        $conditions[] = "transaction.id != :exclude_id";
        $params['exclude_id'] = $excludeTransactionId;
    }

    if ($upToDate !== null) {
        $conditions[] = "transaction.transaction_date <= :up_to_date";
        $params['up_to_date'] = $upToDate;
    }

    $where = implode(' AND ', $conditions);

    $incomeResult = getOne(
        "SELECT COALESCE(SUM(price), 0) as total FROM transaction WHERE $where AND type = 'income'",
        $params
    );
    $expenseResult = getOne(
        "SELECT COALESCE(SUM(price), 0) as total FROM transaction WHERE $where AND type = 'expense'",
        $params
    );

    $income = $incomeResult ? (float) $incomeResult['total'] : 0;
    $expense = $expenseResult ? (float) $expenseResult['total'] : 0;

    return $income - $expense;
}

function checkBalanceSufficient($userId, $price, $editingId = null) {
    $balance = getCurrentBalance($userId, $editingId);
    $balanceAfter = $balance - $price;

    if ($balanceAfter < NEGATIVE_BALANCE_LIMIT) {
        $balanceFormatted = number_format($balance, 0, ',', '.');
        $priceFormatted = number_format($price, 0, ',', '.');
        $limitFormatted = number_format(abs(NEGATIVE_BALANCE_LIMIT), 0, ',', '.');

        return "Số dư hiện tại: {$balanceFormatted}đ. Giao dịch {$priceFormatted}đ sẽ vượt quá giới hạn âm cho phép (-{$limitFormatted}đ). Vui lòng thêm thu nhập hoặc giảm số tiền.";
    }

    return null;
}

function checkBudgetLimit($userId, $categoryId, $newAmount, $transactionDate = null, $editingId = null) {
    if ($categoryId <= 0) return null;

    $date = $transactionDate ?: date('Y-m-d');
    $month = (int)date('m', strtotime($date));
    $year = (int)date('Y', strtotime($date));

    $budget = getOne(
        "SELECT amount FROM budget WHERE user_id = :uid AND category_id = :cid AND month = :m AND year = :y",
        ['uid' => $userId, 'cid' => $categoryId, 'm' => $month, 'y' => $year]
    );
    if (!$budget || (float)$budget['amount'] <= 0) return null;

    $budgetAmount = (float)$budget['amount'];
    $maxAllowed = $budgetAmount * BUDGET_OVERAGE_LIMIT;

    $excludeCondition = '';
    $params = ['uid' => $userId, 'cid' => $categoryId, 'm' => $month, 'y' => $year];
    if ($editingId !== null) {
        $excludeCondition = " AND t.id != :eid";
        $params['eid'] = $editingId;
    }

    $spentResult = getOne(
        "SELECT COALESCE(SUM(t.price), 0) as total
         FROM transaction t
         WHERE t.user_id = :uid AND t.category_id = :cid
           AND t.type = 'expense' AND t.is_archived = 0
           AND MONTH(t.transaction_date) = :m AND YEAR(t.transaction_date) = :y
           $excludeCondition",
        $params
    );
    $spent = $spentResult ? (float)$spentResult['total'] : 0;
    $totalAfterTransaction = $spent + $newAmount;

    if ($totalAfterTransaction > $maxAllowed) {
        $budgetFormatted = number_format($budgetAmount, 0, ',', '.');
        $spentFormatted = number_format($spent, 0, ',', '.');
        $maxFormatted = number_format($maxAllowed, 0, ',', '.');
        $newFormatted = number_format($newAmount, 0, ',', '.');
        $overPercent = round(($totalAfterTransaction / $budgetAmount) * 100, 1);

        return "Ngân sách danh mục này là {$budgetFormatted}đ/tháng (tối đa {$maxFormatted}đ). "
             . "Bạn đã chi {$spentFormatted}đ, thêm {$newFormatted}đ sẽ là {$overPercent}% ngân sách, "
             . "vượt quá giới hạn cho phép 110%.";
    }

    return null;
}

function checkMonthlyBudgetLimit($userId, $newAmount, $transactionDate = null, $editingId = null) {
    $date = $transactionDate ?: date('Y-m-d');
    $month = (int)date('m', strtotime($date));
    $year = (int)date('Y', strtotime($date));

    $mb = getOne(
        "SELECT amount FROM monthly_budget WHERE user_id = :uid AND month = :m AND year = :y",
        ['uid' => $userId, 'm' => $month, 'y' => $year]
    );
    if (!$mb || (float)$mb['amount'] <= 0) return null;

    $budgetAmount = (float)$mb['amount'];
    $maxAllowed = $budgetAmount * BUDGET_OVERAGE_LIMIT;

    $excludeCondition = '';
    $params = ['uid' => $userId, 'm' => $month, 'y' => $year];
    if ($editingId !== null) {
        $excludeCondition = " AND id != :eid";
        $params['eid'] = $editingId;
    }

    $spentRow = getOne(
        "SELECT COALESCE(SUM(price), 0) as total
         FROM transaction
         WHERE user_id = :uid AND type = 'expense' AND is_archived = 0
           AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y
           $excludeCondition",
        $params
    );
    $spent = $spentRow ? (float)$spentRow['total'] : 0;
    $totalAfter = $spent + $newAmount;

    if ($totalAfter > $maxAllowed) {
        $budgetFormatted = number_format($budgetAmount, 0, ',', '.');
        $spentFormatted = number_format($spent, 0, ',', '.');
        $maxFormatted = number_format($maxAllowed, 0, ',', '.');
        $newFormatted = number_format($newAmount, 0, ',', '.');
        $overPercent = round(($totalAfter / $budgetAmount) * 100, 1);

        return "Ngân sách tháng {$budgetFormatted}đ (tối đa {$maxFormatted}đ). "
             . "Bạn đã chi {$spentFormatted}đ, thêm {$newFormatted}đ sẽ là {$overPercent}% "
             . "vượt quá giới hạn cho phép 110%.";
    }

    return null;
}

function checkSuspicious($userId, $price, $type, $categoryId = null, $evidenceReference = '', $editingId = null) {
    $reasons = [];

    if ($type === 'expense') {
        $balance = getCurrentBalance($userId, $editingId);
        if ($balance > 0 && $price > ($balance * SUSPICIOUS_BALANCE_RATIO)) {
            $balanceFormatted = number_format($balance, 0, ',', '.');
            $priceFormatted = number_format($price, 0, ',', '.');
            $percent = round(($price / $balance) * 100);
            $reasons[] = "Số tiền {$priceFormatted}đ chiếm {$percent}% số dư hiện tại ({$balanceFormatted}đ).";
        }
    }

    if ($categoryId !== null) {
        $windowTime = date('Y-m-d H:i:s', strtotime('-' . DUPLICATE_WINDOW_MINUTES . ' minutes'));
        $duplicate = getOne(
            "SELECT id FROM transaction WHERE user_id = :uid AND price = :price AND category_id = :cat AND type = :type AND create_at >= :window AND is_archived = 0" .
            ($editingId !== null ? " AND id != :eid" : ""),
            array_merge(
                ['uid' => $userId, 'price' => $price, 'cat' => $categoryId, 'type' => $type, 'window' => $windowTime],
                $editingId !== null ? ['eid' => $editingId] : []
            )
        );
        if ($duplicate) {
            $reasons[] = 'Phát hiện giao dịch trùng lặp (cùng số tiền và danh mục) trong vài phút gần đây.';
        }
    }

    if (!empty($evidenceReference)) {
        $duplicateReference = getOne(
            "SELECT id FROM transaction WHERE user_id = :uid AND evidence_reference = :ref AND is_archived = 0" .
            ($editingId !== null ? " AND id != :eid" : "") .
            " LIMIT 1",
            array_merge(
                ['uid' => $userId, 'ref' => trim((string) $evidenceReference)],
                $editingId !== null ? ['eid' => $editingId] : []
            )
        );
        if ($duplicateReference) {
            $reasons[] = 'Mã tham chiếu này đã tồn tại ở một giao dịch khác. Vui lòng kiểm tra lại.';
        }
    }

    $currentHour = (int) date('H');
    if ($currentHour >= 0 && $currentHour < 5) {
        $reasons[] = 'Giao dịch được tạo ngoài giờ thông thường (00:00 - 05:00).';
    }

    return $reasons;
}

function checkRateLimit($userId) {
    $lastTransaction = getOne(
        "SELECT create_at FROM transaction WHERE user_id = :id ORDER BY create_at DESC LIMIT 1",
        ['id' => $userId]
    );

    if ($lastTransaction && !empty($lastTransaction['create_at'])) {
        $lastTime = strtotime($lastTransaction['create_at']);
        $now = time();
        $diff = $now - $lastTime;

        if ($diff < RATE_LIMIT_SECONDS) {
            $waitSeconds = RATE_LIMIT_SECONDS - $diff;
            return "Vui lòng đợi thêm {$waitSeconds} giây trước khi tạo giao dịch tiếp theo.";
    }
    }

    return null;
}
