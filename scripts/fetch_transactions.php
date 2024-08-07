// scripts/fetch_transactions.php
<?php
require_once __DIR__ . '/../public/index.php';

// Configurações da API SICOOB
$apiUrl = 'https://sandbox.sicoob.com.br/sicoob/sandbox/pix/api/v2/pix';
$accessToken = 'your_access_token_here';
$clientId = 'your_client_id_here';

$controller = new TransactionsController($apiUrl, $accessToken, $clientId);
$controller->fetchAndSaveTransactions();

$logFile = __DIR__ . '/../logs/transactions.log';
$logData = date('Y-m-d H:i:s') . " - Transactions fetched and saved.\n";
file_put_contents($logFile, $logData, FILE_APPEND);
