<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

/**
 * @var string $mode
 * @var string $order_id
 */

use Tygh\Http;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

class RealexRemotePaymentMethod
{
    const REALEX_REMOTE_3DSECURE_URL     = 'https://epage.payandshop.com/epage-3dsecure.cgi';
    const REALEX_REMOTE_NON_3DSECURE_URL = 'https://epage.payandshop.com/epage-remote.cgi';

    /**
     * Full 3D Secure – cardholder enrolled
     */
    const TRANSACTION_FULL_3DSECURE = 'TRANSACTION_FULL_3DSECURE';

    /**
     * Merchant 3D Secure – cardholder not enrolled or attempt ACS server was used
     */
    const TRANSACTION_MERCHANT_3DSECURE = 'TRANSACTION_MERCHANT_3DSECURE';

    /**
     * Non 3D Secure transaction. E.g. a refund or a 3D Secure transaction which failed midway.
     * It is up to the merchant to decide whether or not to proceed with a non-3D Secure transaction.
     * The liability shift no longer applies.
     * It may be better to offer the customer the chance to try again now or later.
     */
    const TRANSACTION_NON_3DSECURE = 'TRANSACTION_NON_3DSECURE';

    protected $redirected_to_acs = false;

    protected $processor_data;
    protected $order_info;
    protected $card;

    protected $response = array();

    public function __construct($processor_data, $order_info)
    {
        $this->processor_data = $processor_data;
        $this->order_info = $order_info;

        $this->card = $this->getCardData();
        $this->request_data = $this->getRequestData();

        $this->response = array(
            'order_status' => 'D',
            'reason_text' => 'Your transaction was unsuccessful. There was a problem with your order, please return to the checkout and try again.',
            'payments.realex.transaction_order_id' => 'N/A',
            'payments.realex.transaction_pasref' => 'N/A',
            'payments.realex.result_code' => 'N/A',
            'payments.realex.result_message' => 'N/A',
            '3d_secure' => 'N/A',
            'payments.realex.3d_secure_message' => 'N/A',
            'payments.realex.liability_shift' => 'N/A',
            'payments.realex.xid' => 'N/A',
            'payments.realex.cavv' => 'N/A',
            'payments.realex.eci' => 'N/A',
            'payments.realex.tss_result' => 'N/A',
            'payments.realex.avs_address' => 'N/A',
            'payments.realex.avs_postcode' => 'N/A',
        );

        if ($this->processor_data['processor_params']['3d_secure'] == 'enabled') {
            $this->response['3d_secure'] = 'Enabled';
        } else {
            $this->response['3d_secure'] = 'Disabled';
        }
    }

    protected function getCardData()
    {
        $card = array();
        $card['number'] = $this->order_info['payment_info']['card_number'];
        $card['cardholder_name'] = $this->order_info['payment_info']['cardholder_name'];
        $card['exp_month'] = $this->order_info['payment_info']['expiry_month'];
        $card['exp_year'] = $this->order_info['payment_info']['expiry_year'];
        $card['cvv2'] = !empty($this->order_info['payment_info']['cvv2']) ? $this->order_info['payment_info']['cvv2'] : '';
        $card['type'] = fn_get_payment_card(
            $this->order_info['payment_info']['card_number'],
            array(
                'visa' => 'VISA',
                'amex' => 'AMEX',
                'mastercard' => 'MC',
                'maestro' => 'MC',
                'laser' => 'LASER',
                'diners_club_carte_blanche' => 'DINERS',
                'diners_club_international' => 'DINERS',
            )
        );

        return $card;
    }

