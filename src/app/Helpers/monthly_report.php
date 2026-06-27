<?php
if (!defined('CODE')) die('Bạn không có quyền truy cập vào trang này');

require_once _WEB_PATH . 'vendor/fpdf/tfpdf.php';

class MonthlyReport
{
    private tFPDF $pdf;

    public function __construct()
    {
        $this->pdf = new tFPDF();
        $this->pdf->SetAutoPageBreak(true, 20);
    }

    private function setupFonts(): void
    {
        $this->pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $this->pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $this->pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
        $this->pdf->AddFont('DejaVu', 'BI', 'DejaVuSans-BoldOblique.ttf', true);
    }

    public function generate(int $userId, int $month, int $year): string
    {
        $user = getOne("SELECT id, username, email, phone, create_at FROM user WHERE id = :id", ['id' => $userId]);
        if (!$user) throw new RuntimeException("User #{$userId} not found");

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $income = (float)(getOne(
            "SELECT COALESCE(SUM(price),0) AS total FROM transaction
             WHERE user_id = :uid AND type = 'income' AND is_archived = 0
               AND transaction_date BETWEEN :s AND :e",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        )['total'] ?? 0);

        $expense = (float)(getOne(
            "SELECT COALESCE(SUM(price),0) AS total FROM transaction
             WHERE user_id = :uid AND type = 'expense' AND is_archived = 0
               AND transaction_date BETWEEN :s AND :e",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        )['total'] ?? 0);

        $categoryData = getAll(
            "SELECT c.id, c.name, c.icon,
                    SUM(CASE WHEN t.type = 'income' THEN t.price ELSE 0 END) AS total_income,
                    SUM(CASE WHEN t.type = 'expense' THEN t.price ELSE 0 END) AS total_expense,
                    COUNT(t.id) AS txn_count
             FROM transaction t
             JOIN category c ON c.id = t.category_id
             WHERE t.user_id = :uid AND t.is_archived = 0
               AND t.transaction_date BETWEEN :s AND :e
             GROUP BY c.id, c.name, c.icon
             ORDER BY total_expense DESC, total_income DESC",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        ) ?: [];

        $wallets = getAll(
            "SELECT w.id, w.name, w.icon, w.type,
                    COALESCE(inc.income,0) AS total_income,
                    COALESCE(exp.expense,0) AS total_expense
             FROM wallet w
             LEFT JOIN (SELECT wallet_id, SUM(price) AS income FROM transaction
                        WHERE user_id = :uid1 AND type = 'income' AND is_archived = 0
                        GROUP BY wallet_id) inc ON inc.wallet_id = w.id
             LEFT JOIN (SELECT wallet_id, SUM(price) AS expense FROM transaction
                        WHERE user_id = :uid2 AND type = 'expense' AND is_archived = 0
                        GROUP BY wallet_id) exp ON exp.wallet_id = w.id
             WHERE w.user_id = :uid3
             ORDER BY w.is_default DESC, w.id ASC",
            ['uid1' => $userId, 'uid2' => $userId, 'uid3' => $userId]
        ) ?: [];

        $recentTxns = getAll(
            "SELECT t.*, c.name AS cat_name, c.icon AS cat_icon, w.name AS wallet_name
             FROM transaction t
             LEFT JOIN category c ON c.id = t.category_id
             LEFT JOIN wallet w ON w.id = t.wallet_id
             WHERE t.user_id = :uid AND t.is_archived = 0
               AND t.transaction_date BETWEEN :s AND :e
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT 20",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        ) ?: [];

        $budgets = getAll(
            "SELECT b.*, c.name AS cat_name, c.icon AS cat_icon
             FROM budget b
             JOIN category c ON c.id = b.category_id
             WHERE b.user_id = :uid AND b.month = :m AND b.year = :y",
            ['uid' => $userId, 'm' => $month, 'y' => $year]
        ) ?: [];

        $pdf = $this->pdf;
        $this->setupFonts();
        $pdf->AddPage();

        $green  = [34, 197, 94];
        $red    = [239, 68, 68];
        $blue   = [13, 148, 136];
        $gold   = [212, 168, 67];
        $dark   = [15, 23, 42];
        $gray   = [100, 116, 139];
        $lgray  = [241, 245, 249];
        $white  = [255, 255, 255];

        $lMargin = 10;
        $pageW   = $pdf->GetPageWidth() - 20;

        // ── HEADER BANNER ──
        $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
        $pdf->Rect($lMargin, $pdf->GetY(), $pageW, 28, 'F');
        $yBanner = $pdf->GetY();
        $pdf->SetXY($lMargin + 5, $yBanner + 4);
        $pdf->SetFont('DejaVu', 'B', 16);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'BAO CAO TAI CHINH', 0, 1, 'C');
        $pdf->SetX($lMargin + 5);
        $pdf->SetFont('DejaVu', '', 11);
        $pdf->Cell(0, 8, sprintf('Thang %02d / %04d', $month, $year), 0, 1, 'C');
        $pdf->SetY($yBanner + 28 + 6);

        // ── USER INFO ──
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
        $pdf->Cell(0, 7, 'Thong tin nguoi dung', 0, 1);

        $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
        $pdf->Ln(3);

        $pdf->SetFont('DejaVu', '', 9);
        $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
        $pdf->Cell(0, 5, 'Ten: ' . $user['username'], 0, 1);
        $pdf->Cell(0, 5, 'Email: ' . $user['email'], 0, 1);
        $pdf->Cell(0, 5, 'Ngay tham gia: ' . date('d/m/Y', strtotime($user['create_at'])), 0, 1);
        $pdf->Ln(5);

        // ── MONTHLY SUMMARY (3 cards) ──
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
        $pdf->Cell(0, 7, 'Tong quan thu chi', 0, 1);
        $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
        $pdf->Ln(4);

        $net = $income - $expense;

        $cardW = ($pageW - 10) / 3;
        $cardH = 24;
        $y0 = $pdf->GetY();

        // Income card
        $pdf->SetFillColor($green[0], $green[1], $green[2]);
        $pdf->Rect($lMargin, $y0, $cardW, $cardH, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($lMargin + 4, $y0 + 3);
        $pdf->SetFont('DejaVu', '', 7.5);
        $pdf->Cell($cardW - 8, 5, 'Tong thu nhap', 0, 1);
        $pdf->SetX($lMargin + 4);
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->Cell($cardW - 8, 7, number_format($income, 0, ',', '.') . ' VND', 0, 1);

        // Expense card
        $x2 = $lMargin + $cardW + 5;
        $pdf->SetFillColor($red[0], $red[1], $red[2]);
        $pdf->Rect($x2, $y0, $cardW, $cardH, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x2 + 4, $y0 + 3);
        $pdf->SetFont('DejaVu', '', 7.5);
        $pdf->Cell($cardW - 8, 5, 'Tong chi tieu', 0, 1);
        $pdf->SetX($x2 + 4);
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->Cell($cardW - 8, 7, number_format($expense, 0, ',', '.') . ' VND', 0, 1);

        // Net card
        $x3 = $lMargin + ($cardW + 5) * 2;
        $netColor = $net >= 0 ? $green : $red;
        $pdf->SetFillColor($netColor[0], $netColor[1], $netColor[2]);
        $pdf->Rect($x3, $y0, $cardW, $cardH, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x3 + 4, $y0 + 3);
        $pdf->SetFont('DejaVu', '', 7.5);
        $pdf->Cell($cardW - 8, 5, 'Chenh lech', 0, 1);
        $pdf->SetX($x3 + 4);
        $pdf->SetFont('DejaVu', 'B', 10);
        $netLabel = ($net >= 0 ? '+' : '') . number_format($net, 0, ',', '.');
        $pdf->Cell($cardW - 8, 7, $netLabel . ' VND', 0, 1);

        $pdf->SetY($y0 + $cardH + 6);

        // ── BUDGET PROGRESS ──
        if (!empty($budgets)) {
            $pdf->SetFont('DejaVu', 'B', 10);
            $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
            $pdf->Cell(0, 7, 'Ngan sach theo danh muc', 0, 1);
            $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
            $pdf->Ln(4);

            foreach ($budgets as $b) {
                $bName = ($b['cat_icon'] ?? '') . ' ' . ($b['cat_name'] ?? '');
                $bSpent = (float)(getOne(
                    "SELECT COALESCE(SUM(price),0) AS total FROM transaction
                     WHERE user_id = :uid AND category_id = :cid AND type = 'expense'
                       AND is_archived = 0 AND transaction_date BETWEEN :s AND :e",
                    ['uid' => $userId, 'cid' => $b['category_id'], 's' => $monthStart, 'e' => $monthEnd]
                )['total'] ?? 0);
                $bLimit = (float)($b['amount'] ?? 0);
                $bPct = $bLimit > 0 ? min(100, round($bSpent / $bLimit * 100)) : 0;

                $pdf->SetFont('DejaVu', '', 8);
                $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
                $pdf->Cell(90, 5, mb_substr($bName, 0, 40), 0, 0);
                $pdf->SetFont('DejaVu', 'B', 8);
                $barColor = $bPct >= 90 ? $red : ($bPct >= 75 ? $gold : $green);
                $pdf->SetTextColor($barColor[0], $barColor[1], $barColor[2]);
                $pdf->Cell(20, 5, number_format($bSpent, 0, ',', '.') . 'd', 0, 0, 'R');
                $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
                $pdf->Cell(20, 5, '/' . number_format($bLimit, 0, ',', '.') . 'd', 0, 0, 'R');
                $pdf->SetFont('DejaVu', 'B', 8);
                $pdf->Cell(15, 5, "{$bPct}%", 0, 0, 'R');

                $barX = $lMargin + 145;
                $barY = $pdf->GetY() + 1;
                $barW = 40;
                $barH = 3;
                $pdf->SetFillColor(226, 232, 240);
                $pdf->Rect($barX, $barY, $barW, $barH, 'F');
                $barColor = $bPct >= 90 ? $red : ($bPct >= 75 ? $gold : $green);
                $pdf->SetFillColor($barColor[0], $barColor[1], $barColor[2]);
                if ($bPct > 0) {
                    $pdf->Rect($barX, $barY, $barW * $bPct / 100, $barH, 'F');
                }
                $pdf->Ln(6);
            }
            $pdf->Ln(2);
        }

        // ── CATEGORY BREAKDOWN ──
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
        $pdf->Cell(0, 7, 'Chi tiet theo danh muc', 0, 1);
        $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
        $pdf->Ln(3);

        if (!empty($categoryData)) {
            $colW = [8, 48, 34, 34, 34, 16];
            $headers = ['', 'Danh muc', 'Thu nhap', 'Chi tieu', 'Chenh lech', 'SL'];
            $pdf->SetFont('DejaVu', 'B', 7);
            $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
            $pdf->SetTextColor(255, 255, 255);
            $x0 = $pdf->GetX();
            $y0 = $pdf->GetY();
            foreach ($headers as $i => $h) {
                $pdf->Cell($colW[$i], 7, $h, 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('DejaVu', '', 7.5);
            $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
            $fill = false;
            foreach ($categoryData as $row) {
                $catNet = (float)$row['total_income'] - (float)$row['total_expense'];
                $pdf->SetFillColor($lgray[0], $lgray[1], $lgray[2]);
                $x0 = $pdf->GetX();
                $y0 = $pdf->GetY();

                if ($y0 > 270) {
                    $pdf->AddPage();
                    $pdf->SetFont('DejaVu', 'B', 7);
                    $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
                    $pdf->SetTextColor(255, 255, 255);
                    foreach ($headers as $i => $h) {
                        $pdf->Cell($colW[$i], 7, $h, 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('DejaVu', '', 7.5);
                    $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
                    $y0 = $pdf->GetY();
                }

                $pdf->Cell($colW[0], 6, $row['icon'], 1, 0, 'C', $fill);
                $pdf->Cell($colW[1], 6, mb_substr($row['name'], 0, 22), 1, 0, 'L', $fill);
                $pdf->Cell($colW[2], 6, $row['total_income'] > 0 ? number_format($row['total_income'], 0, ',', '.') . 'd' : '-', 1, 0, 'R', $fill);
                $pdf->Cell($colW[3], 6, $row['total_expense'] > 0 ? number_format($row['total_expense'], 0, ',', '.') . 'd' : '-', 1, 0, 'R', $fill);
                $catNetColor = $catNet >= 0 ? $green : $red;
                $pdf->SetTextColor($catNetColor[0], $catNetColor[1], $catNetColor[2]);
                $pdf->Cell($colW[4], 6, number_format($catNet, 0, ',', '.') . 'd', 1, 0, 'R', $fill);
                $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
                $pdf->Cell($colW[5], 6, (string)$row['txn_count'], 1, 0, 'C', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }
        } else {
            $pdf->SetFont('DejaVu', '', 9);
            $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
            $pdf->Cell(0, 6, 'Khong co giao dich trong thang nay.', 0, 1);
        }
        $pdf->Ln(6);

        // ── WALLET BALANCES ──
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
        $pdf->Cell(0, 7, 'So du vi', 0, 1);
        $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
        $pdf->Ln(3);

        if (!empty($wallets)) {
            $colW = [8, 50, 42, 42];
            $headers = ['', 'Vi', 'Tong thu', 'Tong chi'];
            $pdf->SetFont('DejaVu', 'B', 7);
            $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
            $pdf->SetTextColor(255, 255, 255);
            foreach ($headers as $i => $h) {
                $pdf->Cell($colW[$i], 7, $h, 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('DejaVu', '', 7.5);
            $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
            $fill = false;
            foreach ($wallets as $w) {
                $pdf->SetFillColor($lgray[0], $lgray[1], $lgray[2]);
                if ($pdf->GetY() > 270) $pdf->AddPage();
                $pdf->Cell($colW[0], 6, $w['icon'], 1, 0, 'C', $fill);
                $pdf->Cell($colW[1], 6, mb_substr($w['name'], 0, 25), 1, 0, 'L', $fill);
                $pdf->Cell($colW[2], 6, number_format($w['total_income'], 0, ',', '.') . 'd', 1, 0, 'R', $fill);
                $pdf->Cell($colW[3], 6, number_format($w['total_expense'], 0, ',', '.') . 'd', 1, 0, 'R', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }
        } else {
            $pdf->SetFont('DejaVu', '', 9);
            $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
            $pdf->Cell(0, 6, 'Khong co vi nao.', 0, 1);
        }
        $pdf->Ln(6);

        // ── RECENT TRANSACTIONS ──
        $pdf->SetFont('DejaVu', 'B', 10);
        $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
        $pdf->Cell(0, 7, 'Giao dich gan day', 0, 1);
        $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lMargin, $pdf->GetY(), $lMargin + 40, $pdf->GetY());
        $pdf->Ln(3);

        if (!empty($recentTxns)) {
            $colW = [17, 40, 32, 14, 42];
            $headers = ['Ngay', 'Danh muc', 'So tien', 'Loai', 'Mo ta'];
            $pdf->SetFont('DejaVu', 'B', 7);
            $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
            $pdf->SetTextColor(255, 255, 255);
            foreach ($headers as $i => $h) {
                $pdf->Cell($colW[$i], 7, $h, 1, 0, 'C', true);
            }
            $pdf->Ln();

            $pdf->SetFont('DejaVu', '', 7);
            $pdf->SetTextColor($dark[0], $dark[1], $dark[2]);
            $fill = false;
            foreach ($recentTxns as $tx) {
                $pdf->SetFillColor($lgray[0], $lgray[1], $lgray[2]);
                if ($pdf->GetY() > 270) $pdf->AddPage();
                $price = number_format($tx['price'], 0, ',', '.');
                $typeLabel = $tx['type'] === 'income' ? 'Thu' : 'Chi';
                $desc = ($tx['wallet_name'] ?? '') . ' ' . mb_substr($tx['description'] ?? '', 0, 15);
                $catName = ($tx['cat_icon'] ?? '') . ' ' . mb_substr($tx['cat_name'] ?? '', 0, 12);

                $pdf->Cell($colW[0], 6, date('d/m', strtotime($tx['transaction_date'])), 1, 0, 'C', $fill);
                $pdf->Cell($colW[1], 6, $catName, 1, 0, 'L', $fill);
                $pdf->Cell($colW[2], 6, $price . 'd', 1, 0, 'R', $fill);
                $pdf->Cell($colW[3], 6, $typeLabel, 1, 0, 'C', $fill);
                $pdf->Cell($colW[4], 6, $desc, 1, 0, 'L', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }
        } else {
            $pdf->SetFont('DejaVu', '', 9);
            $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
            $pdf->Cell(0, 6, 'Khong co giao dich trong thang nay.', 0, 1);
        }

        // ── FOOTER ──
        $pdf->SetY(-15);
        $pdf->SetFont('DejaVu', '', 7);
        $pdf->SetTextColor($gray[0], $gray[1], $gray[2]);
        $pdf->Cell(0, 10, 'Bao cao duoc tao luc ' . date('H:i d/m/Y') . ' | Trang ' . $pdf->PageNo() . '/{nb}', 0, 0, 'C');

        $filename = sprintf('report_%d_%04d_%02d.pdf', $userId, $year, $month);
        $filepath = _WEB_PATH . 'storage/temp/' . $filename;
        $pdf->AliasNbPages();
        $pdf->Output('F', $filepath);
        return $filepath;
    }

    public function generateAndSendAll(int $month, int $year): array
    {
        $users = getAll("SELECT id, username, email FROM user WHERE role = 'user'");
        return $this->sendToUsers($users, $month, $year);
    }

    public function generateAndSendSelected(int $month, int $year, array $userIds): array
    {
        if (empty($userIds)) return [];
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $users = getAll(
            "SELECT id, username, email FROM user WHERE role = 'user' AND id IN ($placeholders)",
            $userIds
        );
        return $this->sendToUsers($users, $month, $year);
    }

    private function sendToUsers(array $users, int $month, int $year): array
    {
        $results = [];
        foreach ($users as $user) {
            try {
                $pdfPath = $this->generate((int)$user['id'], $month, $year);
                $subject = sprintf('Bao cao tai chinh thang %02d/%04d', $month, $year);
                $body = sprintf(
                    'Chao %s,<br><br>Day la bao cao tai chinh thang %02d/%04d cua ban.<br>',
                    htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'), $month, $year
                );
                $body .= 'Xem file dinh kem ben duoi.<br><br>';
                $body .= '--<br>He thong quan ly chi tieu';

                $sent = sendMail($user['email'], $subject, $body, $pdfPath);
                $results[] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'success' => $sent,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        return $results;
    }
}
