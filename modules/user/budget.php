<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$userId = getSession('id');
if (!$userId) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}

// Xóa budget danh mục
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_budget'])) {
    $budgetId = (int)($_POST['budget_id'] ?? 0);
    $budget = getOne("SELECT id FROM budget WHERE id = :id AND user_id = :uid", ['id' => $budgetId, 'uid' => $userId]);
    if ($budget) {
        delete('budget', "id = :id", ['id' => $budgetId]);
        setMessage("Đã xóa ngân sách", "success");
    }
    redirect("?template=user&action=budget");
}

// Lưu budget danh mục
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_budget'])) {
    $filterAll = filter();
    $month = (int)($filterAll['month'] ?? date('m'));
    $year = (int)($filterAll['year'] ?? date('Y'));

    if (!empty($filterAll['amounts']) && is_array($filterAll['amounts'])) {
        foreach ($filterAll['amounts'] as $categoryId => $amount) {
            $categoryId = (int)$categoryId;
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
            $amount = (float)$amount;

            if ($categoryId <= 0) continue;

            $existing = getOne(
                "SELECT id FROM budget WHERE user_id = :uid AND category_id = :cid AND month = :m AND year = :y",
                ['uid' => $userId, 'cid' => $categoryId, 'm' => $month, 'y' => $year]
            );

            if ($amount > 0) {
                if ($existing) {
                    update('budget', ['amount' => $amount, 'updated_at' => date('Y-m-d H:i:s')], "id = :id", ['id' => $existing['id']]);
                } else {
                    insert('budget', [
                        'user_id' => $userId,
                        'category_id' => $categoryId,
                        'month' => $month,
                        'year' => $year,
                        'amount' => $amount,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } else {
                if ($existing) {
                    delete('budget', "id = :id", ['id' => $existing['id']]);
                }
            }
        }
        setMessage("Đã lưu ngân sách danh mục", "success");
    }

    $redirectUrl = !empty($filterAll['redirect']) ? $filterAll['redirect'] : '?template=user&action=budget';
    redirect($redirectUrl);
}

// Lưu monthly budget
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_monthly_budget'])) {
    $filterAll = filter();
    $month = (int)($filterAll['month'] ?? date('m'));
    $year = (int)($filterAll['year'] ?? date('Y'));
    $amount = $filterAll['monthly_amount'] ?? '';
    $amount = str_replace('.', '', $amount);
    $amount = str_replace(',', '.', $amount);
    $amount = (float)$amount;

    $existing = getOne(
        "SELECT id FROM monthly_budget WHERE user_id = :uid AND month = :m AND year = :y",
        ['uid' => $userId, 'm' => $month, 'y' => $year]
    );

    if ($amount > 0) {
        if ($existing) {
            update('monthly_budget', ['amount' => $amount, 'updated_at' => date('Y-m-d H:i:s')], "id = :id", ['id' => $existing['id']]);
        } else {
            insert('monthly_budget', [
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    } else {
        if ($existing) {
            delete('monthly_budget', "id = :id", ['id' => $existing['id']]);
        }
    }

    setMessage("Đã lưu ngân sách tháng", "success");
    $redirectUrl = !empty($filterAll['redirect']) ? $filterAll['redirect'] : '?template=user&action=budget';
    redirect($redirectUrl);
}
