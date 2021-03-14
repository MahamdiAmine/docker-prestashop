<?php


if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ez_express/includer.php';

class Ez_express extends CarrierModule
{
    const PREFIX = 'belvg_fcdd_';
    const GET_REQUESTS_LOG_FILE = 'requests.get.txt';
    const POST_REQUESTS_LOG_FILE = 'requests.post.txt';
    const EXCEPTIONS_FILE = 'exceptions.log';
    private $_HOST = 'https://tst2.easy-relay.com';
    private $_VENDOR_ID = 1150;
    private $_VENDOR_EMAIL = 'test@prestashop.com';
    private $_VENDOR_PASSWORD = '0597';
    private $_SHIPPING_URL_TEMPLATE = '/api/prix/prix_var_acheteur.php?id_vendeur=_VENDOR&wilaya=_STATE&isrelais=0';
    private $_VALIDATE_DELIVERY_URL = '/api/delivery/api.php?action=validate';
    private $_ADD_DELIVERY_URL = "/api/delivery/api.php?action=add";
    private $_PACKAGED_STATE_ID = 3;


    public $id_carrier;

    protected $_hooks = array(
        'header',
        'actionCarrierUpdate',
        'displayOrderConfirmation',
        'displayAdminOrder',
        'displayBeforeCarrier',
        'displayShoppingCart',
        'actionOrderStatusPostUpdate',
    );

    protected $_carriers = array(
        'Easy Relay' => 'fcdd',
    );

    public function __construct()
    {
        $this->name = 'ez_express';
        $this->tab = 'shipping_logistics';
        $this->version = '0.1';
        $this->author = 'Easy Relay';
        $this->author_uri = 'http://easy-relay.com/';
        $this->bootstrap = TRUE;
        $this->module_key = '';
        $this->description = 'Prestashop module to manage the shipping';
        $this->description_full = 'prestashop plugIn to handle the shipping the return process';
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => '1.7.7.2');

        parent::__construct();

