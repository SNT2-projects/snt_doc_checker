<?php
class SntDocCheckerData extends ObjectModel
{
    public $id_data;
    public $dte_upload;
    public $link_document;
    public $type_document;
    public $statut_document;
    public $comment_document;
    public $dte_update;
    public $id_document;
    public $id_customer;

    public static $definition = array(
        'table' => 'snt_doc_checker_data',
        'primary' => 'id_data',
        'fields' => array(
            'dte_upload' => array('type' => self::TYPE_DATE, 'required' => true),
            'link_document' => array('type' => self::TYPE_STRING, 'required' => true),
            'type_document' => array('type' => self::TYPE_STRING, 'required' => true),
            'statut_document' => array('type' => self::TYPE_STRING, 'required' => true),
            'comment_document' => array('type' => self::TYPE_STRING, 'required' => true),
            'dte_update' => array('type' => self::TYPE_DATE, 'required' => true),
            'id_document' => array('type' => self::TYPE_INT, 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'required' => true),
        ),
    );

    public static function getDocCheckerData($id_data)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('snt_doc_checker_data');
        $sql->where('id_data = '.(int)$id_data);

        return Db::getInstance()->getRow($sql);
    }

    public static function deleteDocCheckerData($id_data)
    {
        return Db::getInstance()->delete('snt_doc_checker_data', 'id_data = '.(int)$id_data);
    }

    public static function addDocCheckerData($dte_upload, $link_document, $type_document, $statut_document, $comment_document, $dte_update, $id_document, $id_customer)
    {
        $data = array(
            'dte_upload' => pSQL($dte_upload),
            'link_document' => pSQL($link_document),
            'type_document' => pSQL($type_document),
            'statut_document' => pSQL($statut_document),
            'comment_document' => pSQL($comment_document),
            'dte_update' => pSQL($dte_update),
            'id_document' => (int)$id_document,
            'id_customer' => (int)$id_customer,
        );

        $object = new SntDocCheckerData();
        $object->hydrate($data);
        $object->add();

        return $object->id_data;
    }

    public function updateDocCheckerData()
    {
        $this->update();
    }
}
