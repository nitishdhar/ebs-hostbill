<?php
/*
 * EBS Payment Gateway Integration Module for HostBill
 * Author - Nitish Dhar
 * Email - nitishdhar11@gmail.com
 *  
 * http://nitishdhar.com
 */
class Ebs extends PaymentModule {
    
    /*
     * const NAME
     * Note: This needs to reflect class name - case sensitive.
     */
    const NAME = 'Ebs';

    /*
     * const VER
     * Insert your module version here
     */
    const VER ='1.0';
    
    /*
     * protected $modname
     * AKA. "Nice name" - you can additionally add this variable - its contents will be displayed as module name after activation
     */
    protected $modname = 'EBS Hostbill';
    
    /*
     * protected $description
     * If you want, you can add description to module, so its potential users will know what its for.
     */
    protected $description='EBS Payment Gateway Module.';

    /*
     * protected $filename
     * This needs to reflect actual filename of module - case sensitive.
     */
    protected $filename='class.ebs.php';
    
    /*
     * protected $supportedCurrencies
     * List of currencies supported by EBS.
     */
    protected $supportedCurrencies = array('INR');
    
    /*
     * protected $configuration
     * Configuration Array
     */
    protected $configuration = array(
        'account_id' =>array(
            'value'=>'',
            'type'=>'input'
        ),
        'secret_key'=>array(
            'value'=>'',
            'type'=>'input'
        ),
        'mode'=>array(
            'value'=>'TEST',
            'type'=>'input'
        ),
        'tdr'=>array(
            'value'=>'0.05',
            'type'=>'input'
        ),
        'success_message'=>array(
            'value'=>'Thank you! Transaction was successful! We have received you payment.',
            'type'=>'input'
        ),
        'failure_message'=>array(
            'value'=>'Transaction Failed!',
            'type'=>'input'
        )
        
    );
    
    //language array - each element key should start with module NAME
    protected $lang=array(
        'english'=>array(
            'Ebsaccount_id'=>'Account ID',
            'Ebssecret_key'=>'Secret Key',
            'Ebsmode'=>'Mode',
            'Ebstdr'=>'TDR - If TDR is 5%, provide value as 0.05',
            'Ebssuccess_message'=>'Success Message',
            'Ebsfailure_message'=>'Failure Message'
        )
    );
    
//CRYPT CLASS - Required by EBS Payment Gateway Integration
    /**
     * Real programmers...
     * @var array
     */
    var $s = array();

    /**
     * Real programmers...
     * @var array
     */
    var $i = 0;

    /**
     * Real programmers...
     * @var array
     */
    var $j = 0;

    /**
     * Key holder
     * @var string
     */
    var $_key;

    /**
     * Constructor
     * Pass encryption key to key()
     *
     * @see    key() 
     * @param  string key    - Key which will be used for encryption
     * @return void
     * @access public
     */
    function Crypt_RC4($key = null) {
        if ($key != null) {
            $this->setKey($key);
        }
    }

    function setKey($key) {
        if (strlen($key) > 0)
            $this->_key = $key;
    }

    /**
     * Assign encryption key to class
     *
     * @param  string key	- Key which will be used for encryption
     * @return void
     * @access public    
     */
    function key(&$key) {
        $len = strlen($key);
        for ($this->i = 0; $this->i < 256; $this->i++) {
            $this->s[$this->i] = $this->i;
        }

        $this->j = 0;
        for ($this->i = 0; $this->i < 256; $this->i++) {
            $this->j = ($this->j + $this->s[$this->i] + ord($key[$this->i % $len])) % 256;
            $t = $this->s[$this->i];
            $this->s[$this->i] = $this->s[$this->j];
            $this->s[$this->j] = $t;
        }
        $this->i = $this->j = 0;
    }

    /**
     * Encrypt function
     *
     * @param  string paramstr 	- string that will encrypted
     * @return void
     * @access public    
     */
    function crypt(&$paramstr) {

        //Init key for every call, Bugfix 22316
        $this->key($this->_key);

        $len = strlen($paramstr);
        for ($c = 0; $c < $len; $c++) {
            $this->i = ($this->i + 1) % 256;
            $this->j = ($this->j + $this->s[$this->i]) % 256;
            $t = $this->s[$this->i];
            $this->s[$this->i] = $this->s[$this->j];
            $this->s[$this->j] = $t;

            $t = ($this->s[$this->i] + $this->s[$this->j]) % 256;

            $paramstr[$c] = chr(ord($paramstr[$c]) ^ $this->s[$t]);
        }
    }

    /**
     * Decrypt function
     *
     * @param  string paramstr 	- string that will decrypted
     * @return void
     * @access public    
     */
    function decrypt(&$paramstr) {
        //Decrypt is exactly the same as encrypting the string. Reuse (en)crypt code
        $this->crypt($paramstr);
    }

