<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Services\NotificationService;

/**
 * FundsController — handles deposits and transaction history.
 */
final class FundsController extends BaseController
{
    private Database $db;
    private NotificationService $notify;

    public function __construct()
    {
        parent::__construct();
        $this->db     = Database::getInstance();
        $this->notify = new NotificationService();
        $this->requireAuth();
    }

    // ── Add Funds Page ────────────────────────────────────────

    public function addFunds(array $params): void
    {
        $user = $this->currentUser();

        $paymentMethods = $this->getEnabledPaymentMethods();

        $pendingDeposits = $this->db->fetchAll(
            'SELECT * FROM smmPanel_deposits WHERE user_id = ? AND status = "pending" ORDER BY created_at DESC LIMIT 5',
            [$user['id']]
        );

        $this->view('dashboard/add-funds', [
            'title'          => 'Add Funds',
            'user'           => $user,
            'methods'        => $paymentMethods,
            'pendingDeposits'=> $pendingDeposits,
            'minDeposit'     => $this->getSetting('min_deposit', 100) / 100,
            'csrf'           => $this->generateCsrfToken(),
        ], 'dashboard');
    }

    // ── Submit Deposit ────────────────────────────────────────

    public function submitDeposit(array $params): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Security token mismatch.'], 403);
        }

        $userId  = $this->currentUserId();
        $method  = $_POST['method'] ?? '';
        $amount  = (float)($_POST['amount'] ?? 0);
        $trxId   = trim($_POST['transaction_id'] ?? '');
        $note    = trim($_POST['note'] ?? '');

        $validMethods = ['bkash', 'nagad', 'rocket', 'usdt_trc20', 'usdt_erc20', 'binance'];

        if (!in_array($method, $validMethods, true)) {
            $this->json(['success' => false, 'message' => 'Invalid payment method.']);
        }

        $minDeposit = $this->getSetting('min_deposit', 100) / 100;

        if ($amount < $minDeposit) {
            $this->json(['success' => false, 'message' => "Minimum deposit is \${$minDeposit}."]);
        }

        if (empty($trxId)) {
            $this->json(['success' => false, 'message' => 'Transaction ID is required.']);
        }

        // Check duplicate transaction ID
        $dupCheck = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_deposits WHERE transaction_id = ? AND method = ?',
            [$trxId, $method]
        );

        if ($dupCheck > 0) {
            $this->json(['success' => false, 'message' => 'This transaction ID has already been submitted.']);
        }

        // Handle proof screenshot
        $proofPath = null;

        if (!empty($_FILES['proof']['tmp_name'])) {
            $proofPath = $this->handleProofUpload($_FILES['proof'], $userId);

            if (!$proofPath) {
                $this->json(['success' => false, 'message' => 'Invalid proof file. Only JPEG/PNG/WebP under 5MB allowed.']);
            }
        }

        $depositId = $this->db->insert('smmPanel_deposits', [
            'user_id'        => $userId,
            'amount'         => $amount,
            'method'         => $method,
            'transaction_id' => $trxId,
            'proof_image'    => $proofPath,
            'note'           => $note,
            'status'         => 'pending',
            'ip_address'     => $this->getClientIp(),
        ]);

        $this->json([
            'success'    => true,
            'deposit_id' => $depositId,
            'message'    => 'Deposit submitted! Your balance will be credited after admin verification.',
        ]);
    }

    // ── Transactions ──────────────────────────────────────────

    public function transactions(array $params): void
    {
        $userId = $this->currentUserId();

        $pagination = $this->paginate(
            'SELECT * FROM smmPanel_transactions WHERE user_id = ? ORDER BY created_at DESC',
            [$userId],
            25
        );

        $this->view('dashboard/transactions', [
            'title'      => 'Transaction History',
            'user'       => $this->currentUser(),
            'pagination' => $pagination,
        ], 'dashboard');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function getEnabledPaymentMethods(): array
    {
        $methods = [];

        if ($this->getSetting('bkash_enabled')) {
            $methods['bkash'] = [
                'name'   => 'bKash',
                'icon'   => '/assets/img/payments/bkash.png',
                'number' => $this->getSetting('bkash_number'),
                'type'   => $this->getSetting('bkash_type', 'Personal'),
                'color'  => '#E2136E',
                'steps'  => [
                    'Open bKash app',
                    'Send Money to ' . $this->getSetting('bkash_number'),
                    'Enter the amount',
                    'Submit Transaction ID below',
                ],
            ];
        }

        if ($this->getSetting('nagad_enabled')) {
            $methods['nagad'] = [
                'name'   => 'Nagad',
                'icon'   => '/assets/img/payments/nagad.png',
                'number' => $this->getSetting('nagad_number'),
                'color'  => '#F6A623',
                'steps'  => [
                    'Open Nagad app',
                    'Send Money to ' . $this->getSetting('nagad_number'),
                    'Enter the amount',
                    'Submit Transaction ID below',
                ],
            ];
        }

        if ($this->getSetting('rocket_enabled')) {
            $methods['rocket'] = [
                'name'   => 'Rocket',
                'icon'   => '/assets/img/payments/rocket.png',
                'number' => $this->getSetting('rocket_number'),
                'color'  => '#8B4AE2',
                'steps'  => [
                    'Open Rocket (DBBL) app',
                    'Send Money to ' . $this->getSetting('rocket_number'),
                    'Enter the amount',
                    'Submit Transaction ID below',
                ],
            ];
        }

        if ($this->getSetting('usdt_trc20_enabled')) {
            $address = $this->getSetting('usdt_trc20_address');
            $methods['usdt_trc20'] = [
                'name'    => 'USDT TRC20',
                'icon'    => '/assets/img/payments/usdt.png',
                'address' => $address,
                'color'   => '#26A17B',
                'steps'   => [
                    'Copy the TRC20 wallet address below',
                    'Send USDT via Tron (TRC20) network',
                    'Minimum: $10 equivalent',
                    'Submit TXID below',
                ],
            ];
        }

        if ($this->getSetting('usdt_erc20_enabled')) {
            $address = $this->getSetting('usdt_erc20_address');
            $methods['usdt_erc20'] = [
                'name'    => 'USDT ERC20',
                'icon'    => '/assets/img/payments/usdt.png',
                'address' => $address,
                'color'   => '#627EEA',
                'steps'   => [
                    'Copy the ERC20 wallet address below',
                    'Send USDT via Ethereum (ERC20) network',
                    'Minimum: $20 equivalent',
                    'Submit TXID below',
                ],
            ];
        }

        if ($this->getSetting('binance_enabled')) {
            $methods['binance'] = [
                'name'   => 'Binance Pay',
                'icon'   => '/assets/img/payments/binance.png',
                'id'     => $this->getSetting('binance_id'),
                'color'  => '#F0B90B',
                'steps'  => [
                    'Open Binance app',
                    'Go to Pay → Send',
                    'Enter Binance Pay ID: ' . $this->getSetting('binance_id'),
                    'Submit Order ID below',
                ],
            ];
        }

        return $methods;
    }

    private function handleProofUpload(array $file, int $userId): string|false
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true) || $file['size'] > $maxSize) {
            return false;
        }

        $ext      = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $filename = 'deposit_' . $userId . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $dir      = dirname(__DIR__, 2) . '/public/assets/uploads/proofs/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return false;
        }

        return '/assets/uploads/proofs/' . $filename;
    }
}
