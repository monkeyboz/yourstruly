<?php
	/********************************************
	PayPal API Module
	 
	Defines all the global variables and the wrapper functions 
	********************************************/
	class paypalFunction{
		var $PROXY_HOST = '127.0.0.1';
		var $PROXY_PORT = '808';

		var $SandboxFlag = true;
		//' TODO:
		//'------------------------------------
		//' PayPal API Credentials
		//' Replace <API_USERNAME> with your API Username
		//' Replace <API_PASSWORD> with your API Password
		//' Replace <API_SIGNATURE> with your Signature
		//'------------------------------------
		var $API_UserName="monkey_1352330853_biz_api1.gmail.com";
		var $API_Password="1352330872";
		var $API_Signature="ADnYvj5cNUn1a98m7-AW18wLFMfKAbdbZkZ-vxbdV3ifq1NwIaUrAm9n ";

		// BN Code 	is only applicable for partners
		var $sBNCode = "PP-ECWizard";
		
		
		/*	
		' Define the PayPal Redirect URLs.  
		' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
		' 	change the URL depending if you are testing on the sandbox or the live PayPal site
		'
		' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
		' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
		*/
		var $API_Endpoint;
		var $PAYPAL_URL;
		var $PAYPAL_DG_URL;
		var $USE_PROXY = false;
		var $version = "84";
		
		function __construct(){
			if ($this->SandboxFlag == true) 
			{
				$this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
				$this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
				$this->PAYPAL_DG_URL = "https://www.sandbox.paypal.com/incontext?token=";
			}
			else
			{
				$this->API_Endpoint = "https://api-3t.paypal.com/nvp";
				$this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
				$this->PAYPAL_DG_URL = "https://www.paypal.com/incontext?token=";
			}
		}

		/* An express checkout transaction starts with a token, that
		   identifies to PayPal your transaction
		   In this example, when the script sees a token, the script
		   knows that the buyer has already authorized payment through
		   paypal.  If no token was found, the action is to send the buyer
		   to PayPal to first authorize payment
		   */

		/*   
		'-------------------------------------------------------------------------------------------------------------------------------------------
		' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call for a Digital Goods payment.
		' Inputs:  
		'		paymentAmount:  	Total value of the shopping cart
		'		currencyCodeType: 	Currency code value the PayPal API
		'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
		'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
		'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
		'--------------------------------------------------------------------------------------------------------------------------------------------	
		*/
		public function SetExpressCheckoutDG( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, 
											$cancelURL, $items) 
		{
			//------------------------------------------------------------------------------------------------------------------------------------
			// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
			
			$nvpstr = "&PAYMENTREQUEST_0_AMT=". $paymentAmount;
			$nvpstr .= "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
			$nvpstr .= "&RETURNURL=" . $returnURL;
			$nvpstr .= "&CANCELURL=" . $cancelURL;
			$nvpstr .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
			$nvpstr .= "&REQCONFIRMSHIPPING=0";
			$nvpstr .= "&NOSHIPPING=1";

			foreach($items as $index => $item) {
			
				$nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $index . "=" . urlencode($item["name"]);
				$nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $index . "=" . urlencode($item["amt"]);
				$nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $index . "=" . urlencode($item["qty"]);
				$nvpstr .= "&L_PAYMENTREQUEST_0_ITEMCATEGORY" . $index . "=Digital";
			}
			
			
			//'--------------------------------------------------------------------------------------------------------------- 
			//' Make the API call to PayPal
			//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
			//' If an error occured, show the resulting errors
			//'---------------------------------------------------------------------------------------------------------------
			$resArray = $this->hash_call("SetExpressCheckout", $nvpstr);
			$ack = strtoupper($resArray["ACK"]);
			if($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
			{
				$token = urldecode($resArray["TOKEN"]);
				$_SESSION['TOKEN'] = $token;
			}
			   
			return $resArray;
		}
		
		/*
		'-------------------------------------------------------------------------------------------
		' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
		'
		' Inputs:  
		'		None
		' Returns: 
		'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
		'-------------------------------------------------------------------------------------------
		*/
		public function GetExpressCheckoutDetails( $token )
		{
			//'--------------------------------------------------------------
			//' At this point, the buyer has completed authorizing the payment
			//' at PayPal.  The function will call PayPal to obtain the details
			//' of the authorization, incuding any shipping information of the
			//' buyer.  Remember, the authorization is not a completed transaction
			//' at this state - the buyer still needs an additional step to finalize
			//' the transaction
			//'--------------------------------------------------------------
		   
			//'---------------------------------------------------------------------------
			//' Build a second API request to PayPal, using the token as the
			//'  ID to get the details on the payment authorization
			//'---------------------------------------------------------------------------
			$nvpstr="&TOKEN=" . $token;

			//'---------------------------------------------------------------------------
			//' Make the API call and store the results in an array.  
			//'	If the call was a success, show the authorization details, and provide
			//' 	an action to complete the payment.  
			//'	If failed, show the error
			//'---------------------------------------------------------------------------
			$resArray=$this->hash_call("GetExpressCheckoutDetails",$nvpstr);
			$ack = strtoupper($resArray["ACK"]);
			if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
			{	
				return $resArray;
			} 
			else return false;
			
		}
		
		/*
		'-------------------------------------------------------------------------------------------------------------------------------------------
		' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
		'
		' Inputs:  
		'		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
		' Returns: 
		'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
		'--------------------------------------------------------------------------------------------------------------------------------------------	
		*/
		public function ConfirmPayment( $token, $paymentType, $currencyCodeType, $payerID, $FinalPaymentAmt )
		{
			/* Gather the information to make the final call to
			   finalize the PayPal payment.  The variable nvpstr
			   holds the name value pairs
			   */
			$token 				= urlencode($token);
			$paymentType 		= urlencode($paymentType);
			$currencyCodeType 	= urlencode($currencyCodeType);
			$payerID 			= urlencode($payerID);
			$serverName 		= urlencode($_SERVER['SERVER_NAME']);

			$nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . '&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
			$nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName; 

			 /* Make the call to PayPal to finalize payment
				If an error occured, show the resulting errors
				*/
			$resArray=$this->hash_call("DoExpressCheckoutPayment",$nvpstr);

			/* Display the API response back to the browser.
			   If the response from PayPal was a success, display the response parameters'
			   If the response was an error, display the errors received using APIError.php.
			   */
			$ack = strtoupper($resArray["ACK"]);

			return $resArray;
		}
		/**
		  '-------------------------------------------------------------------------------------------------------------------------------------------
		  * hash_call: Function to perform the API call to PayPal using API signature
		  * @methodName is name of API  method.
		  * @nvpStr is nvp string.
		  * returns an associtive array containing the response from the server.
		  '-------------------------------------------------------------------------------------------------------------------------------------------
		*/
		public function hash_call($methodName,$nvpStr)
		{
			//declaring of global variables
			//setting the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$this->API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);

			//turning off the server and peer verification(TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			
			//if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
		   //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
			if($this->USE_PROXY)
				curl_setopt ($ch, CURLOPT_PROXY, $this->PROXY_HOST. ":" . $this->PROXY_PORT); 

			//NVPRequest for submitting to server
			$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($this->version) . "&PWD=" . urlencode($this->API_Password) . "&USER=" . urlencode($this->API_UserName) . "&SIGNATURE=" . urlencode($this->API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($this->sBNCode);

			//setting the nvpreq as POST FIELD to curl
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

			//getting response from server
			$response = curl_exec($ch);

			//convrting NVPResponse to an Associative Array
			$nvpResArray=$this->deformatNVP($response);
			$nvpReqArray=$this->deformatNVP($nvpreq);
			$_SESSION['nvpReqArray']=$nvpReqArray;

			if (curl_errno($ch)) 
			{
				// moving to display page to display curl errors
				  $_SESSION['curl_error_no']=curl_errno($ch) ;
				  $_SESSION['curl_error_msg']=curl_error($ch);

				  //Execute the Error handling module to display errors. 
			} 
			else 
			{
				 //closing the curl
				curl_close($ch);
			}

			return $nvpResArray;
		}

		/*'----------------------------------------------------------------------------------
		 Purpose: Redirects to PayPal.com site.
		 Inputs:  NVP string.
		 Returns: 
		----------------------------------------------------------------------------------
		*/
		public function RedirectToPayPal ( $token )
		{
			// Redirect to paypal.com here
			$payPalURL = $this->PAYPAL_URL . $token;
			header("Location: ".$payPalURL);
			exit;
		}
		public function RedirectToPayPalDG ( $token )
		{
			// Redirect to paypal.com here
			$payPalURL = $this->PAYPAL_DG_URL . $token;
			header("Location: ".$payPalURL);
			exit;
		}


		
		/*'----------------------------------------------------------------------------------
		 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
		  * It is usefull to search for a particular key and displaying arrays.
		  * @nvpstr is NVPString.
		  * @nvpArray is Associative Array.
		   ----------------------------------------------------------------------------------
		  */
		function deformatNVP($nvpstr)
		{
			$intial=0;
			$nvpArray = array();

			while(strlen($nvpstr))
			{
				//postion of Key
				$keypos= strpos($nvpstr,'=');
				//position of value
				$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

				/*getting the Key and Value values and storing in a Associative Array*/
				$keyval=substr($nvpstr,$intial,$keypos);
				$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
				//decoding the respose
				$nvpArray[urldecode($keyval)] =urldecode( $valval);
				$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
			 }
			return $nvpArray;
		}
	}
?>