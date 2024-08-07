<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../models/Pix.php';

use GuzzleHttp\Client;

class PixController
{
    private $client;
    private $pix;
    private $base_url;

    public function __construct($pdo, $base_url)
    {
        $this->client = new Client([
            'base_uri' => $base_url,
            'headers' => [
                'client_id' => '9b5e603e428cc477a2841e2683c92d21',
                'Authorization' => '1301865f-c6bc-38f3-9f49-666dbcfc59c3',
                'Content-Type' => 'application/json',
            ]
        ]);
        $this->pix = new Pix($pdo);
    }

    public function fetchTransactions()
    {
        try {
            $inicio = urlencode(date('c', strtotime('-2 minutes')));
            $fim = urlencode(date('c'));

            $response = $this->client->get("cob?inicio=$inicio&fim=$fim");
            $transactions = json_decode($response->getBody(), true);

            foreach ($transactions['cobs'] as $transaction) {
                // Exibir a data recebida antes da conversão
                var_dump($transaction['calendario']['criacao']);

                // Converter a data para o formato aceito pelo MySQL
                $cleanedDate = preg_replace('/\.\d+Z$/', '', $transaction['calendario']['criacao']);
                $cleanedDate = str_replace('T', ' ', $cleanedDate);
                $transactionDate = DateTime::createFromFormat('Y-m-d H:i:s', $cleanedDate);

                if ($transactionDate) {
                    $formattedDate = $transactionDate->format('Y-m-d H:i:s');
                    var_dump($formattedDate); // Exibir a data formatada

                    $this->pix->saveTransaction([
                        'transaction_id' => $transaction['txid'],
                        'value' => $transaction['valor']['original'],
                        'payer_name' => $transaction['devedor']['nome'],
                        'payer_cpf_cnpj' => $transaction['devedor']['cpf'] ?? $transaction['devedor']['cnpj'],
                        'transaction_date' => $formattedDate,
                        'raw_response' => json_encode($transaction), // Convertendo a resposta bruta para JSON
                    ]);
                } else {
                    // Lidar com o erro de formatação da data aqui
                    error_log("Erro ao formatar a data: " . $transaction['calendario']['criacao']);
                    var_dump("Erro ao formatar a data: " . $transaction['calendario']['criacao']); // Exibir o erro no navegador
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar transações: " . $e->getMessage());
            var_dump("Erro ao buscar transações: " . $e->getMessage()); // Exibir o erro no navegador
        }
    }

    public function displayDashboard()
    {
        $transactions = $this->pix->getTransactions();
        require __DIR__ . '/../views/dashboard.php';
    }

    public function searchTransactions($startDate, $endDate, $cpf = null, $name = null, $value = null)
    {
        $transactions = $this->pix->searchTransactions($startDate, $endDate, $cpf, $name, $value);
        require __DIR__ . '/../views/dashboard.php';
    }

    public function createPix($data)
    {
        $response = $this->client->post('cob', [
            'json' => $data
        ]);

        return json_decode($response->getBody(), true);
    }
}
?>