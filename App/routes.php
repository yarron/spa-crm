<?php

use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Order;
use App\Models\Email;

$app->get(
    '/add_order/site/{site_id}/product/{product}/price/{price}/phone/{phone}/name/{name}',
    function (Request $request, Response $response, $args
    ) {
        $model = new Order();
        $data = $model->addOrder($args);

        if ($data['order_id']) {
            $model->addGeo($data['order_id']);
        }

        return $response->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
});

$app->get(
    '/add_additional/product/{product}/price/{price}/id/{id}',
    function (Request $request, Response $response, $args
    ) {
        $model = new Order();
        $data = $model->addAdditional($args);

        return $response->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));

});


$app->get('/send_email', function (Request $request, Response $response) {
    $modelOrder = new Order();
    $modelEmail = new Email();
    $orders = $modelOrder->getOrdersByNotSend();
    $additionals = $modelOrder->getAdditionalByNotSend();

    $result = [
        'orders' => [],
        'additionals' => [],
    ];

    if ($orders['success'] && count($orders['data'])) {
        $result['orders'] = $modelEmail->sendEmailOrders($orders['data']);
        $modelOrder->updateOrderSend($result['orders']);
    }

    if ($additionals['success'] && count($additionals['data'])) {
        $result['additionals'] = $modelEmail->sendEmailOrders($additionals['data'], true);
        $modelOrder->updateAdditionalSend($result['additionals']);
    }

    return $response->withStatus(200)
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($result));
});