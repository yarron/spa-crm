<?php

namespace App\Models;
use App\Lib\Config;

class Email
{
    protected $mailer;

    function __construct() {
        // Create the Transport
        $transport = (new \Swift_SmtpTransport(
            Config::read('smtp.host'),
            Config::read('smtp.port'),
            Config::read('smtp.secure')
        ))
            ->setUsername(Config::read('smtp.username'))
            ->setPassword(Config::read('smtp.password'));

        $this->mailer = new \Swift_Mailer($transport);
    }

    public function sendEmailOrders($orders, $is_additional = false) {
        $result = [];

        foreach($orders as $order) {
            $body = $this->getBodyForEmail($order, $is_additional);
            $message = (new \Swift_Message(Config::read('smtp.subject') . ' #'.$order['id']))
                ->setFrom([Config::read('smtp.from_email') => Config::read('smtp.from_name')])
                ->setTo([Config::read('smtp.to_email') => Config::read('smtp.to_name')])
                ->setBody($body);
            $message->setContentType("text/html");

            if ($this->mailer->send($message)) {
                $result[] = $order['id'];
            }
        }

        return $result;
    }

    private function getBodyForEmail($order, $is_additional) {
        $additional = $is_additional === true ? 'Да' : 'Нет';

        $body  = "Upsell: <b>{$additional}</b><br /><br />";
        $body .= "Товар: <b>{$order['product']}</b><br />";
        $body .= "Клиент: <b>{$order['fio']}</b><br />";
        $body .= "Тел: <b>{$order['phone']}</b><br />";
        $body .= "Статус: <b>{$order['status']}</b><br />";
        $body .= "Host: <b>{$order['url']}</b><br />";
        $body .= "Referer: <b>{$order['referer']}</b><br />";
        $body .= "Browser: <b>{$order['agent']}</b><br />";
        $body .= "IP: <b>{$order['ip']}</b><br />";
        $body .= "Страна: <b>{$order['country_name']} (код {$order['country_code']})</b><br />";
        $body .= "Город: <b>{$order['city']} (регион {$order['region']})</b><br />";
        $body .= "<a target=\"_blank\" href=\"https://www.google.ru/maps/place/{$order['latitude']}+{$order['longitude']}\">Координаты</a>";
        $body .= ": <b>{$order['latitude']}, {$order['longitude']}</b><br />";

        return $body;
    }
}