        $this->displayName = $this->l('Easy Relay');
        $this->description = $this->l('description');
    }

    protected function getTemplate($area, $file): string
    {
        return 'views/templates/' . $area . '/' . $file;
    }

    public function createTabLink(): bool
    {
        $tab = new Tab;
        foreach (Language::getLanguage() as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Origin');
        }
        $tab->class_name = 'AdminOrigin';
        $tab->module = $this->name;
        $tab->id_parent = 0;
        $tab->add();
        return true;

    }

    public function install(): bool
    {
        if (parent::install()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return FALSE;
                }
            }

            if (!$this->installDB()) {
                return FALSE;
            }

            if (!$this->createCarriers()) {
                return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    protected function uninstallDB(): bool
    {
        $sql = array();

        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ez_express`';

        foreach ($sql as $_sql) {
            if (!Db::getInstance()->Execute($_sql)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function installDB(): bool
    {
        $sql = array();

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ez_express` (
            `id_ez_express` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT( 11 ) UNSIGNED,
            `details` TEXT,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_ez_express`)
        ) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $_sql) {
            if (!Db::getInstance()->Execute($_sql)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    protected function createCarriers(): bool
    {
        foreach ($this->_carriers as $key => $value) {
            //Create own carrier
            $carrier = new Carrier();
            $carrier->name = $key;
            $carrier->active = TRUE;
            $carrier->deleted = 0;
            $carrier->shipping_handling = FALSE;
            $carrier->range_behavior = 0;
            $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = 'Selon la destination';
            $carrier->shipping_external = TRUE;
            $carrier->is_module = TRUE;
            $carrier->external_module_name = $this->name;
            $carrier->need_range = TRUE;

            if ($carrier->add()) {
                $groups = Group::getGroups(true);
                foreach ($groups as $group) {
                    Db::getInstance()->execute(_DB_PREFIX_ . 'carrier_group', array(
                        'id_carrier' => (int)$carrier->id,
                        'id_group' => (int)$group['id_group']
                    ), 'INSERT');
                }

                $rangePrice = new RangePrice();
                $rangePrice->id_carrier = $carrier->id;
                $rangePrice->delimiter1 = '0';
                $rangePrice->delimiter2 = '1000000';
                $rangePrice->add();

                $rangeWeight = new RangeWeight();
                $rangeWeight->id_carrier = $carrier->id;
                $rangeWeight->delimiter1 = '0';
                $rangeWeight->delimiter2 = '1000000';
                $rangeWeight->add();

                $zones = Zone::getZones(true);
                foreach ($zones as $z) {
                    Db::getInstance()->execute(_DB_PREFIX_ . 'carrier_zone',
                        array('id_carrier' => (int)$carrier->id, 'id_zone' => (int)$z['id_zone']), 'INSERT');
                    Db::getInstance()->execute(_DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id, 'id_range_price' => (int)$rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int)$z['id_zone'], 'price' => '25'), 'INSERT');
                    Db::getInstance()->execute(_DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int)$rangeWeight->id, 'id_zone' => (int)$z['id_zone'], 'price' => '25'), 'INSERT');
                }

                copy(dirname(__FILE__) . '/views/img/carrier.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg');

                Configuration::updateValue(self::PREFIX . $value, $carrier->id);
                Configuration::updateValue(self::PREFIX . $value . '_reference', $carrier->id);
            }
        }

        return TRUE;
    }

    protected function deleteCarriers(): bool
    {
        foreach ($this->_carriers as $value) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $value);
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return TRUE;
    }

    public function uninstall(): bool
    {
        if (parent::uninstall()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return FALSE;
                }
            }

            /*if (!$this->uninstallDB()) {
                return FALSE;
            }*/

            if (!$this->deleteCarriers()) {
                return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    public function get($url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        $result = curl_exec($ch);
        curl_close($ch);
        $this->log_events(self::GET_REQUESTS_LOG_FILE, $url, $result);
        return $result;
    }

    public function post($fields, $url, $cpt = 0): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        //curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1*1000);
        $result = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        $this->log_events(self::POST_REQUESTS_LOG_FILE, $url, $result);
        return $result;
    }

    private function log_events($filename, $requested_url, $response)
    {
        $date = date('m/d/Y h:i:s', time());
        $fp = fopen($filename, 'a');//opens file in append mode
        $format = $date . "$$" . $this->_VENDOR_ID . "$$" . $requested_url . "$$" . $response . "\n";
        fwrite($fp, $format);
        fclose($fp);
    }

    public function hookDisplayOrderConfirmation($params)
    {
        // prepare the necessary fields
        $res['tracking_vendor'] = $this->_VENDOR_ID;
        $res['email'] = $this->_VENDOR_EMAIL;
        $res['password'] = $this->_VENDOR_PASSWORD;
        $order = $params['order'];

        $address = new Address((int)$order->id_address_delivery);
        $res['tracking'] = $order->id;
        $state = new State((int)$address->id_state);
        $wilaya = $state->iso_code;
        $res['delivery_wilaya'] = $wilaya;
        $res['phone1'] = $address->phone;
        // use the postcode for the delivery_commune
        // $res['delivery_commune'] = $address->postcode;
        $res['delivery_address'] = $address->address1;
        $res['delivery_price'] = (int)$order->total_shipping;
        $quantity = 0;
        $products = $order->getProducts();
        foreach ($products as $product) {
            $quantity += $product['product_quantity'];
        }

        $res['qty'] = $quantity;
        $res['price'] = (int)$order->total_products - (int)$order->total_discounts;

        // customer's infos
        $customer = new Customer((int)$order->id_customer);
        $res['client_name'] = $customer->firstname;
        $res['client_first_name'] = $customer->lastname;
        $res['client_email'] = $customer->email;
        $res['commentaire'] = 'just for testing';
        $this->post($res, $this->_HOST . $this->_ADD_DELIVERY_URL);


    }

    public function getOrderShippingCost($params, $shipping_cost): int
    {
        // get the delivery price
        require("config.php");
        // get the delivery address from the customer  address
        $address = new Address((int)$params->id_address_delivery);
        $state = new State((int)$address->id_state);
        $wilaya = $state->iso_code;
        if ($wilaya) {
            // prepare the url
            $vars = array(
                '_VENDOR' => $this->_VENDOR_ID,
                '_STATE' => $wilaya,
            );
            $shipping_url = strtr($this->_HOST . $this->_SHIPPING_URL_TEMPLATE, $vars);
            try {
                // get the shipping cost
                $price = $this->get($shipping_url);
                if ($price == intval($price)) {
                    return $price;
                } else {
                    return 1000;
                }
            } catch (Exception $ex) {
                $this->log_events(self::EXCEPTIONS_FILE, '/', $ex->getMessage());
            }
        } else {
            return 0;
        }
    }

    public function getOrderShippingCostExternal($params): int
    {
        return $this->getOrderShippingCost($params, 0);
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'fcdd_reference')) {
            Configuration::updateValue(self::PREFIX . 'fcdd', $params['carrier']->id);
        }
    }

    public function hookHeader($params)
    {
        if (in_array(Context::getContext()->controller->php_self, array('order-opc', 'order'))) {
            //$this->context->controller->addCSS(($this->_path) . 'css/ez_express.css', 'all');
            // $id = Configuration::get(self::PREFIX . 'fcdd');

            $this->smarty->assign('express_delivery_id', 16);
            $this->context->controller->addJS(array($this->_path . 'views/js/ez_express.js',));
            return $this->display(__FILE__, 'header.tpl');
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        echo "hookDisplayAdminOrder";
        $freightDeliveryObj = ez_ez_express::loadByOrderId($params['id_order']);
        $this->context->smarty->assign('ez_express_obj', $freightDeliveryObj);
        return $this->display(__file__, $this->getTemplate('admin', 'productAdminTab.tpl'));
    }

    public function hookDisplayBeforeCarrier($params)
    {
        $context = Context::getContext();
        $cart = $context->cart;

        $this->context->smarty->assign(array(
            'freight_company_carrier_details' => $this->context->cookie->fcdd_details,
            // 'freight_company_carrier_id' => Configuration::get('belvg_fcdd_fcdd'),
            //  'express_delivery_id' => Configuration::get(self::PREFIX . 'fcdd'),
        ));


        //$id_customer=$params['objOrder']->id_customer;
        // $customer= new Customer((int)$id_customer);
        $user_address = new Address(intval($params['cart']->id_address_delivery));
        $city = $user_address->city;
        ob_start();
        $output = ob_get_clean();
        file_put_contents("smart.html", $output);

        $this->context->smarty->assign(array(
            'city' => $city,

        ));

        return $this->display(__file__, 'displayBeforeCarrier.tpl');
    }

    public function hookDisplayShoppingCart($params)
    {
        //echo "asdf";
        //exit();
    }

    // validate the delivery
    public function validateLivraison($order_id)
    {
        // prepare the required fields
        $fields['tracking_vendor'] = $this->_VENDOR_ID;
        $fields['email'] = $this->_VENDOR_EMAIL;
        $fields['password'] = $this->_VENDOR_PASSWORD;
        $fields['tracking'] = $order_id;
        $this->post($fields, $this->_HOST . $this->_VALIDATE_DELIVERY_URL);
    }

    // validate the delivery if the state has been changed to the PACKAGED_STATE
    public function hookActionOrderStatusPostUpdate($params)
    {
        //  +----------------+--------------------------------------+
        //  | id_order_state | name                                 |
        //  +----------------+--------------------------------------+
        //  |              1 | Awaiting check payment               |
        //  |              2 | Payment accepted                     |
        //  |              3 | Processing in progress               |
        //  |              4 | Shipped                              |
        //  |              5 | Delivered                            |
        //  |              6 | Canceled                             |
        //  |              7 | Refunded                             |
        //  |              8 | Payment error                        |
        //  |              9 | On backorder (paid)                  |
        //  |             10 | Awaiting bank wire payment           |
        //  |             11 | Remote payment accepted              |
        //  |             12 | On backorder (not paid)              |
        //  |             13 | Awaiting Cash On Delivery validation |
        //  +----------------+--------------------------------------+
        // require("config.php");
        $newStatus = $params['newOrderStatus']->id;
        $order_id = $params['id_order'];
        $order = new Order((int)$order_id);
        if (Validate::isLoadedObject($order)) {
            $carrier = new Carrier((int)$order->id_carrier);
            if ($carrier->external_module_name == 'ez_express' and ($newStatus == $this->_PACKAGED_STATE_ID)) {
                $this->validateLivraison($order_id);
            }
        }
    }

    // display the content for the module configure
    public function getContent()
    {
        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }
}
