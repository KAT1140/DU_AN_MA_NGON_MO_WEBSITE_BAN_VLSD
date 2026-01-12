<?php
require_once 'config.php';
require_once 'momo_config.php';

/**
 * MoMo Payment Handler
 * Xử lý tích hợp thanh toán MoMo QR Code
 */
class MoMoPaymentHandler {
    private $conn;
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->partnerCode = MOMO_PARTNER_CODE;
        $this->accessKey = MOMO_ACCESS_KEY;
        $this->secretKey = MOMO_SECRET_KEY;
        $this->endpoint = MOMO_ENDPOINT;
        
        // Validate configuration
        validateMoMoConfig();
    }
    
    /**
     * Tạo thanh toán MoMo
     * @param int $orderId ID đơn hàng
     * @param float $amount Số tiền
     * @param string $orderInfo Thông tin đơn hàng
     * @return array Kết quả từ MoMo API
     */
    public function createPayment($orderId, $amount, $orderInfo = null) {
        try {
            // Tạo các ID duy nhất
            $momoOrderId = generateMoMoOrderId();
            $requestId = generateMoMoRequestId();
            
            // Thông tin đơn hàng
            if (empty($orderInfo)) {
                $orderInfo = MOMO_ORDER_INFO . " - Đơn hàng #" . $orderId;
            }
            
            // Tạo request data
            $requestData = [
                'partnerCode' => $this->partnerCode,
                'partnerName' => 'VLXD Store',
                'storeId' => 'VLXD_STORE',
                'requestId' => $requestId,
                'amount' => (int)$amount,
                'orderId' => $momoOrderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => getMoMoReturnUrl(),
                'ipnUrl' => getMoMoCallbackUrl(),
                'lang' => 'vi',
                'extraData' => MOMO_EXTRA_DATA,
                'requestType' => MOMO_REQUEST_TYPE,
                'signature' => ''
            ];
            
            // Log request data for debugging
            error_log("MoMo Request Data: " . json_encode($requestData));
            
            // Tạo chữ ký
            $requestData['signature'] = $this->generateSignature($requestData);
            
            // Lưu giao dịch vào database
            $this->saveTransaction($orderId, $momoOrderId, $requestId, $requestData);
            
            // Gửi request đến MoMo
            $response = $this->sendRequest($requestData);
            
            // Log response for debugging
            error_log("MoMo Response: " . json_encode($response));
            
            // Cập nhật response vào database
            $this->updateTransactionResponse($requestId, $response);
            
            return [
                'success' => true,
                'data' => $response,
                'momo_order_id' => $momoOrderId,
                'request_id' => $requestId
            ];
            
        } catch (Exception $e) {
            error_log("MoMo Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tạo chữ ký cho request MoMo
     * @param array $data Dữ liệu request
     * @return string Chữ ký
     */
    public function generateSignature($data) {
        // Tạo raw signature theo thứ tự alphabet
        $rawSignature = "accessKey=" . $this->accessKey .
                       "&amount=" . $data['amount'] .
                       "&extraData=" . $data['extraData'] .
                       "&ipnUrl=" . $data['ipnUrl'] .
                       "&orderId=" . $data['orderId'] .
                       "&orderInfo=" . $data['orderInfo'] .
                       "&partnerCode=" . $data['partnerCode'] .
                       "&redirectUrl=" . $data['redirectUrl'] .
                       "&requestId=" . $data['requestId'] .
                       "&requestType=" . $data['requestType'];
        
        // Tạo signature bằng HMAC SHA256
        return hash_hmac('sha256', $rawSignature, $this->secretKey);
    }
    
    /**
     * Xác thực chữ ký từ MoMo callback
     * @param array $data Dữ liệu từ callback
     * @return bool True nếu chữ ký hợp lệ
     */
    public function verifySignature($data) {
        $signature = $data['signature'] ?? '';
        unset($data['signature']);
        
        // Sắp xếp theo alphabet và tạo raw signature
        ksort($data);
        $rawSignature = '';
        foreach ($data as $key => $value) {
            if ($rawSignature !== '') {
                $rawSignature .= '&';
            }
            $rawSignature .= $key . '=' . $value;
        }
        
        // Tạo signature để so sánh
        $expectedSignature = hash_hmac('sha256', $rawSignature, $this->secretKey);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Gửi request đến MoMo API
     * @param array $data Dữ liệu request
     * @return array Response từ MoMo
     */
    private function sendRequest($data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false // Chỉ dùng cho test, production nên bật
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from MoMo");
        }
        
        return $responseData;
    }
    
    /**
     * Lưu giao dịch vào database
     */
    private function saveTransaction($orderId, $momoOrderId, $requestId, $requestData) {
        // Sử dụng timestamp hiện tại + timeout minutes
        $expiresAt = date('Y-m-d H:i:s', time() + (MOMO_TIMEOUT_MINUTES * 60));
        
        $sql = "INSERT INTO momo_transactions (
                    order_id, momo_order_id, momo_request_id, partner_code, 
                    amount, order_info, redirect_url, ipn_url, request_type, 
                    signature, status, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $status = MOMO_STATUS_PENDING;
        
        $stmt->bind_param('isssdsssssss',
            $orderId, $momoOrderId, $requestId, $requestData['partnerCode'],
            $requestData['amount'], $requestData['orderInfo'], 
            $requestData['redirectUrl'], $requestData['ipnUrl'],
            $requestData['requestType'], $requestData['signature'],
            $status, $expiresAt
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save MoMo transaction: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    /**
     * Cập nhật response từ MoMo vào database
     */
    private function updateTransactionResponse($requestId, $response) {
        $sql = "UPDATE momo_transactions SET 
                result_code = ?, message = ?, pay_type = ?, 
                qr_code_url = ?, pay_url = ?, deeplink = ?,
                callback_data = ? 
                WHERE momo_request_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $callbackData = json_encode($response);
        $resultCode = $response['resultCode'] ?? null;
        $message = $response['message'] ?? null;
        $payType = $response['payType'] ?? null;
        $qrCodeUrl = $response['qrCodeUrl'] ?? null;
        $payUrl = $response['payUrl'] ?? null;
        $deeplink = $response['deeplink'] ?? null;
        
        $stmt->bind_param('isssssss',
            $resultCode,
            $message,
            $payType,
            $qrCodeUrl,
            $payUrl,
            $deeplink,
            $callbackData,
            $requestId
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Lấy thông tin giao dịch theo request ID
     */
    public function getTransactionByRequestId($requestId) {
        $sql = "SELECT * FROM momo_transactions WHERE momo_request_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    }
    
    /**
     * Cập nhật trạng thái giao dịch
     */
    public function updateTransactionStatus($requestId, $status, $momoTransId = null, $additionalData = []) {
        $sql = "UPDATE momo_transactions SET 
                status = ?, momo_trans_id = ?, momo_response_time = ?, 
                result_code = ?, local_message = ?, momo_signature = ?
                WHERE momo_request_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $responseTime = time();
        
        $stmt->bind_param('ssissss',
            $status, $momoTransId, $responseTime,
            $additionalData['resultCode'] ?? null,
            $additionalData['localMessage'] ?? null,
            $additionalData['signature'] ?? null,
            $requestId
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Kiểm tra giao dịch đã timeout chưa
     */
    public function checkTimeout($requestId) {
        $sql = "SELECT expires_at, status FROM momo_transactions WHERE momo_request_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        if (!$transaction) {
            return false;
        }
        
        $now = new DateTime();
        $expiresAt = new DateTime($transaction['expires_at']);
        
        if ($now > $expiresAt && $transaction['status'] === MOMO_STATUS_PENDING) {
            $this->updateTransactionStatus($requestId, MOMO_STATUS_TIMEOUT);
            return true;
        }
        
        return false;
    }
}
?>