    protected function getRequestData()
    {
        $currency_settings = Registry::get('currencies.' . $this->processor_data['processor_params']['currency']);
        if (empty($currency_settings)) {
            $currency_settings = Registry::get('currencies.' . CART_PRIMARY_CURRENCY);
        }

        $timestamp = empty($_REQUEST['timestamp']) ? date('Ymdhis') : $_REQUEST['timestamp'];

        $billing_zipcode = preg_replace("/[^0-9]/", '', $this->order_info['b_zipcode']);
        $billing_address = preg_replace("/[^0-9]/", '', $this->order_info['b_address']);
        $shipping_zipcode = preg_replace("/[^0-9]/", '', $this->order_info['s_zipcode']);
        $shipping_address = preg_replace("/[^0-9]/", '', $this->order_info['s_address']);

        $request_data = array(
            'ORDER_ID' => $this->order_info['order_id'] . $timestamp,
            'MERCHANT_ID' => $this->processor_data['processor_params']['merchant_id'],
            'ACCOUNT' => $this->processor_data['processor_params']['account'],
            'AUTO_SETTLE_FLAG' => (int) ($this->processor_data['processor_params']['settlement'] == 'auto'),
            'CURRENCY' => $currency_settings['currency_code'],
            'AMOUNT' => fn_format_price(
                    $this->order_info['total'] / $currency_settings['coefficient'],
                    $currency_settings['currency_code']
                ) * 100,
            'SHIPPING_CO' => $this->order_info['s_country'],
            'BILLING_CO' => $this->order_info['b_country'],
            'SHIPPING_CODE' => substr($shipping_zipcode, 0, 5) . '|' . substr($shipping_address, 0, 5),
            'BILLING_CODE' => substr($billing_zipcode, 0, 5) . '|' . substr($billing_address, 0, 5),
            'TIMESTAMP' => $timestamp
        );

        $request_data['SHA1HASH'] = sha1(
            strtolower(
                sha1(
                    $request_data['TIMESTAMP'] . '.'
                    . $request_data['MERCHANT_ID'] . '.'
                    . $request_data['ORDER_ID'] . '.'
                    . $request_data['AMOUNT'] . '.'
                    . $request_data['CURRENCY'] . '.'
                    . $this->order_info['payment_info']['card_number']
                )
            ) . '.' . $this->processor_data['processor_params']['secret_word']
        );

        return $request_data;
    }

    public function beginTransaction()
    {
        if (!fn_validate_cc_expiry_month($this->order_info['payment_info']['expiry_month'])) {
            $this->response['order_status'] = 'D';
            $this->response['reason_text'] = __('payments.realex.incorrect_valid_trhu');

            return $this->response;
        }

        if ($this->processor_data['processor_params']['3d_secure'] == 'enabled') {
            if (!in_array($this->card['type'], array('VISA', 'MC', 'AMEX'))) {
                $this->response['order_status'] = 'D';
                $this->response['reason_text'] = __('payments.realex.wrong_card_type');

                return $this->response;
            }

            return $this->begin3DSecureTransaction();
        } else {
            return $this->performNon3DSecureTransaction();
        }
    }

