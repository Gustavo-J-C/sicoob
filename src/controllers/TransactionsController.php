<?php
require_once __DIR__ . '/../api/SicoobApi.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/log.php';

class TransactionsController
{
    private $sicoobApi;
    private $transactionModel;
    private $logger;

    public function __construct($apiUrl, $accessToken, $clientId)
    {
        $this->sicoobApi = new SicoobApi($apiUrl, $accessToken, $clientId);
        $this->transactionModel = new Transaction($GLOBALS['pdo']);
        $this->logger = require __DIR__ . '/../../config/log.php';
    }

    public function fetchAndSaveTransactions()
    {
        try {
            $lastUpdate = $this->transactionModel->getLastTransactionDate();
            $currentDate = date('Y-m-d\TH:i:s\Z');

            $this->logger->info("Fetching transactions from {$lastUpdate} to {$currentDate}");
            $response = $this->sicoobApi->getTransactions($lastUpdate, $currentDate);
            var_dump($response);
            
            if (isset($response['pix'])) {
                foreach ($response['pix'] as $transaction) {
                    $this->transactionModel->save($transaction);
                }
            }
        } catch (Exception $e) {
            $this->logger->critical("Failed to fetch or save transactions", ['exception' => $e]);
            throw $e;
        }
    }
}
?>
