<?
require('wp-load.php');

$f_api = $_GET['fawry_api'];
$arr_api = explode("=", $f_api);
$order_id = (int) $arr_api[1];
$order = new WC_Order($order_id);


if ($order_id > 0) {
    $pay_id = (int) $_GET['fawryRefNo'];
    $fawryRefNumber = $_GET['fawryRefNumber'];

    if (isset($_GET['fawryRefNumber'])) {
        $order->update_status('processing', 'Payment reseved successfuly Bill Ref Number ID: ' . $fawryRefNumber . ' , now order in proccess ');
    } else {
        $order->update_status('failed', 'Payment Failed ');
    }
}

$url = get_site_url();
//print $url;
wp_redirect($url);
exit;