    //CRYPT CLASS ENDS
    // if using constructor - dont forget to invoke parent constructor in it
    public function __construct() {
        parent::__construct();
    }

    
    //prepare  payment hidded form fields
    public function drawForm($autosubmit = false) {
        $gatewayaccountid = $this->configuration['account_id']['value']; // Your Account ID
        $secret_key = $this->configuration['secret_key']['value'];  // Your Secret Key
        $gatewaytestmode = $this->configuration['mode']['value']; // Mode
        # Invoice Variables
        $invoiceid = $this->invoice_id;
        $description = $this->subject;
        $amount = $this->amount;


        # Client Variables
        $name = $this->client['firstname'] . $this->client['lastname'];
        $email = $this->client['email'];
        $address1 = $this->client['address1'];
        $city = $this->client['city'];
        $state = $this->client['state'];
        $postcode = $this->client['postcode'];
        $country = $this->client['country'];
        $phone = $this->client['phonenumber'];

        $callBackUrl = $this->callback_url . "&DR={DR}";

        $hash = $secret_key . "|" . $gatewayaccountid . "|" . $amount . "|" . $invoiceid . "|" . $callBackUrl . "|" . $gatewaytestmode;

        $secure_hash = md5($hash);

        # System Variables
        $companyname = 'EBS';

        $code = '<form method="post" action="https://secure.ebs.in/pg/ma/sale/pay/" name="frmTransaction" id="frmTransaction" onSubmit="return validate()">
<input type="hidden" name="account_id" value="' . $gatewayaccountid . '" />
<input type="hidden" name="mode" value="' . $gatewaytestmode . '" />
<input type="hidden" name="description" value="' . $description . '" />
<input type="hidden" name="reference_no" value="' . $invoiceid . '" />
<input type="hidden" name="name" value="' . $name . '" />
<input type="hidden" name="address" value="' . $address1 . '" />
<input type="hidden" name="city" value="' . $city . '" />
<input type="hidden" name="state" value="' . $state . '" />
<input type="hidden" name="country" value="' . $country . '" />
<input type="hidden" name="postal_code" value="' . $postcode . '" />
<input type="hidden" name="ship_name" value="' . $name . '" />

<input type="hidden" name="ship_address" value="' . $address1 . '" />
<input type="hidden" name="ship_city" value="' . $city . '" />
<input type="hidden" name="ship_state" value="' . $state . '" />
<input type="hidden" name="ship_country" value="' . $country . '" />
<input type="hidden" name="ship_postal_code" value="' . $postcode . '" />
<input type="hidden" name="ship_phone" value="' . $phone . '" />
<input type="hidden" name="email" value="' . $email . '" />
<input type="hidden" name="phone" value="' . $phone . '" />
<input type="hidden" name="amount" value="' . $amount . '" />
<input type="hidden" name="return_url" value="' . $callBackUrl . '" />
<input type="hidden" name="secure_hash" value="' . $secure_hash . '"/>
<input type="submit" value="Pay Now" class="btn btn-success" />
</form>';

        if ($autosubmit) {
            $code .="<script language=\"javascript\">
                setTimeout ( \"autoForward()\" , 5000 );
                function autoForward() {
                    document.forms.payform.submit()
                }
                </script>
                ";
        }

        return $code;
    }

    public function callback() {

        //you will probably going to validate it here somehow - checksum, ip etc.

        $secret_key = $this->configuration['secret_key']['value'];  // Your Secret Key

        if (isset($_GET['DR'])) {

            $DR = preg_replace("/\s/", "+", $_GET['DR']);

            $this->Crypt_RC4($secret_key);
            $QueryString = base64_decode($DR);
            $this->decrypt($QueryString);
            $QueryString = explode('&', $QueryString);

            foreach ($QueryString as $param) {
                $param = split('=', $param);
                $response[$param[0]] = urldecode($param[1]);
            }
        }


        if ($response['ResponseCode'] == 0) {

            $this->logActivity(array(
                'output' => $response,
                'result' => PaymentModule::PAYMENT_SUCCESS
            ));
            
            $response['Fee'] = round(($response['Amount']*.05), 2);  
            
            $this->addTransaction(array(
                'client_id' => $this->client['id'],
                'invoice_id' => $response['MerchantRefNo'],
                'description' => $response['Description'],
                'in' => $response['Amount'],
                'fee' => $response['Fee'],
                'transaction_id' => $response['TransactionID']
            ));
            
            $this->addInfo($this->configuration['success_message']['value']);
            Utilities::redirect('?cmd=clientarea');
        }

        if ($response['ResponseCode'] <> 0) {

            $this->logActivity(array(
                'output' => $response,
                'result' => PaymentModule::PAYMENT_FAILURE
            ));

            $this->addInfo($this->configuration['failure_message']['value']);
            Utilities::redirect('?cmd=clientarea');
        }
    }

}

?>
