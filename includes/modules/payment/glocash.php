<?php

class glocash
{

    var $code, $title, $description, $enabled;

    /**
     * order status setting for pending orders
     *
     * @var int
     */
    var $order_pending_status = 1;

    /**
     * order status setting for completed orders
     *
     * @var int
     */
    var $order_status = DEFAULT_ORDERS_STATUS_ID;
    
    // class constructor
    function glocash ()
    {
        global $order;
        $this->code = 'glocash';
        if ($_GET['main_page'] != '') {
            $this->title = MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_TITLE; // Payment
                                                                      // Module
                                                                      // title
                                                                      // in
                                                                      // Catalog
        } else {
            $this->title = MODULE_PAYMENT_GLOCASH_TEXT_ADMIN_TITLE; // Payment
                                                                    // Module
                                                                    // title in
                                                                    // Admin
        }
        $this->description = MODULE_PAYMENT_GLOCASH_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_GLOCASH_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_GLOCASH_STATUS == 'True') ? true : false);
        if ((int) MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID;
        }
        if (is_object($order)){
            $this->update_status();
        }
            
        if (strtolower(MODULE_PAYMENT_GLOCASH_HANDLER) != 'sandbox') {
            $this->form_action_url = 'https://pay.glocash.com/';
        } else {
            $this->form_action_url = 'https://sandbox.glocash.com/';
        }
        $this->form_action_url .= "gateway/payment/index";
        
    }
    
    // class methods
    function update_status ()
    {
        global $order, $db;
        
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_GLOCASH_ZONE > 0)) {
            $check_flag = false;
            $check_query = $db->Execute(
                    "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES .
                             " where geo_zone_id = '" .
                             MODULE_PAYMENT_GLOCASH_ZONE .
                             "' and zone_country_id = '" .
                             $order->billing['country']['id'] .
                             "' order by zone_id");
            while (! $check_query->EOF) {
                if ($check_query->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check_query->fields['zone_id'] ==
                         $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }
            
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation ()
    {
        return false;
    }

    function selection ()
    {
        return array(
                'id' => $this->code,
                'module' => MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_LOGO,
                'icon' => MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_LOGO
        );
    }

    function pre_confirmation_check ()
    {
        global $order, $messageStack, $zco_notifier, $db;
        
        //force zen cart to load existing order without creating dumplicate order
        if ( isset($_SESSION['order_id']) && ($_SESSION['cart']->cartID == $_SESSION['old_cart_id']) && ($_SESSION['old_cur'] == $_SESSION['currency'])) {
            $order_id = $_SESSION['order_id'];
        } else {
            if ( isset($_SESSION['order_id'])) {
                $order_id = $_SESSION['order_id'];
                // 同一个购物车信息已经生成的订单则删除，重新生成订单
                // 但注意付款完成要unset $_SESSION['order_id']，否则这里会导致删除同一个客户的前一张已提交的订单
                $db->Execute('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
                $db->Execute('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
                $db->Execute('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
                $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
                $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
                $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
                
                $this->gcLog(__FUNCTION__." order id:$order_id deleted.");
            }
            $order = new order();
            $order->info['order_status'] = $this->order_status;//init status,pending
            require_once(DIR_WS_CLASSES . 'order_total.php');
            $order_total_modules = new order_total();
            $order_totals = $order_total_modules->process();
            $order_id = $order->create($order_totals);
            $order->create_add_products($order_id, 2);
            $_SESSION['order_id'] = $order_id;
            $_SESSION['old_cart_id'] = $_SESSION['cart']->cartID;//if customer add or remove item,update qty
            $_SESSION['old_cur'] = $_SESSION['currency']; //if the customer swich the currency
        } // generate order block end, to be compatible with previous version,we do dump data to widelypay talbe(but not manditory)
        
        $ret = $this->getGCPaymentUrl($order, (int)$order_id);
        if($ret['return']){
            $redirectUrl = $ret['msg'];
            $this->form_action_url = $redirectUrl;
        }else{
            $messageStack->add_session('checkout_payment', "REQ_ERROR:".$ret['msg'], 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'NONSSL', true, false));
        }
        
    }

    function confirmation ()
    {
        return array(
                'title' => MODULE_PAYMENT_GLOCASH_TEXT_DESCRIPTION
        );
    }

    function process_button ()
    {
        global $db, $currencies,$messageStack;
        return '<!-- glocash -->';
    }

    function before_process ()
    {
        global $db, $order_total_modules, $messageStack;
        $this->gcLog(__FUNCTION__." POST:".json_encode($_POST));
        
        $valid = false;
        // {"REQ_INVOICE":"ZC30","CUS_EMAIL":"xxx@qq.com","BIL_METHOD":"C01","TNS_UTIMES":"1547624556.9252","TNS_GCID":"C01AL129NYZLNZB4","PGW_PRICE":"13.09","PGW_CURRENCY":"EUR","FDL_DECISION":"ACP","BIL_STATUS":"paid","REQ_TIMES":"1547624575","REQ_SIGN":"d3fd25eb44580b35ead2052a50dfc1bf22e7e3a7bc1cdca1742736dde979facc"}
        $param = $_POST;
        try {
            $valid = $this->validatePSNSIGN($param);
        }catch (\Exception $e){
            $this->gcLog(__FUNCTION__." Exception:".$e->getMessage());
        }
        
        if(!$valid){
            $this->gcLog(__FUNCTION__." validate params fail.");
            $messageStack->add_session('checkout_payment', 'Unfortunately, the confirmation of your payment failed(validate sign failed). Please contact your merchant for clarification.', 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'NONSSL', true, false));
            
            return false;
        }
        
        $orderId = time().uniqid();
        if(!empty($param['REQ_INVOICE'])){
            $orderId = $param['REQ_INVOICE'];
            $orderId = str_replace("ZC", "", $orderId);
        }
        
        $payStatus = $param['BIL_STATUS'];
        $this->gcLog(__FUNCTION__." glocash-info-ids-".$orderId.'--'.$payStatus);
        switch ($payStatus) {
            case "failed":
                $messageStack->add_session('checkout_payment', 'Unfortunately, the confirmation of your payment failed. Please contact your merchant for clarification.', 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'NONSSL', true, false));
                break;
            case 'pending':
            case "unpaid":
            case 'paid':
                unset($_SESSION['order_id']);
                unset($_SESSION['old_cart_id']);
                unset($_SESSION['old_cur']);
                $_SESSION['cart']->reset(true);
                break;
            default:
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'NONSSL', true, false));
                break;
        }
        
        
        $this->after_process();
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS));
        return true;
        //stop as we produce customer order before customer leave our store
        
    }

    function after_process ()
    {
        global $insert_id, $db;
        $_SESSION['order_created'] = '';
        return false;
    }

    function output_error ()
    {
        return false;
    }

    function check ()
    {
        global $db;
        if (! isset($this->_check)) {
            $check_query = $db->Execute(
                    "select configuration_value from " . TABLE_CONFIGURATION .
                             " where configuration_key = 'MODULE_PAYMENT_GLOCASH_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install ()
    {
        global $db, $language, $module_type;
        if (! defined('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_1_1'))
            include (DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] .
                     '/modules/' . $module_type . '/' . $this->code . '.php');
        
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_1_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_STATUS', 'False', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_1_2 .
                         "', '9', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_2_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_SELLER_EMAIL', '', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_2_2 .
                         "', '9', '2', now())");
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_3_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_SECRET_KEY', '', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_3_2 .
                         "', '9', '3', now())");
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_4_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_ZONE', '0', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_4_2 .
                         "', '9', '4', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
        
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_5_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID', '1', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_5_2 .
                         "', '9', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_6_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_PROCESSING_STATUS_ID', '2', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_6_2 .
                         "', '9', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_7_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_ORDER_STATUS_PAY_FAIL_ID', '2', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_7_2 .
                         "', '9', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_8_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_SORT_ORDER', '0', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_8_2 .
                         "', '9', '8', now())");
        $db->Execute(
                "insert into " . TABLE_CONFIGURATION .
                         " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_9_1 .
                         "', 'MODULE_PAYMENT_GLOCASH_HANDLER', 'Live', '" .
                         MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_9_2 .
                         "', '9', '9', 'zen_cfg_select_option(array(\'Live\', \'Sandbox\'), ', now())");
        
        $this->createTable();
    }

    function remove ()
    {
        global $db;
        $db->Execute(
                "delete from " . TABLE_CONFIGURATION .
                         " where configuration_key LIKE  'MODULE_PAYMENT_GLOCASH%'");
        $this->removeTable();
    }

    function keys ()
    {
        return array(
                'MODULE_PAYMENT_GLOCASH_STATUS',
                'MODULE_PAYMENT_GLOCASH_SELLER_EMAIL',
                'MODULE_PAYMENT_GLOCASH_SECRET_KEY',
                'MODULE_PAYMENT_GLOCASH_ZONE',
                'MODULE_PAYMENT_GLOCASH_PROCESSING_STATUS_ID',
                'MODULE_PAYMENT_GLOCASH_ORDER_STATUS_PAY_FAIL_ID',
                'MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID',
                'MODULE_PAYMENT_GLOCASH_SORT_ORDER',
                'MODULE_PAYMENT_GLOCASH_HANDLER'
        );
    }

    function arg_sort ($array)
    {
        ksort($array);
        reset($array);
        return $array;
    }
    
    // 实现多种字符解码方式
    function charset_decode ($input, $_input_charset, $_output_charset = "utf-8")
    {
        $output = "";
        if (! isset($_input_charset))
            $_input_charset = $this->_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, 
                    $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else
            die("sorry, you have no libs support for charset changes.");
        return $output;
    }
    
    
    // 验证 付款结果/PSN 提交的REQ_SIGN 是否合法
    function validatePSNSIGN($param){
        // REQ_SIGN = SHA256 ( SECRET_KEY + REQ_TIMES + REQ_EMAIL + CUS_EMAIL + TNS_GCID + BIL_STATUS + BIL_METHOD + PGW_PRICE + PGW_CURRENCY )
        $sign = hash("sha256",
                MODULE_PAYMENT_GLOCASH_SECRET_KEY.
                $param['REQ_TIMES'].
                MODULE_PAYMENT_GLOCASH_SELLER_EMAIL.
                $param['CUS_EMAIL'].
                $param['TNS_GCID'].
                $param['BIL_STATUS'].
                $param['BIL_METHOD'].
                $param['PGW_PRICE'].
                $param['PGW_CURRENCY']
        );
    
        $this->gcLog(__FUNCTION__." sign:".$sign);
    
        return $sign==$param['REQ_SIGN'];
    }
    
    /**
     * 生成gc支付url,跳转用
     * $price 支付的金额
     * $currency 支付的货币单位 默认USD
     */
    function getGCPaymentUrl($order, $orderId) {
        $msg= array();
        $msg['return']= false;
    
        //获取卖家注册的邮箱地址
        $email = $order->customer['email_address'];
    
        $orderId= "ZC".$orderId;
        $currency = $order->info['currency'];
        // $currency = $_SESSION['currency'];
        
        $grandTotal = $order->info['total'];
    
        //货币转大写
        $currency = strtoupper($currency);
        $BIL_METHOD = 'C01';
    
        $returnUrl = zen_href_link(FILENAME_CHECKOUT_PROCESS);
        $notifyUrl = zen_href_link('glocash_notify.php','', 'NONSSL',false,false,true);
        
        //这里生成url需要的各种参数呗
        $param= array(
                'REQ_TIMES'=>time(),
                'REQ_EMAIL'=> MODULE_PAYMENT_GLOCASH_SELLER_EMAIL,
                'REQ_INVOICE'=> $orderId,
                'CUS_EMAIL'=>$email,
                'BIL_PRICE'=>$grandTotal,
                'BIL_CURRENCY'=>$currency,
                'BIL_METHOD'=>$BIL_METHOD,
    
                'URL_SUCCESS'=>$returnUrl,
                'URL_PENDING'=>$returnUrl,
                'URL_FAILED' =>$returnUrl,
                'URL_NOTIFY' =>$notifyUrl,
                'BIL_GOODSNAME'=>'',// 展示在付款页面的商品描述
        );
    
        $param['REQ_SIGN'] = hash("sha256",
                MODULE_PAYMENT_GLOCASH_SECRET_KEY.
                $param['REQ_TIMES'].
                $param['REQ_EMAIL'].
                $param['REQ_INVOICE'].
                $param['CUS_EMAIL'].
                $param['BIL_METHOD'].
                $param['BIL_PRICE'].
                $param['BIL_CURRENCY']
        );
    
        
        $this->gcLog(__FUNCTION__.":".json_encode($param));
        $httpCode = self::paycurl($this->form_action_url, http_build_query($param), $result);
        $data = json_decode($result, true);
        $this->gcLog(__FUNCTION__." paycurl:".json_encode($data));
        if ($httpCode!=200 || empty($data['URL_PAYMENT'])) {
            // 请求失败
            $msg['msg']= $data['REQ_ERROR'];
            return $msg;
        }
    
        //交易号
        $transaction_id = $data['TNS_GCID'];
        //保存流水信息,状态为提交申请,这个类型的,列表就不用显示了吧,属于垃圾信息
    
        $msg['msg'] = $data['URL_PAYMENT'];
        $msg['return'] = true;
        return $msg;
    }
    
    /**
     * 支付curl提交
     * @param $url
     * @param $postData
     * @param $result
     * akirametero
     */
    private function paycurl( $url, $postData, &$result ){
        $options = array();
        if (!empty($postData)) {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = $postData;
        }
        $options[CURLOPT_USERAGENT] = 'Glocash/v2.*/CURL';
        $options[CURLOPT_ENCODING] = 'gzip,deflate';
        $options[CURLOPT_HTTPHEADER] = [
                'Accept: text/html,application/xhtml+xml,application/xml',
                'Accept-Language: en-US,en',
                'Pragma: no-cache',
                'Cache-Control: no-cache'
        ];
        $options[CURLOPT_RETURNTRANSFER] = 1;
        $options[CURLOPT_HEADER] = 0;
        if (substr($url,0,5)=='https') {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode;
    }
    
    function createTable()
    {
        global $db;
        return $db->Execute('
            CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'glocash_log` (
			`id_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` varchar(50) NOT NULL,
            `message` text NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `id_order` (`id_order`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }
    
    function removeTable()
    {
        global $db;
        return $db->Execute('
			DROP TABLE IF EXISTS `' . DB_PREFIX . 'glocash_log`');
    }
    
    
    function gcLog($message){
        error_log(date("[Y-m-d H:i:s]")."\t" .$message ."\r\n", 3, DIR_FS_LOGS.'/gc_response'.date("Y-m-d").'.log');
    }
    
    function gcDbLog($orderId, $param){
        global $db;
        if(is_string($param)){
            $message = $param;
        }else{
            $message = json_encode($param);
        }
    
        $sql = "INSERT INTO `" . DB_PREFIX . "glocash_log`
                (`id_order`, `message`, `date_add`) VALUES ('".$orderId."', :message, '".date("Y-m-d H:i:s", time())."')";
        $sql = $db->bindVars($sql, ':message', $message, 'string');
        return $db->Execute($sql);

    }
}
?>