    /**
     * Begins 3DSecure transaction.
     * This method is called right after checkout.
     *
     * @return array Payment processor response
     */
    protected function begin3DSecureTransaction()
    {
        // Send 3DSecure verify-enrolled XML request to Realex with card details
        // Realex check the Visa or Mastercard Directory to see if the card is enrolled in the 3DSecure program
        $verifyenrolled_response = simplexml_load_string($this->send3DSecureVerifyEnrolledRequest());
        $this->response['payments.realex.3d_secure_message'] = "3DS Verifyenrolled request: " . (string) $verifyenrolled_response->message;

        // Yes - card is enrolled in the 3DSecure program.
        // Realex send the URL of the cardholder’s bank ACS (Access Control Server - this is the webpage that
        // the cardholder uses to enter their password). Also included is the PAReq (this is needed by the ACS).
        if ((string) $verifyenrolled_response->result == '00' && (string) $verifyenrolled_response->enrolled == 'Y') {
            // We redirect user to ACS
            $this->redirected_to_acs = true;
            fn_create_payment_form(
                (string) $verifyenrolled_response->url,
                array(
                    'PaReq' => (string) $verifyenrolled_response->pareq,
                    'TermUrl' => fn_url(
                        "payment_notification.process&payment=realex_remote&order_id={$this->order_info['order_id']}&timestamp={$this->request_data['TIMESTAMP']}",
                        AREA,
                        'current'
                    )
                ),
                'Realex Payments',
                false
            );
        } // Card is not enrolled
        elseif ((string) $verifyenrolled_response->result == '110' && (string) $verifyenrolled_response->enrolled == 'N') {
            $this->authorizeTransaction(self::TRANSACTION_MERCHANT_3DSECURE);
        }
        // If a merchant is using 3DSecure for Visa and Mastercard but not for American Express
        // the following error will come back in response to the verify-enrolled request.
        // What we recommend is that if this happens, the transaction can proceed directly to authorisation.
        elseif((string) $verifyenrolled_response->result == '503') {
            $this->performNon3DSecureTransaction();
        }
        elseif (
            // The enrolled status could not be verified
            (string) $verifyenrolled_response->enrolled == 'U'
            // Invalid response from ACS server
            || ((int) $verifyenrolled_response->result >= 500 && (int) $verifyenrolled_response->result < 600)
            // Card scheme directory server may be unavailable
            || (string) $verifyenrolled_response->result == '220'
        ) {
            if (!$this->getIsLiabilityShiftRequired()) {
                $this->authorizeTransaction(self::TRANSACTION_NON_3DSECURE);
            }
        }

        return $this->response;
    }

    protected function send3DSecureVerifyEnrolledRequest()
    {
        $data = $this->request_data;
        $request = <<<EOT
<request timestamp="{$data['TIMESTAMP']}" type="3ds-verifyenrolled">
<merchantid>{$data['MERCHANT_ID']}</merchantid>
<account>{$data['ACCOUNT']}</account>
<orderid>{$data['ORDER_ID']}</orderid>
<amount currency="{$data['CURRENCY']}">{$data['AMOUNT']}</amount>
<card>
    <number>{$this->card['number']}</number>
    <expdate>{$this->card['exp_month']}{$this->card['exp_year']}</expdate>
    <type>{$this->card['type']}</type>
    <chname>{$this->card['cardholder_name']}</chname>
</card>
<sha1hash>{$data['SHA1HASH']}</sha1hash>
</request>
EOT;

        Registry::set('log_cut_data', array('card', 'sha1hash'));
        $response_data = Http::post(
            self::REALEX_REMOTE_3DSECURE_URL,
            $request,
            array(
                'headers' => array(
                    'Content-type: text/xml',
                    'Connection: close'
                )
            )
        );

        return $response_data;
    }

    protected function authorizeTransaction($transaction_type, $mpi_data = array())
    {
        $mpi_data['eci'] = $this->getEciCode($transaction_type);
        $auth_response = simplexml_load_string($this->send3DSecureAuthRequest($mpi_data));

        $success_payment = false;
        if ((string) $auth_response->result == '00') {
            $this->response['order_status'] = 'P';
            $this->response['payments.realex.transaction_pasref'] = (string) $auth_response->pasref;
            $this->response['payments.realex.liability_shift'] = in_array(
                $transaction_type,
                array(self::TRANSACTION_FULL_3DSECURE, self::TRANSACTION_MERCHANT_3DSECURE)
            ) ? 'Yes' : 'No';
            $this->response['reason_text'] = '';
            $success_payment = true;
        } else {
            $this->response['order_status'] = 'D';
        }

        $this->response['payments.realex.result_message'] = "3DS Auth request: " . (string) $auth_response->message;
        if (isset($mpi_data['eci'])) {
            $this->response['payments.realex.eci'] = $mpi_data['eci'];
        }
        if (isset($mpi_data['cavv'])) {
            $this->response['payments.realex.cavv'] = $mpi_data['cavv'];
        }
        if (isset($mpi_data['xid'])) {
            $this->response['payments.realex.xid'] = $mpi_data['xid'];
        }

        $this->fillPaymentInformationFields($auth_response);

        return $success_payment;
    }

