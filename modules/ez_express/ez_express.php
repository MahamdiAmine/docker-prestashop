<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ez_express/includer.php';

class Ez_express extends CarrierModule
{
    const PREFIX = 'belvg_fcdd_';

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
        $this->version = '0.6.2';
        $this->author = 'Easy Relay';
        $this->bootstrap = TRUE;
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('Easy Relay');
        $this->description = $this->l('description');
    }

    public function getTemplate($area, $file)
    {
        return 'views/templates/' . $area . '/' . $file;
    }

    public function install()
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

    protected function uninstallDB()
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

    protected function installDB()
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

    protected function createCarriers()
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
                        'id_carrier' => (int) $carrier->id,
                        'id_group' => (int) $group['id_group']
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
                        array('id_carrier' => (int) $carrier->id, 'id_zone' => (int) $z['id_zone']), 'INSERT');
                    Db::getInstance()->execute(_DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int) $z['id_zone'], 'price' => '25'), 'INSERT');
                    Db::getInstance()->execute(_DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $z['id_zone'], 'price' => '25'), 'INSERT');
                }

                copy(dirname(__FILE__) . '/views/img/carrier.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier->id . '.jpg');

                Configuration::updateValue(self::PREFIX . $value, $carrier->id);
                Configuration::updateValue(self::PREFIX . $value . '_reference', $carrier->id);
            }
        }

        return TRUE;
    }

    protected function deleteCarriers()
    {
        foreach ($this->_carriers as $value) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $value);
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return TRUE;
    }

    public function uninstall()
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


    public function get($url){
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            //curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); 
            //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields)); 
                        
            $result = curl_exec($ch); 
            curl_close($ch);    
            return $result ;
    }
    public function post($fields,$url,$cpt=0){
                $ch = curl_init(); 
                curl_setopt($ch, CURLOPT_URL, $url);    
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); 
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields)); 
                //curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1*1000);
                $result = curl_exec($ch); 
                $curl_errno = curl_errno($ch);
                $curl_error = curl_error($ch); 
                curl_close($ch);
                return $result ;
        }
    public function hookDisplayOrderConfirmation($params) {
        require_once("config.php") ;
        $url                        = $add_url ;
        $res['tracking_vendor']     = $id_vendeur; 
        $res['email']               = $email  ;
        $res['password']            = $password ;
        $order                      = $params['order'];

        $address =     new Address((int)$order->id_address_delivery);
        $res['tracking']            = $order->id ; 
        $res['delivery_wilaya']     = $address->id_state ; 
        $res['phone1']              = $address->phone ; 
        $res['delivery_commune']    = $address->city ;
        $res['delivery_address']    = $address->address1 ;
        $res['delivery_price']      = (int)$order->total_shipping;

        $res['delivery_wilaya']     = 16 ; 
        $res['delivery_commune']    = 554 ; 
        $res['qty']                 = 0 ; 
        $products = $order->getProducts(); 
        foreach ($products as $product) {

            $res['qty']         += $product['product_quantity'] ; 
           // $res['id_product']  = $product['product_reference'] ;
        }
        
        $res['price']               = (int)$order->total_products - (int) $order->total_discounts ;

        //Customer
        $customer = new Customer((int)$order->id_customer); 
        $res['client_name']         = $customer->firstname ;
        $res['client_first_name']   = $customer->lastname;
        $res['client_email']        = $customer->email;
        $a = $this->post($res,$url ) ;  

        if (json_decode($a,true)["error_code"] != 0){

        }

    }
    public function getOrderShippingCost($params, $shipping_cost)
    {

        require("config.php") ; 
       // return 555 ;  
        $adress =     new Address((int)$params->id_address_delivery);
        $wilaya = $adress->id_state ; 
        $wilaya = 16 ; 
        if ($wilaya){
            // get the delivery price
            $lien   = $racine."/api/prix/prix_var_acheteur.php?id_vendeur=".$id_vendeur."&wilaya=".$wilaya."&isrelais=0" ; 
            $prix =  $this->get($lien);
            if ($prix == intval($prix)){
                return $prix ;
            }else{
                return 1000; 
            }

        }else{
             return 0;
        }

        return 0;
    }

    public function getOrderShippingCostExternal($params)
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

            $this->smarty->assign('express_delivery_id',16 ); 
            $this->context->controller->addJS(array( $this->_path . 'views/js/ez_express.js', ));
       

            return $this->display(__FILE__, 'header.tpl'); 
        }
    }



    public function hookDisplayAdminOrder($params)
    {
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
        $city = $user_address->city ;
        ob_start();
        $output = ob_get_clean();
        file_put_contents("smart.html", $output) ;

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

    public function validateLivraison($order_id){
        require("config.php") ;
        $res['tracking_vendor']     = $id_vendeur; 
        $res['email']               = $email  ;
        $res['password']            = $password ;
        $res['tracking']            = $order_id ;

        $a = $this->post($res,$racine."/api/delivery/api.php?action=validate" ) ; 
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        require("config.php") ;
        $newStatus = $params['newOrderStatus']->id ; 
        $order_id  = $params['id_order'] ;  
        $order = new Order((int) $order_id);
       // var_dump($order_id) ;
       // die() ; 
        if (Validate::isLoadedObject($order)) {
           $carrier =  new Carrier((int) $order->id_carrier) ; 
           if ( $carrier->external_module_name == 'ez_express' AND ($newStatus == $packaged_state_id)  ){
                $this->validateLivraison($order_id) ; 
           }
        }
      
        

    }
}