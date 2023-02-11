<?php

class MpesaPay
{
	public $environment;
	public $mpesa_gateway;
	public $server_protocal;
	public $payment;

	private $mpesaResponse;
	private $transactionId;
	private $port;
	private $consumer_key;
	private $consumer_secret;
	private $access_token_url;
	private $access_token;
	private $register_url;
	private $curl_response;
	private $data; 
	private $ResponseType;
	private $stkPush_Request_url;
	private $curl;
	private $headers;
	private $BusinessShortCode;
	private $Password;
	private $PassKey;
	private $Timestamp;
	private $TransactionType;
	private $Amount;
	private $PartyA;
	private $PartyB;
	private $PhoneNumber;
	private $CallBackURL;
	private $validationUrl;
	private $AccountReference;
	private $BillRefNumber;
	private $TransactionDesc;
	private $CommandID;
	private $IdentifierType;
	private $Remarks;
	private $Initiator;
	private $SecurityCredential;
	private $QueueTimeOutURL;
	private $ResultURL;
	private $Occasion;

	private $residentialAddress;
	private $residentialCity;
	private $residentialState;
	private $zipcode;

	private $stkPush_transaction_status_Request_url;
	private $CheckoutRequestID;

	public function __construct($environment, $consumer_key, $consumer_secret, $PassKey)
	{

		$this->environment = $environment;
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->PassKey = $PassKey;

		$this->payment = null;
		$this->Timestamp = date('Ymdhis');

		if (isset($this->environment)) {
			if ($this->environment == 'sandbox') {
				$this->mpesa_gateway = 'sandbox';
			}
			else if ($this->environment == 'live') {
				$this->mpesa_gateway = 'api';
			}
			else {
				$this->mpesa_gateway = 'sandbox';
			}
		}
		else {
			$this->mpesa_gateway = 'sandbox';
		}

		if (isset($_SERVER['HTTPS'])) {
			if ($_SERVER['HTTPS'] == 'on') {
				$this->server_protocal = "https://";
			} else {
				$this->server_protocal = "http://";
			}
		} else {
			$this->server_protocal = "https://";
		} 

		$this->transactionId = substr(md5(uniqid()), -5);
	}

	/**********************************
	 * We pass the business api key credentials.
	 * 			This is where we generate the token for the lipa na mpesa api.
	 * 			Lipa na mpesa functionality is initiated by this token.
	 * 			A successful request returns a valid token where a bad request returns a bad response.
	 *****/
	public function generate_access_token()
	{
		$this->headers = ['Content-Type:application/json; charset=utf8'];

		$this->access_token_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

		$this->curl = curl_init($this->access_token_url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->curl, CURLOPT_HEADER, FALSE);
		curl_setopt($this->curl, CURLOPT_USERPWD, $this->consumer_key . ':' . $this->consumer_secret);
		$result = curl_exec($this->curl);
		$status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		$result = json_decode($result);
		$this->access_token = $result->access_token;

		curl_close($this->curl);

		return $this->access_token;
	}

