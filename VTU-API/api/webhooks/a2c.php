<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../notifications/send.php';

use Binali\Config\Database;
use PDO;

// Log webhook for debugging
$webhook_data = file_get_contents("php://input");
$webhook_json = json_decode($webhook_data, true);

error_log("[A2C Webhook] Received: " . json_encode($webhook_json));

try {
    // Validate webhook data
    if (!$webhook_json) {
        throw new Exception('Invalid JSON payload');
    }

    $ref = $webhook_json['ref'] ?? null;
    $status = $webhook_json['status'] ?? null;
    $amount = $webhook_json['amount'] ?? null;
    $credit = $webhook_json['credit'] ?? null;
    $sender = $webhook_json['sender'] ?? null;

    if (!$ref || !$status) {
        throw new Exception('Missing required fields: ref, status');
    }

    $db = new Database();

    // Fetch transaction by reference
    $transactionResult = $db->query(
        "SELECT * FROM transactions WHERE transref = ? AND servicename = 'Airtime 2 Cash'",
        [$ref]
    );

    if (!$transactionResult) {
        error_log("[A2C Webhook] Transaction not found for ref: {$ref}");
        throw new Exception('Transaction not found');
    }

    $transaction = $transactionResult[0];
    $user_id = $transaction['sId'];
    $old_status = $transaction['status'];

    // If transaction already completed/failed, ignore (idempotent)
    if ($old_status != 0) {
        error_log("[A2C Webhook] Transaction already processed (ref: {$ref}, status: {$old_status})");
        http_response_code(200);
        echo json_encode([
            'code' => 101,
            'status' => 'Completed',
        ]);
        exit();
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        if (strtolower($status) === 'completed') {
            // Credit user wallet with the credit amount
            $credit_amount = floatval($credit ?? $amount ?? 0);

            if ($credit_amount > 0) {
                // Get current user balance
                $userResult = $db->query("SELECT sWallet FROM subscribers WHERE sId = ?", [$user_id]);
                if (!$userResult) {
                    throw new Exception('User not found');
                }

                $current_balance = floatval($userResult[0]['sWallet'] ?? 0);
                $new_balance = $current_balance + $credit_amount;

                // Update subscriber wallet
                $db->execute(
                    "UPDATE subscribers SET sWallet = ? WHERE sId = ?",
                    [$new_balance, $user_id]
                );

                // Update transaction record - mark as completed (status = 0 means success in this system)
                $db->execute(
                    "UPDATE transactions SET status = ?, newbal = ?, api_response = ? WHERE transref = ?",
                    [
                        0, // completed/success
                        $new_balance,
                        json_encode($webhook_json),
                        $ref,
                    ]
                );

                error_log("[A2C Webhook] Transaction completed - User: {$user_id}, Amount: {$amount}, Credit: {$credit_amount}, New Balance: {$new_balance}");

                // Send success notification
                $notificationData = [
                    'title' => '💰 Airtime Conversion Successful',
                    'body' => "₦{$credit_amount} has been credited to your wallet from airtime conversion. Ref: {$ref}",
                    'type' => 'airtime2cash',
                    'status' => 'success',
                    'amount' => $credit_amount,
                    'reference' => $ref,
                ];

                // Send notification (non-blocking)
                try {
                    sendTransactionNotification($user_id, 'airtime2cash', $notificationData);
                } catch (Exception $notifError) {
                    error_log("[A2C Webhook] Notification failed: " . $notifError->getMessage());
                    // Don't fail transaction if notification fails
                }

            } else {
                throw new Exception('Invalid credit amount');
            }

        } else {
            // Transaction failed
            $db->execute(
                "UPDATE transactions SET status = ?, api_response = ? WHERE transref = ?",
                [
                    2, // failed
                    json_encode($webhook_json),
                    $ref,
                ]
            );

            error_log("[A2C Webhook] Transaction failed - User: {$user_id}, Status: {$status}");

            // Send failure notification
            $notificationData = [
                'title' => '❌ Airtime Conversion Failed',
                'body' => "Your airtime conversion request failed. Balance not deducted. Ref: {$ref}. Please try again.",
                'type' => 'airtime2cash',
                'status' => 'failed',
                'reference' => $ref,
            ];

            try {
                sendTransactionNotification($user_id, 'airtime2cash', $notificationData);
            } catch (Exception $notifError) {
                error_log("[A2C Webhook] Notification failed: " . $notifError->getMessage());
            }
        }

        // Commit transaction
        $db->commit();

        // Return required webhook response
        http_response_code(200);
        echo json_encode([
            'code' => 101,
            'status' => 'Completed',
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("[A2C Webhook] Error: " . $e->getMessage());
    
    http_response_code(200); // Return 200 to acknowledge receipt
    echo json_encode([
        'code' => 101,
        'status' => 'Completed', // Return success to prevent VTU from retrying
    ]);
}
