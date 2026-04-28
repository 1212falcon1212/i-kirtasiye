<?php

namespace App\Services\Erp\Providers;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class KolaySoftProvider implements ErpIntegrationInterface
{
    // Live endpoint
    private const LIVE_WSDL = 'https://servis.smartdonusum.com/EArchiveInvoiceService/EArchiveInvoiceWS?wsdl';
    // Test endpoint
    private const TEST_WSDL = 'https://servis.kolayentegrasyon.net/EArchiveInvoiceService/EArchiveInvoiceWS?wsdl';

    protected UserIntegration $integration;
    protected string $wsdlUrl;
    protected string $username;
    protected string $password;
    protected bool $testMode;
    protected ?SoapClient $client = null;

    public function __construct(UserIntegration $integration)
    {
        $this->integration = $integration;

        $extras = $this->integration->extra_params ?? [];

        $this->username = $extras['username'] ?? $this->integration->api_key ?? '';
        $this->password = $extras['password'] ?? $this->integration->api_secret ?? '';
        $this->testMode = !empty($extras['test_mode']);

        // Use test or live endpoint
        $this->wsdlUrl = $this->testMode ? self::TEST_WSDL : self::LIVE_WSDL;

        // Custom WSDL URL if provided
        if (!empty($extras['wsdl_url'])) {
            $this->wsdlUrl = $extras['wsdl_url'];
        }

        Log::info('KolaySoft Provider Initialized', [
            'integration_id' => $integration->id,
            'test_mode' => $this->testMode,
            'environment' => $this->testMode ? 'TEST' : 'CANLI',
            'wsdl_url' => $this->wsdlUrl,
            'username' => $this->username,
            'has_password' => !empty($this->password),
        ]);
    }

    public function getName(): string
    {
        return 'kolaysoft';
    }

