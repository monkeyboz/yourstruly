<?php
require_once ("paypalfunctions.php");

$PaymentOption = "PayPal";
if ( $PaymentOption == "PayPal")
{
        // ==================================
        // PayPal Express Checkout Module
        // ==================================

	
	        
        //'------------------------------------
        //' The paymentAmount is the total value of 
        //' the purchase.
        //'
        //' TODO: Enter the total Payment Amount within the quotes.
        //' example : $paymentAmount = "15.00";
        //'------------------------------------

        $paymentAmount = "100.00";
        
        
        //'------------------------------------
        //' The currencyCodeType  
        //' is set to the selections made on the Integration Assistant 
        //'------------------------------------
        $currencyCodeType = "USD";
        $paymentType = "Sale";

        //'------------------------------------
        //' The returnURL is the location where buyers return to when a
        //' payment has been succesfully authorized.
        //'
        //' This is set to the value entered on the Integration Assistant 
        //'------------------------------------
        $returnURL = "http://taureanwooley.com/yourstruly/?step=thankyou";

        //'------------------------------------
        //' The cancelURL is the location buyers are sent to when they hit the
        //' cancel button during authorization of payment during the PayPal flow
        //'
        //' This is set to the value entered on the Integration Assistant 
        //'------------------------------------
        $cancelURL = "http://taureanwooley.com/yourstruly/?step=cancel";

        //'------------------------------------
        //' Calls the SetExpressCheckout API call
        //'
        //' The CallSetExpressCheckout function is defined in the file PayPalFunctions.php,
        //' it is included at the top of this file.
        //'-------------------------------------------------

        
		$items = array();
		$items[] = array('name' => 'Item Name', 'amt' => $paymentAmount, 'qty' => 1);
	
		//::ITEMS::
		
		// to add anothe item, uncomment the lines below and comment the line above 
		// $items[] = array('name' => 'Item Name1', 'amt' => $itemAmount1, 'qty' => 1);
		// $items[] = array('name' => 'Item Name2', 'amt' => $itemAmount2, 'qty' => 1);
		// $paymentAmount = $itemAmount1 + $itemAmount2;
		
		// assign corresponding item amounts to "$itemAmount1" and "$itemAmount2"
		// NOTE : sum of all the item amounts should be equal to payment  amount 
		
		$payFunctions = new paypalFunction();
		$resArray = $payFunctions->SetExpressCheckoutDG( $paymentAmount, $currencyCodeType, $paymentType, 
												$returnURL, $cancelURL, $items );

        $ack = strtoupper($resArray["ACK"]);
        if($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
        {
                $token = urldecode($resArray["TOKEN"]);
                $payFunctions->RedirectToPayPalDG( $token );
        } 
        else  
        {
                //Display a user friendly Error on the page using any of the following error information returned by PayPal
                $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
                $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
                $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
                
                echo "SetExpressCheckout API call failed. ";
                echo "Detailed Error Message: " . $ErrorLongMsg;
                echo "Short Error Message: " . $ErrorShortMsg;
                echo "Error Code: " . $ErrorCode;
                echo "Error Severity Code: " . $ErrorSeverityCode;
        }
}

?>
