<?php
 
/**
 *
 * GL CMS, v0.02
 * 
 * Copyright 2009-2024, Rich Morgan (rich@richmorgan.me).
 * All rights reserved.
 *
 * PHP 5.x Class to handle Authorize.Net financial transactions.
 * Currently supported transaction version is 3.1.
 *
 * Class version: 0.02, 02/13/2009
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */
 
class rlmAuthorizeDotNetTransaction {
    private $CLASS_VERSION = "0.02";
    private $DEBUGGING;
    private $TESTING;
    private $ERROR_RETRIES;
    private $auth_net_login_id;
    private $auth_net_tran_key;
    private $auth_net_url;
     
    /**
     * Magic public constructor method.
     * @param array $credentials
     */
    public function __construct($credentials) {
        // Must pass an array consisting of: login id and transaction key
        $this->init($credentials);
    }
     
    /**
     * Magic unknown property get method.
     * @param mixed $var
     * @return string
     */ 
    public function __get($var) {
        die("rlmAuthorizeDotNetTransaction->__get():  Error - Cannot get nonexistent properties of this class." . $var . " is not a valid property.\n");
    }
     
    /**
     * Magic unknown property get method.
     * @param mixed $var
     * @return string
     */
    public function __set($var,$value) {
        die("rlmAuthorizeDotNetTransaction->__set():  Error - Cannot set nonexistent properties of this class." . $var . " is not a valid property.\n");
    }
     
    /**
     * Public initializer method.
     * @param array $credentials
     */
    public function init($credentials) {
        // Initialize parameters                
        $this->DEBUGGING         = 0;                    // Display additional information to track down problems
        $this->TESTING           = 1;                    // Set the testing flag so that transactions are not live
        $this->ERROR_RETRIES     = 2;                    // Number of transactions to post if soft errors occur
         
        $this->auth_net_login_id = $credentials[0];      // Your login ID
        $this->auth_net_tran_key = $credentials[1];      // Your transaction key
         
        $this->auth_net_url      = "https://secure.authorize.net/gateway/transact.dll";      
    }
     
    /**
     * Public getter method.
     * @param mixed $var
     * @return mixed
     */
    public function getValue($var) {
        if(isset($this->$var)) {
            return $this->$var;
        } else {
            return null;
        }
    }
     
    /**
     * Public setter method.
     * @param mixed $var
     * @param mixed $value
     * @return boolean
     */
     public function setValue($var,$value) {
        if(isset($this->$var)) {
            $this->$var = $value;
            return true;
        } else {
            return false;
        }
    }
     
    /**
     * Private Authorize.net connection method.
     * @return resource $conn
     */
    private function connect() {
        // Connect to Authorize.net     
        $conn = curl_init($this->auth_net_url);
         
        curl_setopt($conn, CURLOPT_HEADER, 0);                              // Set to 0 to eliminate header info from response
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);                      // Returns response data instead of TRUE(1)
        curl_setopt($conn, CURLOPT_POSTFIELDS, rtrim( $fields, "& " ));     // use HTTP POST to send form data
         
        /* curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);                 // Uncomment this line if you get no gateway response. */
         
