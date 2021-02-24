<?php

class ez_ez_express extends ObjectModel
{
    public $id;

    public $id_ez_express;

    public $id_order;

    public $details;

    public $date_upd;

    public static $definition = array(
        'table' => 'ez_express',
        'primary' => 'id_ez_express',
        'fields' => array(
            'id_order' =>       array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => FALSE),
            'details'  =>       array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'copy_post' => FALSE),
            'date_upd' =>       array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        ),
    );

    public static function loadByOrderId($id_order)
    {
        $collection = new Collection('ez_ez_express');
        $collection->where('id_order', '=', (int)$id_order);
        if ($collection->getFirst()) {
            return $collection->getFirst();
        } else {
            return new self();
        }
    }
}