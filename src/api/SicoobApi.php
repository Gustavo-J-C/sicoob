<?php
class SicoobApi
{
    private $apiUrl;
    private $accessToken;
    private $clientId;

    public function __construct($apiUrl, $accessToken, $clientId)
    {
        $this->apiUrl = $apiUrl;
        $this->accessToken = $accessToken;
        $this->clientId = $clientId;
    }

    public function getTransactions($inicio, $fim)
    {
        try {
            $inicio = date('Y-m-d\TH:i:s\Z', strtotime($inicio));
            $fim = date('Y-m-d\TH:i:s\Z', strtotime($fim));

            $url = $this->apiUrl . "?inicio=" . urlencode($inicio) . "&fim=" . urlencode($fim);
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $this->accessToken",
                "client_id: $this->clientId"
            ]);

            
            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            var_dump($url);
            var_dump($response);

            if ($httpCode !== 200) {
                throw new Exception("Error fetching transactions: HTTP status code $httpCode");
            }

            if ($response === false) {
                throw new Exception("Error fetching transactions: " . curl_error($ch));
            }
            
            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error fetching transactions: Invalid JSON response");
            }

            return $decodedResponse;
        } catch (Exception $e) {
            throw new Exception("Error in getTransactions: " . $e->getMessage());
        }
    }
}
?>