    protected function getEciCode($transaction_type)
    {
        switch ($transaction_type) {
            case self::TRANSACTION_FULL_3DSECURE:
                return ($this->card['type'] == 'VISA' || $this->card['type'] == 'AMEX') ? 5 : 2;
                break;
            case self::TRANSACTION_MERCHANT_3DSECURE:
                return ($this->card['type'] == 'VISA' || $this->card['type'] == 'AMEX') ? 6 : 1;
                break;
            case self::TRANSACTION_NON_3DSECURE:
                return ($this->card['type'] == 'VISA' || $this->card['type'] == 'AMEX') ? 7 : 0;
                break;
        }

        return null;
    }

    /**
     * Perfoms transaction authorization request
     *
     * @param array $mpi_data
     *
     * @return mixed
     */
    protected function send3DSecureAuthRequest($mpi_data = array())
    {
        $mpi = '';
        foreach ($mpi_data as $name => $value) {
            $mpi .= "<{$name}>{$value}</{$name}>\n";
        }
        if (!empty($mpi)) {
            $mpi = "<mpi>\n$mpi</mpi>";
        }

        $request = <<<REQUEST
    <request timestamp="{$this->request_data['TIMESTAMP']}" type="auth">
        <merchantid>{$this->request_data['MERCHANT_ID']}</merchantid>
        <account>{$this->request_data['ACCOUNT']}</account>
        <orderid>{$this->request_data['ORDER_ID']}</orderid>
        <amount currency="{$this->request_data['CURRENCY']}">{$this->request_data['AMOUNT']}</amount>
        <card>
            <number>{$this->card['number']}</number>
            <expdate>{$this->card['exp_month']}{$this->card['exp_year']}</expdate>
            <chname>{$this->card['cardholder_name']}</chname>
            <type>{$this->card['type']}</type>
            <cvn>
                <number>{$this->card['cvv2']}</number>
                <presind>1</presind>
            </cvn>
        </card>
        <autosettle flag="{$this->request_data['AUTO_SETTLE_FLAG']}" />
        $mpi
        <tssinfo>
            <custnum>{$this->order_info['user_id']}</custnum>
            <custipaddress>{$this->order_info['ip_address']}</custipaddress>
            <address type="billing">
                <code>{$this->request_data['BILLING_CODE']}</code>
                <country>{$this->request_data['BILLING_CO']}</country>
            </address>
            <address type="shipping">
                <code>{$this->request_data['SHIPPING_CODE']}</code>
                <country>{$this->request_data['SHIPPING_CO']}</country>
            </address>
        </tssinfo>
        <sha1hash>{$this->request_data['SHA1HASH']}</sha1hash>
    </request>
REQUEST;

        Registry::set('log_cut_data', array('card', 'sha1hash'));
        $response_data = Http::post(
            self::REALEX_REMOTE_3DSECURE_URL,
            $request,
            array(
                'headers' => array(
                    'Content-type: text/xml',
                    'Connection: close'
                )
            )
        );

        return $response_data;
    }

    public function getIsLiabilityShiftRequired()
    {
        return ($this->processor_data['processor_params']['liability_shift_required'] == 'yes');
    }

