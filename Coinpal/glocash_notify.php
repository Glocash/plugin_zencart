<?php


require 'includes/modules/payment/glocash.php';
require 'includes/application_top.php';


$glocash = new glocash();

$glocash->gcLog("notify:".json_encode($_POST));
$param = $_POST;

$orderId = time().uniqid();
if(!empty($param['orderNo']))
    $orderId = $param['orderNo'];{
    $orderId = str_replace("ZC", "", $orderId);
}
// 垃圾信息暂时也保存到db

$glocash->gcDbLog($orderId,"notify: ".json_encode($param));
$valid = false;
try {
    $valid = $glocash->validatePSNSIGN($param);
}catch (\Exception $e){
    $glocash->gcLog("notify Exception:".$e->getMessage());
	$glocash->gcDbLog($orderId, "notify Exception:".$e->getMessage());
}

if(!$valid){
    $glocash->gcLog("notify validate params fail.orderId:{$orderId}");
	$glocash->gcDbLog($orderId, "notify validate params fail.orderId:{$orderId}");
    http_response_code(200);
    return ;
}

try{
    
    // Retrieve order
    require(DIR_WS_CLASSES . 'order.php');
    $order = new order($orderId);
    
    $orders_query = "SELECT count(1) as counter FROM " . TABLE_ORDERS . "
                 WHERE orders_id = :orderId LIMIT 1 ";
    $orders_query = $db->bindVars($orders_query, ':orderId', $orderId, 'integer');
    $orders = $db->Execute($orders_query);
    $counter = $orders->fields['counter'];
    

    if($counter == 0){// 订单不存在？
        $glocash->gcLog("notify order:{$orderId} not exists.");
		$glocash->gcDbLog($orderId, "notify order:{$orderId} not exists.");
        http_response_code(200);
        return ;
    }

    $order = new order($orderId);
    $grandTotal = $order->info['total'];

    $prefix = '[INFO] ';
    $payStatus = $param['status'];
    $comment = "status {$payStatus}, PGW_MESSAGE:".$param['respMessage'];
    $odComment = "";
    $order_status = null;
    $sendMail = false;
    
    switch ($payStatus) {
        case 'paid':
            $sendMail = true;
            $order_status = MODULE_PAYMENT_GLOCASH_PROCESSING_STATUS_ID;
            $odComment = 'Order payment successful! TNS_GCID:' . $param['TNS_GCID'];
            break;
        case 'pending':
            $order_status = MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID;
            break;
        case "unpaid":
            $order_status = MODULE_PAYMENT_GLOCASH_ORDER_STATUS_ID;
            break;
        case "failed":
            $prefix = '[ERROR] ';
            $order_status = MODULE_PAYMENT_GLOCASH_ORDER_STATUS_PAY_FAIL_ID;
            break;
        default:
            $prefix = '[ERROR] ';
            $order_status = MODULE_PAYMENT_GLOCASH_ORDER_STATUS_PAY_FAIL_ID;
            break;
    }
    $glocash->gcLog("notify glocash-info-ids- ".$order_status.'--'.$orderId.'--'.$payStatus);

	// for backward compatibility prior to v1.5.7;
	if (!function_exists('zen_update_orders_history')){
		//更新订单状态以及添加订单状态历史记录
		$sql_data_array = array (
			'orders_id' => $orderId,
			'orders_status_id' => $order_status,
			'date_added' => 'now()',
			'comments' => $odComment, 
			'customer_notified' => '1'
		);	
		zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		
		$sql_data_array = array('orders_status' => $order_status, 'orders_date_finished' => 'now()');
		zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = ' . (int)$orderId);//更新订单状态
	}
	else{
		zen_update_orders_history($orderId,$odComment,null,$order_status,1);
	}
	
	if($sendMail){
	    $order->send_order_email($order_id, 2);
	}
	
    $glocash->gcDbLog($orderId, $prefix.$comment);
    $glocash->gcDbLog($orderId, "[INFO] changed order status success.");

    header('HTTP/1.1 200 OK');
}catch ( Exception $e ){
    $glocash->gcLog("notify glocash-info-error- ".print_r($e,true));

    $glocash->gcDbLog($orderId, "[ERROR] notify Exception:".$e->getMessage());
    header('HTTP/1.1 200 OK');
}




