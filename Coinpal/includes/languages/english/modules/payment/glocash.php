<?php
  define('MODULE_PAYMENT_GLOCASH_TEXT_ADMIN_TITLE', 'Coinpal');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_TITLE', 'Coinpal');
  
  
  
  if( IS_ADMIN_FLAG === true ){
      define('MODULE_PAYMENT_GLOCASH_TEXT_DESCRIPTION', 
              '<strong>Coinpal</strong><br />'.
              'Click <a href="https://www.coinpal.io/" target="_blank">here</a> to get your coinapl account.'
              );
  } else {
      define('MODULE_PAYMENT_GLOCASH_TEXT_DESCRIPTION', '<strong>Coinpal</strong>');
  }
  

  
  
  define('MODULE_PAYMENT_GLOCASH_ENTRY_STATE', 'Notification:');
  define('MODULE_PAYMENT_GLOCASH_ENTRY_MODATE', 'Date:');

  define('MODULE_PAYMENT_GLOCASH_MARK_BUTTON_IMG', DIR_WS_MODULES . 'payment/glocash/Coinpal.png');
  define('MODULE_PAYMENT_GLOCASH_MARK_BUTTON_ALT', 'Checkout with Glocash');

  define('MODULE_PAYMENT_GLOCASH_ACCEPTANCE_MARK_TEXT', MODULE_PAYMENT_GLOCASH_CURR_TITLE);


  //define('MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_LOGO', '<span class="smallText">' . MODULE_PAYMENT_GLOCASH_ACCEPTANCE_MARK_TEXT . '</span><br/>' . '<img src="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_IMG . '" width=200 alt="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_ALT . '" /> &nbsp;');
  
  define('MODULE_PAYMENT_GLOCASH_TEXT_CATALOG_LOGO', '<div style="display: inline-block;margin-left: 0px;vertical-align: middle;"><span class="smallText" style="vertical-align: text-bottom;line-height: 40px;">' . MODULE_PAYMENT_GLOCASH_ACCEPTANCE_MARK_TEXT . '</span>' . '<img style="margin-left:40px;" src="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_IMG . '" width=130 alt="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_GLOCASH_MARK_BUTTON_ALT . '" /></div> &nbsp;');


  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_1_1', 'Enable GLOCASH Module');  
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_1_2', 'Do you want to accept GLOCASH payments?');  
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_2_1', 'Merchant Id');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_2_2', 'Merchant Id');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_3_1', 'Secret key');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_3_2', 'Secret key');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_10_1', 'Method Title');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_10_2', 'Method Title');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_11_1', '3DS'); 
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_11_2', '3D－Secure'); 
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_12_1', 'Terminal'); 
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_12_2', 'Terminal'); 
  
  
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_4_1', 'Payment Zone');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_4_2', 'If a zone is selected, only enable this payment method for that zone.');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_5_1', 'Set New Order Status');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_5_2', 'Set the init status of orders made with this payment module<br /> (Pending recommended)<br />');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_6_1', 'Set Order Status');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_6_2', 'Set the status of orders made with this payment module that have completed payment<br />(Processing recommended)<br />');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_7_1', 'Set Failed Order Status');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_7_2', 'Set the status of orders made with this payment module that have failed payment<br />(Failed recommended)');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_8_1', 'Sort order of display');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_8_2', 'Sort order of display. Lowest is displayed first.');
 
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_9_1', 'GLOCASH Transaction System');
  define('MODULE_PAYMENT_GLOCASH_TEXT_CONFIG_9_2', 'Live or Sandbox');

  
?>