    protected function performNon3DSecureTransaction()
    {
        $post = $this->request_data;

        $auth_request = <<<EOT
    <request timestamp="{$post['TIMESTAMP']}" type="auth">
        <merchantid>{$post['MERCHANT_ID']}</merchantid>
        <account>{$post['ACCOUNT']}</account>
        <orderid>{$post['ORDER_ID']}</orderid>
        <amount currency="{$post['CURRENCY']}">{$post['AMOUNT']}</amount>
        <card>
            <number>{$this->card['number']}</number>
            <expdate>{$this->card['exp_month']}{$this->card['exp_year']}</expdate>
            <chname>{$this->card['cardholder_name']}</chname>
            <type>{$this->card['type']}</type>
            <cvn>
                <number>{$this->card['cvv2']}</number>
                <presind>1</presind>
            </cvn>
        </card>
        <autosettle flag="{$post['AUTO_SETTLE_FLAG']}" />
        <tssinfo>
            <custnum>{$this->order_info['user_id']}</custnum>
            <custipaddress>{$this->order_info['ip_address']}</custipaddress>
            <address type="billing">
                <code>{$post['BILLING_CODE']}</code>
                <country>{$post['BILLING_CO']}</country>
            </address>
            <address type="shipping">
                <code>{$post['SHIPPING_CODE']}</code>
                <country>{$post['SHIPPING_CO']}</country>
            </address>
        </tssinfo>
        <sha1hash>{$post['SHA1HASH']}</sha1hash>
    </request>
EOT;
        Registry::set('log_cut_data', array('card', 'sha1hash'));
        $auth_response = simplexml_load_string(
            Http::post(
                self::REALEX_REMOTE_NON_3DSECURE_URL,
                $auth_request,
                array(
                    'headers' => array(
                        'Content-type: text/xml',
                        'Connection: close'
                    )
                )
            )
        );

        if ((string) $auth_response->result != '00') {
            $this->response['order_status'] = 'D';
            $this->response['payments.realex.result_message'] = (string) $auth_response->message;
        } else {
            $this->response['order_status'] = 'P';
            $this->response['payments.realex.transaction_pasref'] = (string) $auth_response->pasref;
            $this->response['payments.realex.result_message'] = (string) $auth_response->message;
            $this->response['reason_text'] = '';
        }
        $this->fillPaymentInformationFields($auth_response);

        return $this->response;
    }

    protected function fillPaymentInformationFields($response)
    {
        $avs = array(
            'M' => __('payments.realex.avs.matched'),
            'N' => __('payments.realex.avs.not_matched'),
            'I' => __('payments.realex.avs.problem_with_check'),
            'U' => __('payments.realex.avs.unable_to_check'),
            'P' => __('payments.realex.avs.partial_match'),
        );

        $this->response['payments.realex.transaction_order_id'] = (string) $response->orderid;
        $this->response['payments.realex.result_code'] = (string) $response->result;
        $this->response['payments.realex.tss_result'] = (string) $response->tss->result;

        $avs_address = (string) $response->avsaddressresponse;
        $avs_postcode = (string) $response->avspostcoderesponse;

        if (isset($avs[$avs_address])) {
            $this->response['payments.realex.avs_address'] = $avs[$avs_address];
        }
        if (isset($avs[$avs_postcode])) {
            $this->response['payments.realex.avs_postcode'] = $avs[$avs_postcode];
        }
    }

    public function endTransaction()
    {
        $this->end3DSecureTransaction();
    }

    /**
     * Ends 3DSecure transaction.
     * This method is called after bank ACS redirects user back to merchant site.
     *
     * @return array Payment processor response
     */
    protected function end3DSecureTransaction()
    {
        $verifysig_response = simplexml_load_string($this->send3DSecureVerifySignatureRequest($_REQUEST['PaRes']));
        $verifysig_result = (string) $verifysig_response->result;
        $verifysig_3dsecure_status = (string) $verifysig_response->threedsecure->status;

        $success_payment = false;

        $mpi_data = array(
            'cavv' => (string) $verifysig_response->threedsecure->cavv,
            'xid' => (string) $verifysig_response->threedsecure->xid,
            'eci' => (string) $verifysig_response->threedsecure->eci
        );

        // Successful authentication
        if ($verifysig_result == '00' && $verifysig_3dsecure_status == 'Y') {
            $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Authentication successful';
            $success_payment = $this->authorizeTransaction(self::TRANSACTION_FULL_3DSECURE, $mpi_data);
        } // Issuing bank with attempt ACS so it accepts liability shift
        elseif ($verifysig_result == '00' && $verifysig_3dsecure_status == 'A') {
            $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Cardholder not Enrolled or Authentication Attempt Acknowledged';
            $success_payment = $this->authorizeTransaction(self::TRANSACTION_MERCHANT_3DSECURE, $mpi_data);
        } elseif (
            // Authentication unavailable
            (
                $verifysig_result == '00'
                && $verifysig_3dsecure_status == 'U'
                && $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Authentication Unavailable'
            )
            //  Incorrect password entered
            || (
                $verifysig_result == '00'
                && $verifysig_3dsecure_status == 'N'
                && $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Incorrect Password'
            )
            // Invalid response from ACS
            || (
                ($verifysig_result >= 500 && $verifysig_result < 600)
                && $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Invalid response from ACS'
            )
        ) {
            if (!$this->getIsLiabilityShiftRequired()) {
                $success_payment = $this->authorizeTransaction(self::TRANSACTION_NON_3DSECURE, $mpi_data);
            }
        } // Enrolled but Invalid Response from ACS. We block this transaction.
        elseif ($verifysig_result == '110') {
            $this->response['payments.realex.3d_secure_message'] = '3DS Verifysig request: Enrolled but Invalid Response from ACS';
        }

        fn_finish_payment($this->order_info['order_id'], $this->response);

        if ($success_payment) {
            fn_order_placement_routines('route', $this->order_info['order_id'], false);
        } else {
            fn_set_notification(
                'E',
                false,
                "Your transaction was unsuccessful. There was a problem with your order, please try again or contact the store administrator."
            );
            fn_order_placement_routines('checkout_redirect', $this->order_info['order_id'], false);
        }
    }

