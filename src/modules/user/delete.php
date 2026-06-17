<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn-delete'])) {
    $filterALl = filter();
    $ids = $filterALl['ids'] ?? [];

    if (!empty($ids)) {
        $ids = array_map('intval', $ids);
        $userId = getSession('id');

        // Collect all real transaction IDs (expand batch when selected)
        $allTxIds = [];
        foreach ($ids as $idVal) {
            $tx = getOne("SELECT id, batch_id FROM transaction WHERE id = :id AND user_id = :uid AND status = 'active'",
                         ['id' => $idVal, 'uid' => $userId]);
            if (!$tx) continue;
            if (!empty($tx['batch_id'])) {
                $batchRows = getAll("SELECT id FROM transaction WHERE batch_id = :bid AND user_id = :uid AND status = 'active'",
                                    ['bid' => $tx['batch_id'], 'uid' => $userId]) ?: [];
                foreach ($batchRows as $br) {
                    $allTxIds[$br['id']] = true;
                }
            } else {
                $allTxIds[$tx['id']] = true;
            }
        }

        $deleteIds = [];
        $pendingIds = [];
        $pendingWallets = [];

        foreach (array_keys($allTxIds) as $tId) {
            $tx = getOne("SELECT id, wallet_id, type, price, description FROM transaction WHERE id = :id AND user_id = :uid AND status = 'active'",
                         ['id' => $tId, 'uid' => $userId]);
            if (!$tx) continue;

            if ($tx['type'] === 'expense') {
                $deleteIds[] = $tx['id'];
            } elseif ($tx['wallet_id'] > 0) {
                $bal = getWalletBalance($tx['wallet_id'], $userId);
                $balAfter = $bal - (float)$tx['price'];
                if ($balAfter >= 0) {
                    $deleteIds[] = $tx['id'];
                } else {
                    update("transaction", ['status' => 'pending_delete'], "id = :id AND user_id = :uid",
                           ['id' => $tx['id'], 'uid' => $userId]);
                    $pendingIds[] = $tx['id'];
                    $walletInfo = getOne("SELECT name, icon FROM wallet WHERE id = :id", ['id' => $tx['wallet_id']]);
                    $wName = ($walletInfo['icon'] ?? '💰') . ' ' . ($walletInfo['name'] ?? '');
                    $pendingWallets[] = [
                        'tx_id' => $tx['id'],
                        'wallet_name' => $wName,
                        'current_balance' => $bal,
                        'price' => (float)$tx['price'],
                        'deficit' => abs($balAfter),
                    ];
                }
            } else {
                $deleteIds[] = $tx['id'];
            }
        }

        if (!empty($deleteIds)) {
            $placeholders = [];
            $params = ['user_id' => $userId];
            foreach ($deleteIds as $i => $idVal) {
                $key = "id$i";
                $placeholders[] = ":$key";
                $params[$key] = $idVal;
            }
            $inClause = implode(',', $placeholders);
            delete("transaction", "id IN ($inClause) AND user_id = :user_id", $params);
        }

        $msgParts = [];
        if (!empty($deleteIds)) {
            $msgParts[] = 'Đã xoá ' . count($deleteIds) . ' giao dịch';
        }
        if (!empty($pendingIds)) {
            $msgParts[] = count($pendingIds) . ' giao dịch thu nhập chuyển sang trạng thái chờ xử lý do số dư ví không đủ';
            setFlashData('pending_delete_wallets', $pendingWallets);
        }

        if (!empty($deleteIds)) {
            setMessage(implode('. ', $msgParts) . '.');
        } elseif (!empty($pendingIds)) {
            setMessage('Không thể xoá do số dư ví không đủ. Các giao dịch đã chuyển sang trạng thái chờ xử lý.', 'warning');
        } else {
            setMessage('Không có giao dịch nào được xoá.', 'error');
        }
    } else {
        setMessage("Vui lòng chọn ít nhất 1 giao dịch", "error");
    }
}

redirect("?template=user&action=filter");