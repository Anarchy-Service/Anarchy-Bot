<?php
declare(strict_types=1);
$input = file_get_contents("php://input");
if (isset($input)) {
    $telegram_ip_ranges = [
        ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'],
        ['lower' => '91.108.4.0',    'upper' => '91.108.7.255'],
    ];
    $ip_dec = (float) sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
    $ok = false;
    foreach ($telegram_ip_ranges as $telegram_ip_range) {
        $lower_dec = (float) sprintf("%u", ip2long($telegram_ip_range['lower']));
        $upper_dec = (float) sprintf("%u", ip2long($telegram_ip_range['upper']));
        if ($ip_dec >= $lower_dec and $ip_dec <= $upper_dec) {
            $ok = true;
            break;
        }
    }
    if ($ok) {
        $obj = json_decode($input);
        $type = $obj->message->chat->type ?? $obj->callback_query->message->chat->type;
        if (function_exists('exec')) {
            if (!is_dir('temp')) {
                mkdir('temp', 0700);
            }
            $temp = "temp/.up_" . rand(0, 1000) . "" . time();
            file_put_contents($temp, $input);
            if ($type == 'private') {
                exec("php private.php $temp > /dev/null &");
            } elseif ($type == 'channel') {
                exec("php channel.php $temp > /dev/null &");
            } elseif ($type == 'supergroup' || $type == 'group') {
                exec("php group.php $temp > /dev/null &");
            }
        } /*not recommended beater way is use exec function */
        else {
            if ($type == 'private') {
                require_once 'private.php';
            } elseif ($type == 'channel') {
                require_once 'channel.php';
            } elseif ($type == 'supergroup' || $type == 'group') {
                require_once 'group.php';
            }
        }
    }
}