    /**
     * @param string $pares PaRes value returned from ACS
     *
     * @return string Response body
     */
    protected function send3DSecureVerifySignatureRequest($pares)
    {
        // 3DS Verifysig request doesnt need card number to be sent
        $sha1_hash = sha1(
            sha1(
                $this->request_data['TIMESTAMP'] . '.'
                . $this->request_data['MERCHANT_ID'] . '.'
                . $this->request_data['ORDER_ID'] . '.'
                . $this->request_data['AMOUNT'] . '.'
                . $this->request_data['CURRENCY'] . '.'
            )
            . '.' . $this->processor_data['processor_params']['secret_word']
        );

        $request = <<<REQUEST
<request timestamp="{$this->request_data['TIMESTAMP']}" type="3ds-verifysig">
    <merchantid>{$this->request_data['MERCHANT_ID']}</merchantid>
    <account>{$this->request_data['ACCOUNT']}</account>
    <orderid>{$this->request_data['ORDER_ID']}</orderid>
    <amount currency="{$this->request_data['CURRENCY']}">{$this->request_data['AMOUNT']}</amount>
    <pares>{$pares}</pares>
    <sha1hash>{$sha1_hash}</sha1hash>
</request>
REQUEST;

        Registry::set('log_cut_data', array('card', 'sha1hash'));
        $response_data = Http::post(
            self::REALEX_REMOTE_3DSECURE_URL,
            $request,
            array(
                'headers' => array(
                    'Content-type: text/xml',
                    'Connection: close'
                )
            )
        );

        return $response_data;
    }

    /**
     * @return boolean
     */
    public function isRedirectedToACS()
    {
        return $this->redirected_to_acs;
    }
}

if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode == 'process' && isset($_REQUEST['order_id'])) {
        $order_id = (int) $_REQUEST['order_id'];

        $realex_payment = new RealexRemotePaymentMethod(
            $_SESSION[$order_id . '_processor_data'],
            $_SESSION[$order_id . '_order_info']
        );
        $realex_payment->endTransaction($order_id);
    }
} else {
    /**
     * @var array $order_info
     * @var array $processor_data
     */
    $_SESSION[$order_info['order_id'] . '_order_info'] = $order_info;
    $_SESSION[$order_info['order_id'] . '_processor_data'] = $processor_data;
    $realex_payment = new RealexRemotePaymentMethod($processor_data, $order_info);
    $pp_response = $realex_payment->beginTransaction();

    if (!$realex_payment->isRedirectedToACS()) {
        unset($_SESSION[$order_id . '_processor_data'], $_SESSION[$order_id . '_order_info']);
    }
}