    /**
     * Get SOAP Client
     */
    protected function getClient(): SoapClient
    {
        if (!$this->client) {
            try {
                $opts = [
                    'http' => [
                        'header' => "Username: {$this->username}\r\nPassword: {$this->password}\r\n"
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ];

                $context = stream_context_create($opts);

                $this->client = new SoapClient($this->wsdlUrl, [
                    'stream_context' => $context,
                    'trace' => 1,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                ]);
            } catch (SoapFault $e) {
                Log::error('KolaySoft SOAP Connection Error: ' . $e->getMessage());
                throw $e;
            }
        }
        return $this->client;
    }

    public function testConnection(): bool
    {
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        Log::info('KolaySoft testConnection Started', [
            'environment' => $envLabel,
            'wsdl_url' => $this->wsdlUrl,
            'username' => $this->username,
        ]);

        try {
            $client = $this->getClient();

            Log::info('KolaySoft SOAP Client Created', [
                'environment' => $envLabel,
                'functions' => $client->__getFunctions() ?? 'N/A',
            ]);

            // Test with getPrefixList
            Log::info('KolaySoft Calling getPrefixList...');
            $result = $client->getPrefixList();

            // Log full response
            Log::info('KolaySoft getPrefixList Response', [
                'environment' => $envLabel,
                'raw_result' => json_encode($result),
                'last_request' => $client->__getLastRequest() ?? 'N/A',
                'last_response' => $client->__getLastResponse() ?? 'N/A',
            ]);

            if (isset($result->return->stateExplanation)) {
                $this->integration->update([
                    'status' => 'active',
                    'error_message' => null,
                ]);

                Log::info('KolaySoft Connection Success', [
                    'environment' => $envLabel,
                    'state_code' => $result->return->stateCode ?? 'N/A',
                    'state_explanation' => $result->return->stateExplanation,
                    'prefix_list' => $result->return->prefixList ?? [],
                ]);

                return true;
            }

            $this->integration->update([
                'status' => 'active',
                'error_message' => null,
            ]);

            Log::info('KolaySoft Connection OK (no stateExplanation)', [
                'environment' => $envLabel,
                'result' => json_encode($result),
            ]);

            return true;
        } catch (SoapFault $e) {
            Log::error('KolaySoft Connection Test SoapFault', [
                'environment' => $envLabel,
                'fault_code' => $e->faultcode ?? 'N/A',
                'fault_string' => $e->faultstring ?? $e->getMessage(),
                'fault_detail' => $e->detail ?? 'N/A',
            ]);
            $this->integration->update([
                'status' => 'error',
                'error_message' => $envLabel . ' SOAP Baglanti hatasi: ' . $e->getMessage(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('KolaySoft Connection Test Exception', [
                'environment' => $envLabel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->integration->update([
                'status' => 'error',
                'error_message' => $envLabel . ' Baglanti hatasi: ' . $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * KolaySoft is for E-Invoice only, not product sync
     */
    public function getProducts(int $page = 1, int $limit = 100): array
    {
        // KolaySoft doesn't support product sync
        return [];
    }

    public function mapToSystemSchema(array $erpProduct): array
    {
        // Not applicable for KolaySoft
        return [];
    }

    /**
     * Create E-Invoice via KolaySoft
     */
    public function createInvoice(array $invoiceData): array
    {
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        try {
            $uuid = $invoiceData['uuid'] ?? \Illuminate\Support\Str::uuid()->toString();
            $invoiceNo = $invoiceData['invoice_no'] ?? '';
            $prefix = $invoiceData['document_no_prefix'] ?? ('DNM' . date('Y'));

            Log::info('KolaySoft createInvoice Started', [
                'environment' => $envLabel,
                'uuid' => $uuid,
                'invoice_no' => $invoiceNo,
                'prefix' => $prefix,
                'invoice_data' => $invoiceData,
            ]);

            // Generate UBL XML (simplified - in production use UBL generator)
            $xmlContent = $this->generateSimpleUBL($invoiceData);

            Log::info('KolaySoft UBL XML Generated', [
                'environment' => $envLabel,
                'xml_content' => $xmlContent,
                'xml_length' => strlen($xmlContent),
            ]);

            // SOAP Envelope
            $soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://earchiveinvoiceservice.entegrator.com/">
<soap:Body>
<ns1:sendInvoice>
<invoiceXMLList>
<documentUUID>' . $uuid . '</documentUUID>
<documentId>' . $invoiceNo . '</documentId>
<xmlContent><![CDATA[' . $xmlContent . ']]></xmlContent>
<sourceUrn>' . ($invoiceData['source_urn'] ?? 'urn:mail:defaultpk') . '</sourceUrn>
<destinationUrn>' . ($invoiceData['destination_urn'] ?? 'urn:mail:defaultgb') . '</destinationUrn>
<documentDate>' . ($invoiceData['issue_date'] ?? date('Y-m-d')) . '</documentDate>
<submitForApproval>' . (($invoiceData['submit_for_approval'] ?? true) ? 'true' : 'false') . '</submitForApproval>
<documentNoPrefix>' . $prefix . '</documentNoPrefix>
</invoiceXMLList>
</ns1:sendInvoice>
</soap:Body>
</soap:Envelope>';

            $response = $this->sendSoapRequest($soapEnvelope);

            // Parse response
            $code = null;
            $explanation = null;

            if (preg_match('/<code>([^<]*)<\/code>/', $response['body'], $m)) $code = $m[1];
            if (preg_match('/<explanation>([^<]*)<\/explanation>/', $response['body'], $m)) $explanation = $m[1];

            Log::info('KolaySoft createInvoice Response Parsed', [
                'environment' => $envLabel,
                'http_code' => $response['http_code'],
                'response_code' => $code,
                'response_explanation' => $explanation,
            ]);

            if (preg_match('/<faultstring>([^<]*)<\/faultstring>/', $response['body'], $m)) {
                Log::error('KolaySoft createInvoice SOAP Fault', [
                    'environment' => $envLabel,
                    'fault_string' => $m[1],
                    'uuid' => $uuid,
                ]);
                return [
                    'success' => false,
                    'message' => 'SOAP Fault: ' . $m[1],
                    'invoice_id' => $uuid,
                ];
            }

            $successCodes = ['000', '200', 'SUCCESS', '0'];
            if (in_array($code, $successCodes)) {
                Log::info('KolaySoft createInvoice Success', [
                    'environment' => $envLabel,
                    'uuid' => $uuid,
                    'invoice_no' => $invoiceNo,
                    'code' => $code,
                    'explanation' => $explanation,
                ]);
                return [
                    'success' => true,
                    'message' => $explanation ?? 'Fatura basariyla gonderildi.',
                    'invoice_id' => $uuid,
                    'invoice_no' => $invoiceNo,
                ];
            }

            Log::warning('KolaySoft createInvoice Failed', [
                'environment' => $envLabel,
                'uuid' => $uuid,
                'code' => $code,
                'explanation' => $explanation,
            ]);

            return [
                'success' => false,
                'message' => 'Fatura gonderimi basarisiz: ' . ($explanation ?? 'Bilinmeyen Hata') . ' (Kod: ' . $code . ')',
                'invoice_id' => $uuid,
            ];
        } catch (\Exception $e) {
            Log::error('KolaySoft createInvoice Exception', [
                'environment' => $envLabel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Sistem Hatasi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send SOAP request via CURL
     */
    protected function sendSoapRequest(string $soapXml): array
    {
        $endpoint = str_replace('?wsdl', '', $this->wsdlUrl);
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        Log::info('KolaySoft SOAP Request', [
            'environment' => $envLabel,
            'endpoint' => $endpoint,
            'username' => $this->username,
            'request_xml' => $soapXml,
            'request_length' => strlen($soapXml),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapXml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ""',
                'Username: ' . $this->username,
                'Password: ' . $this->password,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlInfo = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        Log::info('KolaySoft SOAP Response', [
            'environment' => $envLabel,
            'http_code' => $httpCode,
            'response_body' => $response,
            'response_length' => strlen($response ?? ''),
            'curl_error' => $error ?: null,
            'total_time' => $curlInfo['total_time'] ?? null,
            'connect_time' => $curlInfo['connect_time'] ?? null,
        ]);

        if ($error) {
            Log::error('KolaySoft CURL Error', [
                'environment' => $envLabel,
                'error' => $error,
                'curl_info' => $curlInfo,
            ]);
            throw new \Exception('CURL Error: ' . $error);
        }

        return [
            'http_code' => $httpCode,
            'body' => $response
        ];
    }

    /**
     * Generate simple UBL XML (placeholder - use proper UBL generator in production)
     */
    protected function generateSimpleUBL(array $data): string
    {
        // This is a simplified placeholder
        // In production, use the KolaySoftUBLGenerator class
        return '<?xml version="1.0" encoding="UTF-8"?><Invoice></Invoice>';
    }

    /**
     * Get prefix list
     */
    public function getPrefixList(): array
    {
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        Log::info('KolaySoft getPrefixList Started', [
            'environment' => $envLabel,
        ]);

        try {
            $client = $this->getClient();
            $result = $client->getPrefixList();

            Log::info('KolaySoft getPrefixList Response', [
                'environment' => $envLabel,
                'raw_result' => json_encode($result),
                'last_request' => $client->__getLastRequest() ?? 'N/A',
                'last_response' => $client->__getLastResponse() ?? 'N/A',
            ]);

            return [
                'success' => true,
                'message' => $result->return->stateExplanation ?? 'Basarili',
                'data' => $result->return ?? null
            ];
        } catch (\Exception $e) {
            Log::error('KolaySoft getPrefixList Error', [
                'environment' => $envLabel,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Prefix listesi alinamadi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get customer credit count
     */
    public function getCreditCount(): array
    {
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        Log::info('KolaySoft getCreditCount Started', [
            'environment' => $envLabel,
        ]);

        try {
            $client = $this->getClient();
            $result = $client->getCustomerCreditCount();

            Log::info('KolaySoft getCreditCount Response', [
                'environment' => $envLabel,
                'raw_result' => json_encode($result),
                'credit_count' => $result->return ?? 0,
                'last_request' => $client->__getLastRequest() ?? 'N/A',
                'last_response' => $client->__getLastResponse() ?? 'N/A',
            ]);

            return [
                'success' => true,
                'credit_count' => $result->return ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('KolaySoft getCreditCount Error', [
                'environment' => $envLabel,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'credit_count' => 0,
                'message' => 'Kredi sayisi alinamadi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync order from system to KolaySoft as E-Invoice
     */
    public function syncOrder($order): array
    {
        $envLabel = $this->testMode ? '[TEST]' : '[CANLI]';

        Log::info('KolaySoft syncOrder Started', [
            'environment' => $envLabel,
            'order_id' => $order->id ?? 'N/A',
            'order_code' => $order->order_code ?? 'N/A',
        ]);

        try {
            $customer = $order->customer ?? null;
            $user = $customer->user ?? null;
            $address = $order->billing_address ?? $order->invoiceAddress ?? $order->address ?? null;

            Log::info('KolaySoft syncOrder - Order Details', [
                'environment' => $envLabel,
                'has_customer' => !is_null($customer),
                'has_user' => !is_null($user),
                'has_address' => !is_null($address),
                'customer_tax_number' => $customer->tax_number ?? 'N/A',
                'user_name' => $user->name ?? 'N/A',
                'products_count' => count($order->products ?? []),
            ]);

            // Build invoice lines
            $lines = [];
            $totalAmount = 0;
            $totalVat = 0;

            foreach ($order->products as $product) {
                $quantity = $product->pivot->quantity ?? 1;
                $unitPrice = $product->pivot->price ?? $product->price ?? 0;
                $vatRate = $product->vat_tax->rate ?? $product->vat_rate ?? 18;
                $lineTotal = $quantity * $unitPrice;
                $vatAmount = $lineTotal * ($vatRate / 100);

                $lines[] = [
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'line_total' => $lineTotal,
                ];

                $totalAmount += $lineTotal;
                $totalVat += $vatAmount;
            }

            // Build invoice data
            $invoiceData = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'invoice_no' => $order->prefix . $order->order_code,
                'issue_date' => $order->created_at->format('Y-m-d'),
                'issue_time' => $order->created_at->format('H:i:s'),
                'document_no_prefix' => 'B2B' . date('Y'),

                // Customer info
                'customer_name' => $user->name ?? 'Musteri',
                'customer_tax_number' => $customer->tax_number ?? '',
                'customer_tax_office' => $customer->tax_office ?? '',
                'customer_address' => $address->address ?? $address->address_line ?? '',
                'customer_city' => $address->city ?? $address->province ?? '',
                'customer_district' => $address->district ?? '',
                'customer_phone' => $address->phone ?? $user->phone ?? '',
                'customer_email' => $user->email ?? '',

                // Totals
                'total_amount' => $totalAmount,
                'total_vat' => $totalVat,
                'payable_amount' => $totalAmount + $totalVat,

                // Lines
                'lines' => $lines,

                'submit_for_approval' => true,
            ];

            Log::info('KolaySoft syncOrder - Invoice Data Built', [
                'environment' => $envLabel,
                'order_id' => $order->id ?? 'N/A',
                'order_code' => $order->order_code ?? 'N/A',
                'invoice_uuid' => $invoiceData['uuid'],
                'invoice_no' => $invoiceData['invoice_no'],
                'total_amount' => $totalAmount,
                'total_vat' => $totalVat,
                'payable_amount' => $invoiceData['payable_amount'],
                'lines_count' => count($lines),
                'lines' => $lines,
                'customer_name' => $invoiceData['customer_name'],
                'customer_tax_number' => $invoiceData['customer_tax_number'],
            ]);

            return $this->createInvoice($invoiceData);
        } catch (\Exception $e) {
            Log::error('KolaySoft syncOrder Exception', [
                'environment' => $envLabel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'E-Fatura olusturma hatasi: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