	/***********************************
	 * What happens here:
	 * 			Registering our url.
	 * 			The url helps in validation of our lipa na mpesa api request.
	 * 			By default, Response Type allows Mpesa Request cancellation. 
	 *****/
	public function register_callback_url($BusinessShortCode, $CallBackURL, $ValidationURL, $ResponseType = 'Cancelled')
	{
		$this->register_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/mpesa/c2b/v2/registerurl'; // check the mpesa_accesstoken.php file for this. No need to writing a new file here, just combine the code as in the tutorial.
		
		$this->CallBackURL = $this->server_protocal . $_SERVER['SERVER_NAME'] . DIRECTORY_SEPARATOR . $CallBackURL;
		
		$this->validationUrl = $this->CallBackURL;
		$this->BusinessShortCode = $BusinessShortCode;
		$this->ResponseType = $ResponseType;

		$this->access_token = $this->generate_access_token();

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->register_url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->access_token)); //setting custom header


		$curl_post_data = array(
			'ShortCode' => $this->BusinessShortCode,
			'ResponseType' => $this->ResponseType,
			'ConfirmationURL' => $this->CallBackURL,
			'ValidationURL' => $this->validationUrl
		);

		$data_string = json_encode($curl_post_data);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

		$this->curl_response = curl_exec($curl);
		
		return $this->curl_response;
	}

	/***********
	 * C2B STKPush Request.
	 * Params cannot be null!
	 */
	public function c2b_stk_push_request($BillRefNumber = null, $TransactionType, $Amount, $PhoneNumber, $BusinessShortCode)
	{
		$this->BillRefNumber = $BillRefNumber;
		$this->TransactionType = $TransactionType;
		$this->Amount = $Amount;
		$this->PhoneNumber = $PhoneNumber;
		$this->BusinessShortCode = $BusinessShortCode;

		$this->access_token = $this->generate_access_token();

		$this->curl = curl_init();
		$this->stkPush_Request_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/mpesa/c2b/v1/simulate';
		curl_setopt($this->curl, CURLOPT_URL, $this->stkPush_Request_url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->access_token)); //setting custom header

		$data = array(
			'ShortCode' => $this->BusinessShortCode,
			'CommandID' => $this->TransactionType,
			'Amount' => $this->Amount,
			'Msisdn' => $this->PhoneNumber, //the phone number sending the funds i.e customer in session
			'BillRefNumber' =>  $this->BillRefNumber //can be generated according to your preference, i.e cart 001 for each user cart 001++
		);

		$encoded_data = json_encode($data);

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_data);

		$this->curl_response = curl_exec($this->curl);

		return $this->curl_response;
	}

	/*******
	 * STKPushRequest.
	 * 
	 * This function initiates stk push on the customer's device for 
	 * transaction status checks.
	 * 
	 *  Parameters Description;
	 * -------------------------------------------------------------
	 * CommandID = Takes only 'TransactionStatusQuery' command id.
	 * 
	 * IndentifierType = 1 for MSISDN, 2 for Till Number, 4 for Organization short code.
	 * 
	 * PartyA = Shortcode (6 digits) MSISDN (12 Digits).
	 * 
	 * Remarks = Comments that are sent along with the transaction	- upto a hundred characters.
	 * 
	 * Initiator = This is the credential/username used to authenticate the transaction request.
	 * 
	 * SecurityCredential = Encrypted password for the initiator to authenticate the transaction request.
	 * 
	 * QueueTimeOutURL = The path that stores information of time out transaction - https://ip or domain:port/path.
	 * 
	 * ResultURL = The path that stores information of transaction - https://ip or domain:port/path.
	 * 
	 * transactionId = Unique identifier to identify a transaction on M-Pesa.
	 * 
	 * Occasion = sequence of characters up to 100. Optional parameter.
	 * 
	 * @return curl_response
	 */
	public function check_transaction_status(
		$CommandID, 
		$PartyA, 
		$IdentifierType, 
		$Remarks, 
		$Initiator,  
		$QueueTimeOutURL, 
		$ResultURL, 
		$transactionId, 
		$Occasion = null,
		$SecurityCredential = null
	) { 
		$this->transactionId = $transactionId ? $transactionId : $this->transactionId;
		$this->access_token = $this->generate_access_token();

		$this->curl = curl_init();
		$this->stkPush_Request_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/mpesa/transactionstatus/v1/query';
		
		curl_setopt($this->curl, CURLOPT_URL, $this->stkPush_Request_url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->access_token)); //setting custom header

		$this->SecurityCredential = $SecurityCredential 
			? $SecurityCredential 
			: base64_encode($this->BusinessShortCode . $this->PassKey . $this->Timestamp);
		
		$this->QueueTimeOutURL = $this->server_protocal . $_SERVER['SERVER_NAME'] . DIRECTORY_SEPARATOR . $QueueTimeOutURL;
		$this->ResultURL = $this->server_protocal . $_SERVER['SERVER_NAME'] . DIRECTORY_SEPARATOR . $ResultURL;
		$this->AccountReference = $this->transactionId;
		
		$data = array(
			'CommandID' => $this->CommandID,
			'PartyA' => $this->PartyA,
			'IdentifierType' => $this->IdentifierType, // 1 – MSISDN 2 – Till Number 4 – Organization short code
			'Remarks' => $this->Remarks,
			'Initiator' => $this->Initiator,
			'SecurityCredential' => $this->SecurityCredential, 
			'QueueTimeOutURL' => $this->QueueTimeOutURL, 
			'ResultURL' => $this->ResultURL,
			'TransactionID' =>  $this->AccountReference,
			'Occasion' =>  null
		);

		$encoded_data = json_encode($data);

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_data);

		$this->curl_response = curl_exec($this->curl);

		return $this->curl_response;
	}

	/*******
	 * STKPushRequest.
	 * This function initiates stk push on the customer's device for
	 * lipa bill online.
	 */
	public function lipa_bill_online_stk_push_request()
	{ 
		$this->access_token = $this->generate_access_token();

		$this->curl = curl_init();
		$this->stkPush_Request_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
		curl_setopt($this->curl, CURLOPT_URL, $this->stkPush_Request_url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->access_token)); //setting custom header

		$this->AccountReference = $this->transactionId;

		$data = array(
			'BusinessShortCode' => $this->BusinessShortCode,
			'Password' => base64_encode($this->BusinessShortCode . $this->PassKey . $this->Timestamp),
			'Timestamp' => $this->Timestamp,
			'TransactionType' => $this->TransactionType,
			'Amount' => $this->Amount,
			'PartyA' => $this->PartyA, //This is the organization/phone number sending the money.
			'PartyB' => $this->PartyB, //This is the customer mobile number/short code  to receive the amount. - The number should have the country code (254) without the plus sign.
			'PhoneNumber' => $this->PhoneNumber, //the phone number sending the funds i.e customer in session
			'CallBackURL' =>  $this->CallBackURL,
			'AccountReference' =>  $this->AccountReference, //can be generated according to your preference, i.e cart 001 for each user cart 001++
			'TransactionDesc' => $this->TransactionDesc
		);

		$encoded_data = json_encode($data);

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_data);

		$this->curl_response = curl_exec($this->curl);

		return $this->curl_response;
	}

	/******
	 * We check the status of the lipa bill online transaction
	 * We therefore return the response.
	 */
	public function lipa_bill_online_transaction_status_check($BusinessShortCode = null, $CheckoutRequestID = null)
	{
		$this->BusinessShortCode = isset($this->BusinessShortCode) ? $this->BusinessShortCode : $BusinessShortCode;
		$this->CheckoutRequestID = isset($this->CheckoutRequestID) ? $this->CheckoutRequestID : $CheckoutRequestID;

		$this->curl = curl_init();
		$this->stkPush_transaction_status_Request_url = 'https://' . $this->mpesa_gateway . '.safaricom.co.ke/mpesa/stkpushquery/v1/query';
		curl_setopt($this->curl, CURLOPT_URL, $this->stkPush_transaction_status_Request_url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $this->access_token)); //setting custom header

		$data = array(
			'BusinessShortCode' => $this->BusinessShortCode,
			'Password' => base64_encode($this->BusinessShortCode . $this->PassKey . $this->Timestamp),
			'Timestamp' => $this->Timestamp,
			'CheckoutRequestID' => $this->CheckoutRequestID
		);

		$encoded_data = json_encode($data);

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_data);

		$this->curl_response = curl_exec($this->curl);

		return $this->curl_response;
	}

	/*************************************
	 * Lipa Na M-Pesa Payment functionality is done here.
	 * procedures done here are:
	 *          1) We get the generated token,@generate_access_token() 
	 *          2) we validate the request, @payment_callback_response() 
	 *          3) we confirm the response,@payment_callback_response() 
	 * 
	 * 			By default, ShortCodeType is Paybill.
	 *******/
	public function lipa_bill_online($BusinessShortCode, $PartyA, $PartyB, $PhoneNumber, $ProductName = null, $Amount, $transactionId, $ShortCodeType = "paybill", $transaction_description = null, $CallBackURL, $ValidationURL)
	{
		$this->CallBackURL = $this->server_protocal . $_SERVER['SERVER_NAME'] . DIRECTORY_SEPARATOR . $CallBackURL;
		$this->validationUrl = $ValidationURL;
		$this->BusinessShortCode = $BusinessShortCode;
		$this->ProductName = $ProductName;
		$this->PartyA = $PartyA;
		$this->PartyB = $PartyB;
		$this->PhoneNumber = $PhoneNumber;
		$this->Amount =  $Amount;
		$this->TransactionType = ($ShortCodeType == "tillNumber") ? "CustomerBuyGoodsOnline" : "CustomerPayBillOnline";
		$this->TransactionDesc = ($transaction_description) ? $transaction_description : "Lipa Bill Online. Request Initiated by the Merchant";
		$this->transactionId = $transactionId ? $transactionId : $this->transactionId;

		if ($this->PhoneNumber == null) {
			return "Null Recipient!";
		} else {
			$this->curl_response = $this->lipa_bill_online_stk_push_request();

			$payment_response_object = json_decode($this->curl_response);

			if (isset($payment_response_object->ResponseCode)) {
				if ($payment_response_object->ResponseCode == 0) {
					$this->CheckoutRequestID = isset($payment_response_object->CheckoutRequestID) ? $payment_response_object->CheckoutRequestID : null;

					sleep(30);
					if (isset($payment_response_object->CheckoutRequestID)) {
						$transaction_status = json_decode($this->lipa_bill_online_transaction_status_check($BusinessShortCode = null, $Timestamp = null, $CheckoutRequestID = null));
						
						if (isset($transaction_status->errorMessage)) {
							return array(
								'error' => $transaction_status->errorMessage,
								'CheckoutRequestId' => $this->CheckoutRequestID
							);
						} else {
							return array(
								'message' => $transaction_status->ResultDesc,
								'CheckoutRequestId' => $this->CheckoutRequestID,
								'MerchantRequestID' => $transaction_status->MerchantRequestID,
								"ResultCode" => $transaction_status->ResultCode
							);
						}
					} else {
						return array(
							'error' => $payment_response_object->errorMessage,
							'CheckoutRequestId' => $this->CheckoutRequestID
						);
					}
				} else {
					return array(
						'error' => $payment_response_object->errorMessage,
						'CheckoutRequestId' => $this->CheckoutRequestID
					);
				}
			} else {
				return array(
					'error' => $payment_response_object->errorMessage,
					'CheckoutRequestId' => $this->CheckoutRequestID
				);
			}
		}
	}
}
