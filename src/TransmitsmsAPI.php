<?php

namespace Cserobiul\BurstTransmitsmsApi;

class TransmitsmsAPI
{
    public $url = 'http://api.transmitsms.com/';

    protected $version=2;

    protected $authHeader;

    protected $responseRawData;

    protected $responseStatus;

    public function __construct($key, $secret)
    {
        $this->authHeader=array('Authorization: Basic '.base64_encode($key.':'.$secret));
    }

    protected function generateError($code, $description)
    {
        $error=new stdClass();
        $error->error = new stdClass();
        $error->error->code=$code;
        $error->error->description=$description;
        return $error;
    }

    protected function getRequestURL($method)
    {
        return $this->url.'/'.$this->version.'/'.$method.'.json';
    }

    protected function request($method, $params=array())
    {
        $requestUrl=$this->getRequestURL($method);

        $ch = curl_init($requestUrl);
        if (! $ch) {
            return $this->generateError('REQUEST_FAILED' ,"Error connecting to the server {$requestUrl} : ". curl_errno($ch) .':'. curl_error($ch));
        }

        $urlInfo = parse_url($requestUrl);
        $port = (preg_match("/https|ssl/i", $urlInfo["scheme"])) ? 443 : 80;

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_USERAGENT, "transmitsmsAPI v." . $this->version);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->authHeader);

        $this->responseRawData = curl_exec($ch);
        if (! $this->responseRawData) {
            return $this->generateError('REQUEST_FAILED' ,"Problem executing request, try changing above set options and re-requesting : ".curl_errno($ch) .':' .curl_error($ch));
        }
        $this->responseStatus=curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return json_decode($this->responseRawData);//, false, 512, JSON_BIGINT_AS_STRING);
    }

    protected function handleResponse($response)
    {
        if($response===null)
            return $this->generateError("INVALID_RESPONSE", "Invalid response, received data: ".$this->responseRawData);
        //possible checks for login failure and other common mistakes
        return $response;
    }

    protected function indexCustomFields(&$params, $fields)
    {
        if(!count($fields))
            return;
        if(isset($fields[0]))
        {
            // this is not an associative array, we iterate and indexify from 1 to 10
            $fieldIndex=1;
            foreach($fields as $field)
            {
                $params["field_{$fieldIndex}"]=$field;
                $fieldIndex++;
            }
        }
        else
        {
            // this is an associative array, we iterate and keep the indexes
            foreach($fields as $fieldIndex=>$field)
            {
                $params["field_{$fieldIndex}"]=$field;
            }
        }
    }

    protected function prepareFieldsForEdit(&$params)
    {
        foreach ($params as $key=>$value) {
            if($value===null)
                unset($params[$key]);
        }
    }

    /**
     * Send SMS messages.
     *
     * @param string $message
     * @param string $to - required if list_id is not set
     * @param string $from
     * @param datetime $send_at
     * @param int $list_id - required if to is not set
     * @param string $dlr_callback
     * @param string $reply_callback
     * @param int $validity
     *
     */
    public function sendSms($message, $to='', $from='', $send_at='', $list_id=0, $dlr_callback='', $reply_callback='', $validity=0, $replies_to_email='', $tracked_link_url='')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('send-sms', $params));
    }

    /**
     * Get data about a sent message.
     *
     * @param int $message_id
     *
     */
    public function getSms($message_id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-sms', $params));
    }
    /**
     * Get sent messages.
     *
     * @param int $message_id
     * @param int $page
     * @param int $max
     * @param string $optouts can be 'only', 'omit', 'include'
     *
     */
    public function getSmsSent($message_id, $page=1, $max=10, $optouts='include', $delivery='all')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-sms-sent', $params));
    }
    /**
     * Get SMS responses.
     *
     * @param int $message_id
     * @param int $keyword_id
     * @param string $keyword
     * @param string $number
     * @param string $msisdn
     * @param int $page
     * @param int $max
     *
     */
    public function getSmsResponses($message_id, $keyword_id=0, $keyword='', $number='', $msisdn='', $page=1, $max=10, $include_original=false)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-sms-responses', $params));
    }

    /**
     * Get SMS responses for user
     *
     * @param datetime $start
     * @param datetime $end
     * @param int $page
     * @param int $max
     * @param string $keywords
     *
     */
    public function getUserSmsResponses($start=null, $end=null, $page=1, $max=10, $keywords='both', $number = '', $include_original=false)
    {
        $params = get_defined_vars();
        $this->prepareFieldsForEdit($params);
        return $this->handleResponse($this->request('get-user-sms-responses', $params));
    }

    /**
     * Get SMS sent by user in certain time frame
     *
     * @param datetime $start
     * @param datetime $end
     * @param string $msisdn
     * @param int $page
     * @param int $max
     *
     */
    public function getUserSmsSent($start=null, $end=null, $msisdn='', $page=1, $max=10)
    {
        $params = get_defined_vars();
        $this->prepareFieldsForEdit($params);
        return $this->handleResponse($this->request('get-user-sms-sent', $params));
    }

    /**
     * Cancel a scheduled SMS
     *
     * @param int $id
     *
     */
    public function cancelSms($id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('cancel-sms', $params));
    }
    /**
     * Get information about a list and its members.
     *
     * @param int $list_id
     * @param int $page
     * @param int $max
     * @param string $members can be 'active', 'inactive', 'all', 'none'
     *
     */
    public function getList($list_id, $page=1, $max=10, $members='active')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-list', $params));
    }
    /**
     * Get the metadata of your lists.
     * @param int $page
     * @param int $max
     *
     */
    public function getLists($page=1, $max=10)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-lists', $params));
    }

    /**
     * Create a new list.
     *
     * @param string $name
     * @param array $fields
     *
     */
    public function addList($name, $fields=array())
    {
        $params['name']=$name;
        $this->indexCustomFields($params, $fields);
        return $this->handleResponse($this->request('add-list', $params));
    }
    /**
     * Delete a list and its members.
     *
     * @param integer $list_id
     *
     */
    public function removeList($list_id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('remove-list', $params));
    }
    /**
     * Add a member to a list.
     *
     * @param int $list_id
     * @param string $msisdn
     * @param string $first_name
     * @param string $last_name
     * @param array $fields
     *
     */
    public function addToList($list_id, $msisdn, $first_name='', $last_name='', $fields=array(), $countrycode=NULL)
    {
        $params=get_defined_vars();
        unset($params['fields']);
        $this->indexCustomFields($params, $fields);
        return $this->handleResponse($this->request('add-to-list', $params));
    }
    /**
     * Edit a list member.
     *
     * @param int $list_id
     * @param string $msisdn
     * @param string $first_name
     * @param string $last_name
     * @param array $fields
     *
     */
    public function editListMember($list_id, $msisdn, $first_name=null, $last_name=null, $fields=array())
    {
        $params=get_defined_vars();
        unset($params['fields']);
        $this->indexCustomFields($params, $fields);
        $this->prepareFieldsForEdit($params);
        return $this->handleResponse($this->request('edit-list-member', $params));
    }

    /**
     * Remove a member from one list or all lists.
     *
     * @param int $list_id
     * @param string $msisdn
     *
     */
    public function deleteFromList($list_id, $msisdn)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('delete-from-list', $params));
    }

    /**
     * Opt-out a member from one list or all lists.
     *
     * @param int $list_id
     * @param string $msisdn
     *
     */
    public function optoutListMember($list_id, $msisdn)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('optout-list-member', $params));
    }

    /**
     * Get leased number details
     *
     * @param string $number
     *
     */
    public function getNumber($number)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-number', $params));
    }
    /**
     * Get a list of numbers.
     *
     * @param int $page
     * @param int $max
     * @param string $filter
     *
     */
    public function getNumbers($page=1, $max=10, $filter='owned')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-numbers', $params));
    }

    /**
     * Lease a response number.
     *
     * @param string $number
     *
     */
    public function leaseNumber($number='', $url='')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('lease-number', $params));
    }

    /**
     * Edit options of existing number.
     *
     * @param string $number
     * @param string $forward_email
     * @param string $forward_sms
     * @param string $forward_url
     * @param int $list_id
     * @param string $welcome_message
     * @param string $members_message
     *
     */
    public function editNumberOptions($number, $forward_email='', $forward_sms='', $forward_url='', $list_id=0, $welcome_message='', $members_message='')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('edit-number-options', $params));
    }

    /**
     * Get a client.
     *
     * @param int $client_id
     *
     */
    public function getClient($client_id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-client', $params));
    }

    /**
     * Get a list of clients.
     *
     * @param int $page
     * @param int $max
     *
     */
    public function getClients($page=1, $max=10)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-clients', $params));
    }
    /**
     * Add a new client
     *
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $msisdn
     * @param string $contact
     * @param string $timezone
     * @param bool $client_pays
     * @param float $sms_margin
     *
     */
    public function addClient($name, $email, $password, $msisdn, $contact='', $timezone='', $client_pays=true, $sms_margin=0.0)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-client', $params));
    }

    /**
     * Edit a client
     *
     * @param int $id
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $msisdn
     * @param string $contact
     * @param string $timezone
     * @param bool $client_pays
     * @param float $sms_margin
     *
     */
    public function editClient($client_id, $name=null, $email=null, $password=null, $msisdn=null, $contact=null, $timezone=null, $client_pays=null, $sms_margin=null)
    {
        $params = get_defined_vars();
        $this->prepareFieldsForEdit($params);
        return $this->handleResponse($this->request('edit-client', $params));
    }

    /**
     * Add a keyword to your existing response number.
     *
     * @param string $keyword
     * @param string $number
     * @param string $reference
     * @param int $list_id
     * @param string $welcome_message
     * @param string $members_message
     * @param bool $activate
     * @param string $forward_url
     * @param string $forward_email
     * @param string $forward_sms
     *
     */
    public function addKeyword($keyword, $number, $reference='', $list_id=0, $welcome_message='', $members_message='', $activate=true, $forward_url='', $forward_email='', $forward_sms='')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-keyword', $params));
    }

    /**
     * Edit an existing keyword.
     *
     * @param string $keyword
     * @param string $number
     * @param string $reference
     * @param int $list_id
     * @param string $welcome_message
     * @param string $members_message
     * @param string $status
     * @param string $forward_url
     * @param string $forward_email
     * @param string $forward_sms
     *
     */
    public function editKeyword($keyword, $number, $reference=null, $list_id=null, $welcome_message=null, $members_message=null, $status=null, $forward_url=null, $forward_email=null, $forward_sms=null)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('edit-keyword', $params));
    }

    /**
     * Get a list of existing keywords.
     *
     * @param string $number
     * @param int $page
     * @param int $max
     *
     */
    public function getKeywords($number='',$page=1, $max=10)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-keywords', $params));
    }

    /**
     * Get a list of transactions for a client.
     *
     * @param int $client_id
     * @param datetime $start
     * @param datetime $end
     *
     */
    public function getTransactions($client_id, $start=null, $end=null, $page=1, $max=10)
    {
        $params = get_defined_vars();
        $this->prepareFieldsForEdit($params);
        return $this->handleResponse($this->request('get-transactions', $params));
    }

    /**
     * Get a transaction.
     *
     * @param int $id
     *
     */
    public function getTransaction($id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-transaction', $params));
    }

    /**
     * Register an email address for Email to SMS.
     *
     * @param string $email
     * @param int $max_sms
     * @param string $number
     *
     */
    public function addEmail($email, $max_sms=1, $number='')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-email', $params));
    }

    /**
     * Remove an email address from Email to SMS.
     *
     * @param string $email
     *
     */
    public function deleteEmail($email)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('delete-email', $params));
    }

    /**
     * Get active user's balance
     *
     */
    public function getBalance()
    {
        return $this->handleResponse($this->request('get-balance'));
    }

    public function formatNumber($msisdn, $countrycode)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('format-number', $params));
    }

    /**
     * Add contacts in bulk
     * @param string $name
     * @param string $file_url
     * @param array $fields
     * @param string $countrycode
     *
     */
    public function addContactsBulk($name, $file_url, $fields = [], $countrycode = '')
    {
        $params = get_defined_vars();
        $this->indexCustomFields($params, $fields);
        return $this->handleResponse($this->request('add-contacts-bulk', $params));
    }

    /**
     * Check add contacts bulk progress
     * @param integer $list_id
     *
     */
    public function addContactsBulkProgress($list_id)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-contacts-bulk-progress', $params));
    }

    /**
     * Get contact details
     * @param integer $list_id
     * @param string $msisdn
     *
     */
    public function getContact($list_id, $msisdn)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-contact', $params));
    }

    /**
     * Get link hits report
     * @param integer $message_id
     * @param integer $page
     * @param integer $max
     *
     */
    public function getLinkHits($message_id, $page = 1, $max = 10)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-link-hits', $params));
    }

    /**
     * Get sms delivery status
     * @param integer $message_id
     * @param string $msisdn
     *
     */
    public function getSmsDeliveryStatus($message_id, $msisdn)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('get-sms-delivery-status', $params));
    }

    /**
     * Add credit
     * @param float $amount
     * @param string $creditcard_id
     */
    public function addCredit($amount = 0, $creditcard_id = '')
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-credit', $params));
    }
    /**
     * Add credit card
     * @param string $name
     * @param integer $number
     * @param integer $expiry_month
     * @param integer $expiry_year
     * @param integer $cvv
     */
    public function addCard($name, $number, $expiry_month, $expiry_year, $cvv)
    {
        $params = get_defined_vars();
        return $this->handleResponse($this->request('add-card', $params));
    }
}
