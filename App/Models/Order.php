<?php

namespace App\Models;
use App\Lib\Core;
use PDOException;
use PDO;


class Order {
    protected $core;


    function __construct() {
        $this->core = Core::getInstance();
    }

    public function addOrder($params) {
        $sql = "INSERT INTO crm_order (
          spa_id, 
          phone, 
          fio, 
          date_added, 
          product, 
          price
        ) 
        VALUES (
          :site_id, 
          :phone, 
          :fio, 
          :date_added, 
          :product,
          :price
        )";

        $prepare = $this->core->dbh->prepare($sql);
        $prepare->bindParam(':spa_id', $params['site_id'], PDO::PARAM_INT);
        $prepare->bindParam(':phone', $params['phone'], PDO::PARAM_STR);
        $prepare->bindParam(':fio', $params['name'], PDO::PARAM_STR);
        $prepare->bindParam(':date_added', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $prepare->bindParam(':product', $params['product'], PDO::PARAM_STR);
        $prepare->bindParam(':price', $params['price'], PDO::PARAM_INT);

        try {
            $prepare->execute();
            $result = ['success' => true, 'order_id' => $this->core->dbh->lastInsertId()];
        } catch(PDOException $e) {
            $result = ['error' => $e->getMessage(), 'order_id' => 0];
        }

        return $result;
    }


    public function addGeo($order_id) {
        $sql = "INSERT INTO crm_geo (
          order_id, 
          ip,
          referer, 
          agent, 
          country_name,
          country_code,
          city,
          region,
          latitude,
          longitude
        ) 
        VALUES (
          :order_id,
          :ip, 
          :referer, 
          :agent, 
          :country_name,
          :country_code,
          :city,
          :region,
          :latitude,
          :longitude
        )";

        $ip = $_SERVER['REMOTE_ADDR'];
        $referer = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
        $agent = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : '';

        $prepare = $this->core->dbh->prepare($sql);
        $prepare->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $prepare->bindParam(':ip', $ip, PDO::PARAM_STR);
        $prepare->bindParam(':referer', $referer, PDO::PARAM_STR);
        $prepare->bindParam(':agent',  $agent, PDO::PARAM_STR);

        try {
            $geo_info = function_exists("geoip_country_code_by_name")
                ? \geoip_record_by_name($_SERVER['REMOTE_ADDR'])
                : [
                    'country_name' => '',
                    'country_code3' => '',
                    'city' => '',
                    'region' => '',
                    'latitude' => '',
                    'longitude' => '',
                ];
            $prepare->bindParam(':country_name', $geo_info['country_name'], PDO::PARAM_STR);
            $prepare->bindParam(':country_code', $geo_info['country_code3'], PDO::PARAM_STR);
            $prepare->bindParam(':city', $geo_info['city'], PDO::PARAM_STR);
            $prepare->bindParam(':region', $geo_info['region'], PDO::PARAM_STR);
            $prepare->bindParam(':latitude', $geo_info['latitude'], PDO::PARAM_STR);
            $prepare->bindParam(':longitude', $geo_info['longitude'], PDO::PARAM_STR);

            $prepare->execute();
            $result = ['success' => true];
        } catch(PDOException $e) {
            $result = ['success' => true, 'error' => $e->getMessage()];
        }