        return $conn;
    }
         
    /**
     * Private connection closer method.
     * @param resource $conn
     * @return boolean
     */
    private function close($conn) {
        // Close connection 
        return curl_close($conn);
    }
         
    /**
     * Public Authorize.net transaction processing method.
     * @param array $customer_data
     * @param array $card_data
     * @param array $sale_data
     * @return array $returned_data
     */
    public function process($customer_data,$card_data,$sale_data) {
        // Send transaction and return result
        // Authorize.net transaction fields
         
        $complete_street_address = trim($customer_data['address1'] . " " . $customer_data['address2']);
        $complete_shipping_address = trim($customer_data['shipping_address1'] . " " . $customer_data['shipping_address2']);
         
        $authnet_values             = array
        (
            "x_login"               => $this->auth_net_login_id,
            "x_version"             => "3.1",
            "x_delim_char"          => "|",
            "x_delim_data"          => "TRUE",
            "x_url"                 => "FALSE",
            "x_type"                => "AUTH_CAPTURE",
            "x_method"              => "CC",
            "x_tran_key"            => $this->auth_net_tran_key,
            "x_relay_response"      => "FALSE",
            "x_card_num"            => $card_data['ccnum'],
            "x_exp_date"            => $card_data['ccexpdate'],
            "x_card_code"           => $card_data['ccseccode'],
            "x_description"         => $sale_data['description'],
            "x_amount"              => $sale_data['amount'],
            "x_first_name"          => $customer_data['first_name'],
            "x_last_name"           => $customer_data['last_name'],
            "x_address"             => $complete_street_address,
            "x_city"                => $customer_data['city'],
            "x_state"               => $customer_data['state'],
            "x_zip"                 => $customer_data['zipcode'],
            "x_ship_to_first_name"  => $customer_data['shipping_first_name'],
            "x_ship_to_last_name"   => $customer_data['shipping_last_name'],
            "x_ship_to_address"     => $complete_shipping_address,
            "x_ship_to_city"        => $customer_data['shipping_city'],
            "x_ship_to_state"       => $customer_data['shipping_state'],
            "x_ship_to_zip"         => $customer_data['shipping_zipcode']    
        );
         
        $fields = "";
        foreach( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";
         
        // Execute post and get results
        $ch = $this->connect();      
        $resp = curl_exec($ch);             
        $this->close($ch);
                 
        $text = $resp;
        $h = substr_count($text, "|");
        $h++;
 
        for($j=1; $j <= $h; $j++){
     
            $p = strpos($text, "|");        
            $p++;   
             
            //  get one portion of the response at a time
            $pstr = substr($text, 0, $p);
         
            //  this prepares the text and returns one value of the submitted
            //  and processed name/value pairs at a time
            //  for AIM-specific interpretations of the responses
            //  please consult the AIM Guide and look up
            //  the section called Gateway Response API
             
            $pstr_trimmed = substr($pstr, 0, -1); // removes "|" at the end
         
            if($pstr_trimmed=="") {
                $pstr_trimmed="NO VALUE RETURNED";
            }
             
            switch($j) {
                 
                case 1:
                    // Response Code
                    $respcode="";
                    if($pstr_trimmed=="1") {
                        $respcode="Approved";
                    } elseif($pstr_trimmed=="2") {
                        $respcode="Declined";
                    } elseif($pstr_trimmed=="3") {
                        $respcode="Error";
                    }
                    //echo $fval;
                    break;
                         
                case 2:
                    //echo "Response Subcode: ";
                    $respsubcode=$pstr_trimmed;
                    break;
     
                case 3:
                    //echo "Response Reason Code: ";
                    $respreasoncode=$pstr_trimmed;
                    break;
     
                case 4:
                    //echo "Response Reason Text: ";
                    $respreasontext=$pstr_trimmed;
                    break;
     
                case 5:
                    //echo "Approval Code: ";
                    $approvalcode=$pstr_trimmed;
                    break;
     
                case 6:
                    //echo "AVS Result Code: ";
                    $avsresultcode=$pstr_trimmed;
                    break;
     
                case 7:
                    //echo "Transaction ID: ";
                    $transactionid=$pstr_trimmed;
                    break;
     
                case 8:
                    //echo "Invoice Number (x_invoice_num): ";
                    $invoicenumber=$pstr_trimmed;
                    break;
     
                case 9:
                    //echo "Description (x_description): ";
                    $description=$pstr_trimmed;
                    break;
     
                case 10:
                    //echo "Amount (x_amount): ";
                    $amount=$pstr_trimmed;
                    break;
     
                case 11:
                    //echo "Method (x_method): ";
                    $method=$pstr_trimmed;
                    break;
     
                case 12:
                    //echo "Transaction Type (x_type): ";
                    $transactiontype=$pstr_trimmed;
                    break;
     
                case 13:
                    //echo "Customer ID (x_cust_id): ";
                    $customerid=$pstr_trimmed;
                    break;
     
                case 14:
                    //echo "Cardholder First Name (x_first_name): ";
                    $cardholderfirstname=$pstr_trimmed;
                    break;
     
                case 15:
                    //echo "Cardholder Last Name (x_last_name): ";
                    $cardholderlastname=$pstr_trimmed;
                    break;
     
                case 16:
                    //echo "Company (x_company): ";
                    $company=$pstr_trimmed;
                    break;
     
                case 17:
                    //echo "Billing Address (x_address): ";             
                    $billingaddress=$pstr_trimmed;
                    break;
     
                case 18:
                    //echo "City (x_city): ";               
                    $city=$pstr_trimmed;
                    break;
     
                case 19:
                    //echo "State (x_state): ";             
                    $state=$pstr_trimmed;
                    break;
     
                case 20:
                    //echo "ZIP (x_zip): ";             
                    $zip=$pstr_trimmed;
                    break;
     
                case 21:
                    //echo "Country (x_country): ";             
                    $country=$pstr_trimmed;
                    break;
     
                case 22:
                    //echo "Phone (x_phone): ";             
                    $phone=$pstr_trimmed;
                    break;
     
                case 23:
                    //echo "Fax (x_fax): ";             
                    $fax=$pstr_trimmed;
                    break;
     
                case 24:
                    //echo "E-Mail Address (x_email): ";                
                    $emailaddress=$pstr_trimmed;
                    break;
     
                case 25:
                    //echo "Ship to First Name (x_ship_to_first_name): ";               
                    $shiptofirstname=$pstr_trimmed;
                    break;
     
                case 26:
                    //echo "Ship to Last Name (x_ship_to_last_name): ";             
                    $shiptolastname=$pstr_trimmed;
                    break;
     
                case 27:
                    //echo "Ship to Company (x_ship_to_company): ";             
                    $shiptocompany=$pstr_trimmed;
                    break;
     
                case 28:
                    //echo "Ship to Address (x_ship_to_address): ";             
                    $shiptoaddress=$pstr_trimmed;
                    break;
     
                case 29:
                    //echo "Ship to City (x_ship_to_city): ";               
                    $shiptocity=$pstr_trimmed;
                    break;
     
                case 30:
                    //echo "Ship to State (x_ship_to_state): ";             
                    $shiptostate=$pstr_trimmed;
                    break;
     
                case 31:
                    //echo "Ship to ZIP (x_ship_to_zip): ";             
                    $shiptozip=$pstr_trimmed;
                    break;
     
                case 32:
                    //echo "Ship to Country (x_ship_to_country): ";             
                    $shiptocountry=$pstr_trimmed;
                    break;
     
                case 33:
                    //echo "Tax Amount (x_tax): ";              
                    $taxamount=$pstr_trimmed;
                    break;
     
                case 34:
                    //echo "Duty Amount (x_duty): ";                
                    $dutyamount=$pstr_trimmed;
                    break;
     
                case 35:
                    //echo "Freight Amount (x_freight): ";              
                    $freightamount=$pstr_trimmed;
                    break;
     
                case 36:
                    //echo "Tax Exempt Flag (x_tax_exempt): ";              
                    $taxexemptflag=$pstr_trimmed;
                    break;
     
                case 37:
                    //echo "PO Number (x_po_num): ";                
                    $ponumber=$pstr_trimmed;
                    break;
     
                case 38:
                    //echo "MD5 Hash: ";
                    $md5hash=$pstr_trimmed;
                    break;
     
                case 39:
                    //echo "Card Code Response: ";
                    $ccr="";
                    if ($pstr_trimmed=="M") {
                        // = Match
                        $ccr="M";
                    } elseif ($pstr_trimmed=="N") {
                        // = No Match
                        $ccr="N";
                    } elseif ($pstr_trimmed=="P") {
                        // = Not Processed
                        $ccr="P";
                    } elseif ($pstr_trimmed=="S") {
                        // = Should have been present
                        $ccr="S";
                    } elseif ($pstr_trimmed=="U") {
                        // = Issuer unable to process request
                        $ccr="U";
                    } else {
                        $ccr="NO VALUE RETURNED";
                    }
                    break;
     
                case 40:
                case 41:
                case 42:
                case 43:
                case 44:
                case 45:
                case 46:
                case 47:
                case 48:
                case 49:
                case 50:
                case 51:
                case 52:
                case 53:
                case 54:
                case 55:
                case 55:
                case 56:
                case 57:
                case 58:
                case 59:
                case 60:
                case 61:
                case 62:
                case 63:
                case 64:
                case 65:
                case 66:
                case 67:
                case 68:
                    // echo "Reserved (".$j."): ";
                    // $pstr_trimmed;
                    break;  
                default:
                    if ($j>=69) {
                        // Merchant-defined
                        // $pstr_trimmed;
                    } else {
                        // echo $j;
                        // $pstr_trimmed;
                    }
                    break;
            }
     
            // Remove the part that we identified and work with the rest of the string
            $text = substr($text, $p);
        }
 
        $timestamp = date("Y-m-d h:m:s");
 
        $returned_data = array(
            "RESPONSE"      => $respcode,
            "REASON"        => $respreasontext,
            "APPROVALCODE"  => $approvalcode,
            "AVSRESULTCODE" => $avsresultcode,
            "TRANSACTIONID" => $transactionid,
            "MD5HASH"       => $md5hash,
            "CARDCODERESP"  => $ccr,
            "RAWRESPDATA"   => $resp,
            "TIMESTAMP"     => $timestamp
        );

        return $returned_data;
    }
}
