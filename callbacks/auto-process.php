<?
require('../../../wp-load.php');

$is_paid = $_GET['is_paid'];
$isLive =  $_GET['live'];
$order_id = $_GET['invoice_id'];
$f_api = $_GET['fawry_api'];
$arr_api = explode("=", $f_api);

$t = $_REQUEST['chargeResponse'];
$arr = explode("\\\\", $t);

$ord_id = str_replace('\\"', "", $arr[9]);
$faw_id = str_replace('\\"', "", $arr[21]);
$order = new WC_Order($order_id);

if ($order_id > 0) {
  if ($is_paid == 1) {
    $order->update_status('processing', 'Payment reseved successfuly ');
  } else {
    $order->update_status('failed', 'Payment Failed ');
  }
}

$url = get_site_url();
wp_redirect($url);
exit;