        return $result;
    }


    public function addAdditional($params) {
        $sql = "INSERT INTO crm_additional (
          order_id, 
          product, 
          price, 
          date_added
        ) 
        VALUES (
          :order_id, 
          :product,
          :price,
          :date_added
        )";

        $prepare = $this->core->dbh->prepare($sql);
        $prepare->bindParam(':order_id', $params['id'], PDO::PARAM_INT);
        $prepare->bindParam(':date_added', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $prepare->bindParam(':product', $params['product'], PDO::PARAM_STR);
        $prepare->bindParam(':price', $params['price'], PDO::PARAM_INT);

        try {
            $prepare->execute();
            $result = ['success' => true, 'id' => $this->core->dbh->lastInsertId()];
        } catch(PDOException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }


    public function getOrdersByNotSend() {
        $sql = "SELECT COUNT(id) FROM crm_order WHERE send_email = 0";
        $prepare = $this->core->dbh->prepare($sql);

        try {
            $prepare->execute();
            $result_count = $prepare->fetchColumn();

            if ($result_count) {
                $sql_select = "SELECT 
                                  o.id, o.phone, o.fio, o.date_added, o.product, o.price,
                                  g.ip, g.referer, g.agent, g.country_name, g.country_code, 
                                  g.city, g.region, g.latitude, g.longitude,
                                  s.url,
                                  ss.name as status
                                FROM crm_order as o
                                LEFT JOIN crm_geo as g ON o.id = g.order_id
                                LEFT JOIN crm_spa as s ON o.spa_id = s.id
                                LEFT JOIN crm_status as ss ON o.status_id = ss.id
                                WHERE send_email = 0 
                                ORDER BY o.date_added ASC 
                                LIMIT 1";

                try {
                    $prepare_select = $this->core->dbh->prepare($sql_select);
                    $prepare_select->execute();
                    $data = $prepare_select->fetchAll(PDO::FETCH_ASSOC);
                    $result = ['success' => true, 'data' => $data];
                } catch(PDOException $e) {
                    $result = ['error' => $e->getMessage()];
                }
            } else {
                $result = ['success' => true, 'data' => []];
            }
        } catch(PDOException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }


    public function getAdditionalByNotSend() {
        $sql = "SELECT COUNT(id) FROM crm_additional WHERE send_email = 0";
        $prepare = $this->core->dbh->prepare($sql);

        try {
            $prepare->execute();
            $result_count = $prepare->fetchColumn();

            if ($result_count) {
                $sql_select = "SELECT 
                                    a.order_id as id, o.phone, o.fio, a.date_added, a.product, a.price, 
                                    g.ip, g.referer, g.agent, g.country_name, g.country_code, 
                                    g.city, g.region, g.latitude, g.longitude,
                                    s.url,
                                    ss.name as status
                                FROM crm_additional as a 
                                LEFT JOIN crm_order as o ON a.order_id = o.id
                                LEFT JOIN crm_geo as g ON a.order_id = g.order_id
                                LEFT JOIN crm_spa as s ON o.spa_id = s.id
                                LEFT JOIN crm_status as ss ON o.status_id = ss.id
                                WHERE a.send_email = 0 
                                ORDER BY a.date_added ASC 
                                LIMIT 1";

                try {
                    $prepare_select = $this->core->dbh->prepare($sql_select);
                    $prepare_select->execute();
                    $data = $prepare_select->fetchAll(PDO::FETCH_ASSOC);
                    $result = ['success' => true, 'data' => $data];
                } catch(PDOException $e) {
                    $result = ['error' => $e->getMessage()];
                }
            } else {
                $result = ['success' => true, 'data' => []];
            }
        } catch(PDOException $e) {
            $result = ['error' => $e->getMessage()];
        }

        return $result;
    }


    public function updateOrderSend($ids) {
        $sql = "UPDATE crm_order
                SET send_email = 1
                WHERE id = :id";

        return $this->updateSend($ids,  $sql);
    }


    public function updateAdditionalSend($ids) {
        $sql = "UPDATE crm_additional
                SET send_email = 1
                WHERE order_id = :id";

        return $this->updateSend($ids,  $sql);
    }


    private function updateSend($ids, $sql) {
        $result = [];

        foreach($ids as $id) {
            $prepare = $this->core->dbh->prepare($sql);
            $prepare->bindParam(':id', $id, PDO::PARAM_INT);

            try {
                $prepare->execute();
                $result[$id] = ['success' => true];
            } catch(PDOException $e) {
                $result[$id] = ['error' => $e->getMessage()];
            }
        }

        return $result;
    }
}