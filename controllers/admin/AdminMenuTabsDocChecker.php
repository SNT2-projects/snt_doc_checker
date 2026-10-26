<?php

require_once _PS_MODULE_DIR_.'snt_doc_checker/models/SntDocCheckerData.php';
require_once _PS_MODULE_DIR_.'snt_doc_checker/models/SntDocCheckerCustomer.php';
require_once _PS_MODULE_DIR_.'snt_doc_checker/s3/S3Utils.php';


class AdminMenuTabsDocCheckerController extends ModuleAdminController
{
    private $s3Utils;

    public function __construct()
    {
        $this->display    = 'table';
        $this->bootstrap  = true;
        $this->context    = Context::getContext();
        
        $this->identifier = 'id_data';
        $this->lang       = false;
        $this->class      = 'SntDocCheckerData';


        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)';
        $this->_select = 'c.firstname, c.lastname, c.email, a.*';


        $this->initList();

        parent::__construct();
        
        // S'assurer que les requêtes AJAX sont correctement gérées
        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'searchCustomer') {
            $this->ajaxProcessSearchCustomer();
        }

        // Gestion des nouvelles actions AJAX pour les documents liés
        if (Tools::isSubmit('ajax')) {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'searchCustomer':
                    $this->ajaxProcessSearchCustomer();
                    break;
                case 'getCustomerDocuments':
                    $this->ajaxProcessGetCustomerDocuments();
                    break;
                case 'linkDocuments':
                    $this->ajaxProcessLinkDocuments();
                    break;
                case 'unlinkDocument':
                    $this->ajaxProcessUnlinkDocument();
                    break;
                case 'setExpirationDate':
                    $this->ajaxProcessSetExpirationDate();
                    break;
                case 'setBirthDate':
                    $this->ajaxProcessSetBirthDate();
                    break;
                case 'applyApprovedStatus':
                    $this->ajaxProcessApplyApprovedStatus();
                    break;
                case 'unblockOrder':
                    $this->ajaxProcessUnblockErpOrder();
                    break;
            }
        }

        $this->s3Utils = new S3Utils();
    }

    public function initList()
    {
        $this->fields_list = array(
            'id_data' => array(
                'title'           => 'ID Document',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'firstname' => array(
                'title'           => 'Prénom',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'lastname' => array(
                'title'           => 'Nom',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'email' => array(
                'title'           => 'Email',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'link_document' => array(
                'title'           => 'Document',
                'align'           => 'center', 
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'type_document' => array(
                'title'           => 'Type de document',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
                'callback'        => 'getDocType',
                'type'            => 'select',
                'filter_type'     => 'select',
                'filter_key'      => 'type_document',
                'list'            => array(
                    'pi' => 'Carte Nationale d\'Identité',
                    'pc' => 'Permis de chasse',
                    'vpc' => 'Validation du permis de chasse',
                    'lfft' => 'Licence de la Fédération Française de Tir',
                    'lffb' => 'Licence de la Fédération Française de Biathlon',
                    'lffbt' => 'Licence de la Fédération Française de ball-trap',
                    'cc' => 'Carte de collectionneur',
                    'pp' => 'Passeport',
                    'pcd' => 'Permis de conduire',
                ),
            ),
            'statut_document' => array(
                'title'           => 'Statut',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
                'callback'        => 'getDocStatus',
                'type'            => 'select',
                'filter_type'     => 'select',
                'filter_key'      => 'statut_document',
                'list'            => array(
                    'pending' => 'En attente',
                    'valid' => 'Validé',
                    'refused' => 'Refusé',
                    'expired' => 'Expiré',
                ),
            ),
            'dte_upload' => array(
                'title'           => 'Date upload',
                'align'           => 'center',
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),
            'dte_update' => array(
                'title'           => 'Date mise à jour',
                'align'           => 'center', 
                'class'           => 'fixed-width-xl',
                'remove_onclick'  => true,
            ),

        );

        $helper = new HelperList();
        
        $this->simple_header = true;
        $this->actions       = array('edit', 'view', 'delete');
        $this->show_toolbar  = true;
        $this->title        = 'Documents';
        $this->identifier   = 'id_data';
        $this->table        = 'snt_doc_checker_data';
         
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        return $helper;
    }

    public function renderView()
    {
        $id_data = Tools::getValue($this->identifier);

        if (!$id_data) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMenuTabsDocChecker'));
        }

        $data = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'snt_doc_checker_data WHERE id_data = '.(int)$id_data);

        // Récupérer les informations du client
        $customer_data = null;
        if (!empty($data['id_customer'])) {
            $customer_data = Db::getInstance()->getRow('
                SELECT id_customer, firstname, lastname, email, date_add, is_guest, active
                FROM '._DB_PREFIX_.'customer
                WHERE id_customer = '.(int)$data['id_customer']
            );
        }

        // Récupérer la date de naissance du client
        $customer_birth_info = null;
        $has_birth_date = false;
        if (!empty($data['id_customer'])) {
            $customer_birth_info = SntDocCheckerCustomer::getByCustomerId($data['id_customer']);
            $has_birth_date = SntDocCheckerCustomer::hasBirthDate($data['id_customer']);
        }

        // Générer le lien temporaire S3 pour l'aperçu
        $temporaryUrl = '';
        if (!empty($data['link_document'])) {
            require_once _PS_MODULE_DIR_.'snt_doc_checker/s3/S3Utils.php';
            $s3Utils = new S3Utils();
            $fileName = basename($data['link_document']);
            $fileName = str_replace('/documents_client/', '', $fileName);
            
            // Utiliser la méthode spécifique pour la prévisualisation si c'est un PDF
            $fileExtension = strtolower(pathinfo($data['link_document'], PATHINFO_EXTENSION));
            $temporaryUrl = $s3Utils->getTemporaryConsultationUrl($fileName, $fileExtension);

        }

        // Récupérer le document lié s'il existe et déterminer le statut du document actuel
        $linkedDocument = null;
        $isDocumentPrincipal = false;
        $isDocumentAssociated = false;
        
        // Vérifier si le document actuel est principal
        $principalCheckSql = '
            SELECT d.*, a.id_document_associe 
            FROM '._DB_PREFIX_.'snt_doc_checker_data d
            INNER JOIN '._DB_PREFIX_.'snt_doc_checker_doc_association a ON a.id_document_associe = d.id_data
            WHERE a.id_document_principal = '.(int)$id_data.'
        ';
        $associatedDocument = Db::getInstance()->getRow($principalCheckSql);
        
        if ($associatedDocument) {
            $isDocumentPrincipal = true;
            $linkedDocument = $associatedDocument;
        } else {
            // Vérifier si le document actuel est associé
            $associatedCheckSql = '
                SELECT d.*, a.id_document_principal 
                FROM '._DB_PREFIX_.'snt_doc_checker_data d
                INNER JOIN '._DB_PREFIX_.'snt_doc_checker_doc_association a ON a.id_document_principal = d.id_data
                WHERE a.id_document_associe = '.(int)$id_data.'
            ';
            $principalDocument = Db::getInstance()->getRow($associatedCheckSql);
            
            if ($principalDocument) {
                $isDocumentAssociated = true;
                $linkedDocument = $principalDocument;
            }
        }

        // Passer les données au template
        $this->context->smarty->assign(array(
            'document_data' => $data,
            'customer_data' => $customer_data,
            'customer_birth_info' => $customer_birth_info,
            'has_birth_date' => $has_birth_date,
            'temporary_url' => $temporaryUrl,
            'file_extension' => pathinfo($data['link_document'], PATHINFO_EXTENSION),
            'file_name' => basename($data['link_document']),
            'linked_document' => $linkedDocument,
            'is_document_principal' => $isDocumentPrincipal,
            'is_document_associated' => $isDocumentAssociated,
            'is_edit_mode' => false
        ));

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => 'Afficher le document',
                    'icon' => 'icon-info',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Document',
                        'name' => 'link_document',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Type',
                        'name' => 'type_document',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Statut',
                        'name' => 'statut_document',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Commentaire',
                        'name' => 'comment_document',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Date upload',
                        'name' => 'dte_upload',
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Date mise à jour',
                        'name' => 'dte_update',
                        'readonly' => true,
                    ),
                ),
                'buttons' => array(
                    'cancel' => array(
                        'href' => self::$currentIndex.'&token='.$this->token,
                        'title' => 'Retour à la liste',
                        'icon' => 'process-icon-back',
                    ),
                ),
            ),
        );

        $output = '';
    
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->token = Tools::getAdminTokenLite('AdminMenuTabsDocChecker');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->title = "Détails du document";
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
    
        // Load current values
        $helper->fields_value = array(
            'link_document' => $data['link_document'],
            'type_document' => $data['type_document'],
            'statut_document' => $data['statut_document'],
            'comment_document' => $data['comment_document'],
            'dte_upload' => $data['dte_upload'],
            'dte_update' => $data['dte_update']
        );

        $output .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/preview_document.tpl');

        return $output . $helper->generateForm(array($fields_form));
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => 'Ajouter un nouveau document',
                ),
                'input' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_customer',
                    ),
                    array(
                        'type' => 'file',
                        'label' => 'Document',
                        'name' => 'link_document',
                        'required' => true,
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Type de document',
                        'name' => 'type_document',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => '', 'name' => 'Sélectionnez un type de document'),
                                array('id' => 'pi', 'name' => 'Carte Nationale d\'Identité'),
                                array('id' => 'pc', 'name' => 'Permis de chasse'),
                                array('id' => 'vpc', 'name' => 'Validation du permis de chasse'),
                                array('id' => 'lfft', 'name' => 'Licence de la Fédération Française de Tir'),
                                array('id' => 'lffb', 'name' => 'Licence de la Fédération Française de Biathlon'),
                                array('id' => 'lffbt', 'name' => 'Licence de la Fédération Française de ball-trap'),
                                array('id' => 'cc', 'name' => 'Carte de collectionneur'),
                                array('id' => 'pp', 'name' => 'Passeport'),
                                array('id' => 'pcd', 'name' => 'Permis de conduire'),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Statut',
                        'name' => 'statut_document',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'valid', 'name' => 'Validé'),
                                array('id' => 'pending', 'name' => 'En attente'),
                                array('id' => 'refused', 'name' => 'Refusé'),
                                array('id' => 'expired', 'name' => 'Expiré'),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => 'Commentaire',
                        'name' => 'comment_document',
                    ),
                ),
                'buttons' => array(
                    'cancel' => array(
                        'href' => self::$currentIndex.'&token='.$this->token,
                        'title' => 'Retour à la liste',
                        'icon' => 'process-icon-back',
                    ),
                ),
                'submit' => array(
                    'title' => 'Enregistrer',
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->token = Tools::getAdminTokenLite('AdminMenuTabsDocChecker');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->title = "Ajouter un nouveau document";
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submitAddDocument';
        $helper->tpl_vars = array(
            'fields_value' => array(
                'id_customer' => '',
                'link_document' => '',
                'type_document' => '',
                'statut_document' => '',
                'comment_document' => '',
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );



        // Assigner l'URL AJAX pour la recherche de clients
        $this->context->smarty->assign(array(
            'ajax_url' => 'index.php?controller=AdminMenuTabsDocChecker&ajax=1&action=searchCustomer&token='.Tools::getAdminTokenLite('AdminMenuTabsDocChecker'),
        ));

        $output  = '';
        $output .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/search_customer.tpl');

        return $output . $helper->generateForm(array($fields_form));
    }

    public function renderUpdate()
    {
        $id_data = Tools::getValue($this->identifier);

        if (!$id_data) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMenuTabsDocChecker'));
        }

        $data = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'snt_doc_checker_data WHERE id_data = '.(int)$id_data);

        // Récupérer les informations du client
        $customer_data = null;
        if (!empty($data['id_customer'])) {
            $customer_data = Db::getInstance()->getRow('
                SELECT id_customer, firstname, lastname, email, date_add, is_guest, active
                FROM '._DB_PREFIX_.'customer
                WHERE id_customer = '.(int)$data['id_customer']
            );
        }

        // Récupérer la date de naissance du client
        $customer_birth_info = null;
        $has_birth_date = false;
        if (!empty($data['id_customer'])) {
            $customer_birth_info = SntDocCheckerCustomer::getByCustomerId($data['id_customer']);
            $has_birth_date = SntDocCheckerCustomer::hasBirthDate($data['id_customer']);
        }

        // Générer le lien temporaire S3 pour l'aperçu
        $temporaryUrl = '';
        if (!empty($data['link_document'])) {
            require_once _PS_MODULE_DIR_.'snt_doc_checker/s3/S3Utils.php';
            $s3Utils = new S3Utils();
            $fileName = basename($data['link_document']);
            $fileName = str_replace('/documents_client/', '', $fileName);
            
            // Utiliser la méthode spécifique pour la prévisualisation si c'est un PDF
            $fileExtension = strtolower(pathinfo($data['link_document'], PATHINFO_EXTENSION));
            $temporaryUrl = $s3Utils->getTemporaryConsultationUrl($fileName, $fileExtension);
        }

        // Récupérer le document lié s'il existe et déterminer le statut du document actuel
        $linkedDocument = null;
        $isDocumentPrincipal = false;
        $isDocumentAssociated = false;
        
        // Vérifier si le document actuel est principal
        $principalCheckSql = '
            SELECT d.*, a.id_document_associe 
            FROM '._DB_PREFIX_.'snt_doc_checker_data d
            INNER JOIN '._DB_PREFIX_.'snt_doc_checker_doc_association a ON a.id_document_associe = d.id_data
            WHERE a.id_document_principal = '.(int)$id_data.'
        ';
        $associatedDocument = Db::getInstance()->getRow($principalCheckSql);
        
        if ($associatedDocument) {
            $isDocumentPrincipal = true;
            $linkedDocument = $associatedDocument;
        } else {
            // Vérifier si le document actuel est associé
            $associatedCheckSql = '
                SELECT d.*, a.id_document_principal 
                FROM '._DB_PREFIX_.'snt_doc_checker_data d
                INNER JOIN '._DB_PREFIX_.'snt_doc_checker_doc_association a ON a.id_document_principal = d.id_data
                WHERE a.id_document_associe = '.(int)$id_data.'
            ';
            $principalDocument = Db::getInstance()->getRow($associatedCheckSql);
            
            if ($principalDocument) {
                $isDocumentAssociated = true;
                $linkedDocument = $principalDocument;
            }
        }

        // Récupérer les commandes du client éligibles à l'approbation depuis cette page
        $eligibleOrders = array();
        $approvedStatusConfigured = (bool)(int)Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_APPROVED');
        $erpConfigured = (bool)(Configuration::get('SNT_DOC_CHECKER_ERP_URL') && Configuration::get('SNT_DOC_CHECKER_ERP_TOKEN'));
        if (!empty($data['id_customer'])) {
            $eligibleOrders = $this->getEligibleCustomerOrders((int)$data['id_customer']);
        }

        // Passer les données au template
        $this->context->smarty->assign(array(
            'document_data' => $data,
            'customer_data' => $customer_data,
            'customer_birth_info' => $customer_birth_info,
            'has_birth_date' => $has_birth_date,
            'temporary_url' => $temporaryUrl,
            'file_extension' => pathinfo($data['link_document'], PATHINFO_EXTENSION),
            'file_name' => basename($data['link_document']),
            'linked_document' => $linkedDocument,
            'is_document_principal' => $isDocumentPrincipal,
            'is_document_associated' => $isDocumentAssociated,
            'is_edit_mode' => true,
            'eligible_orders' => $eligibleOrders,
            'approved_status_configured' => $approvedStatusConfigured,
            'erp_configured' => $erpConfigured,
            'apply_approved_ajax_url' => 'index.php?controller=AdminMenuTabsDocChecker&ajax=1&action=applyApprovedStatus&token='.Tools::getAdminTokenLite('AdminMenuTabsDocChecker'),
            'unblock_order_ajax_url' => 'index.php?controller=AdminMenuTabsDocChecker&ajax=1&action=unblockOrder&token='.Tools::getAdminTokenLite('AdminMenuTabsDocChecker'),
        ));

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => 'Modifier le document',
                    'icon' => 'icon-edit',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Document',
                        'name' => 'link_document',
                        'required' => true,
                        'readonly' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Type de document',
                        'name' => 'type_document',
                        'required' => true,
                        'readonly' => true,
                        'callback' => 'getDocType',
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Statut',
                        'name' => 'statut_document',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'valid', 'name' => 'Validé'),
                                array('id' => 'pending', 'name' => 'En attente'),
                                array('id' => 'refused', 'name' => 'Refusé'),
                                array('id' => 'expired', 'name' => 'Expiré'),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => 'Commentaire',
                        'name' => 'comment_document',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => 'Envoyer un email de notification',
                        'name' => 'send_email',
                        'desc' => 'Cochez cette case pour envoyer un email au client lors de la mise à jour du statut',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'send_email_on',
                                'value' => 1,
                                'label' => 'Oui'
                            ),
                            array(
                                'id' => 'send_email_off',
                                'value' => 0,
                                'label' => 'Non'
                            )
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'id_data',
                    ),
                ),
                'buttons' => array(
                    'save_stay' => array(
                        'type' => 'submit',
                        'name' => 'submitUpdateStay',
                        'title' => 'Enregistrer et rester',
                        'icon' => 'process-icon-save',
                        'class' => 'btn btn-default pull-right',
                    ),
                    'cancel' => array(
                        'href' => self::$currentIndex.'&token='.$this->token,
                        'title' => 'Retour à la liste',
                        'icon' => 'process-icon-back',
                    ),
                ),
                'submit' => array(
                    'title' => 'Enregistrer',
                ),
            ),
        );

        $output = '';

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->token = Tools::getAdminTokenLite('AdminMenuTabsDocChecker');
        $helper->title = "Modifier le document";
        $helper->submit_action = 'submitUpdate';
    
        $helper->fields_value = array(
            'link_document' => $data['link_document'],
            'type_document' => $data['type_document'],
            'statut_document' => $data['statut_document'],
            'comment_document' => $data['comment_document'],
            'id_data' => $data['id_data'],
            'send_email' => 0,
        );

        $output .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/preview_document.tpl');

        // Encart "Commandes du client à approuver" (juste au-dessus du bloc "Modifier le document")
        $output .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/client_orders_panel.tpl');

        return $output .  $helper->generateForm(array($fields_form));
    }

    public function getDocType($docShortName)
    {
        switch ($docShortName) {
            case "pi":
                return "Carte Nationale d'Identité";
            case "pc":
                return "Permis de chasse";
            case "vpc":
                return "Validation du permis de chasse";
            case "lfft":
                return "Licence de la Fédération Française de Tir";
            case "lffb":
                return "Licence de la Fédération Française de Biathlon";
            case "lffbt":
                return "Licence de la Fédération Française de ball-trap";
            case "cc":
                return "Carte de collectionneur";
            case "pp":
                return "Passeport";
            case "pcd":
                return "Permis de conduire";
            default:
                return "Autre document";
        }
    }

    public function getDocStatus($docStatus)
    {
        switch ($docStatus) {
            case "valid":
                return '<span class="badge badge-success">Validé</span>';
            case "pending":
                return '<span class="badge badge-warning">En attente</span>';
            case "refused":
                return '<span class="badge badge-danger">Refusé</span>';
            case "expired":
                return '<span class="badge badge-danger">Expiré</span>';
            default:
                return '<span class="badge badge-secondary">Inconnu</span>';
        }
    }

    /*
    public function getCustomerName($customerId, $tr)
    {
        if (empty($customerId) || $customerId == 0) {
            return '<em>Aucun client</em>';
        }

        $customer = Db::getInstance()->getRow('
            SELECT firstname, lastname, email 
            FROM '._DB_PREFIX_.'customer 
            WHERE id_customer = '.(int)$customerId
        );

        if ($customer) {
            return $customer['firstname'] . ' ' . $customer['lastname'] . '<br><small>' . $customer['email'] . '</small>';
        }

        return '<em>Client introuvable</em>';
    }
    */

    public function initContent()
    {
        if (!Module::isEnabled('snt_doc_checker')) {
            $this->content = $this->context->smarty->fetch('module:snt_doc_checker/views/templates/admin/module_inactive.tpl');
        } else {
            if (Tools::isSubmit('addsnt_doc_checker_data')) {
                $this->content .= $this->renderForm();
            } else if (Tools::isSubmit('updatesnt_doc_checker_data')) {
                $this->content .= $this->renderUpdate();
            } else if (Tools::isSubmit('viewsnt_doc_checker_data')) {
                $this->content .= $this->renderView();
            } else {
                $this->content .= $this->renderList();
            }
        }
        
        $this->context->smarty->assign(array(
            'content' => $this->content,
            'url_post' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name,
        ));
    }

    public function ajaxProcessSearchCustomer()
    {
        try {
            $query = Tools::getValue('q', false);
            
            if (empty($query) || strlen($query) < 2) {
                header('Content-Type: application/json');
                die(json_encode(array()));
            }

            $sql =
                'SELECT c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`
                FROM `'._DB_PREFIX_.'customer` c
                WHERE c.active = 1 AND (
                    c.firstname LIKE "%'.pSQL($query).'%"
                    OR c.lastname LIKE "%'.pSQL($query).'%"
                    OR c.email LIKE "%'.pSQL($query).'%"
                )
                '.Shop::addSqlRestriction(false, 'c').'
                GROUP BY c.id_customer
                ORDER BY c.lastname, c.firstname
                LIMIT 20';

            $customers = Db::getInstance()->executeS($sql);

            // S'assurer que le header est correct pour JSON
            header('Content-Type: application/json');
            die(json_encode($customers ? $customers : array()));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array('error' => $e->getMessage())));
        }
    }

    /**
     * Méthode AJAX pour récupérer les documents d'un client
     */
    public function ajaxProcessGetCustomerDocuments()
    {
        try {
            $customerId = (int)Tools::getValue('id_customer');
            $excludeDocument = (int)Tools::getValue('exclude_document');
            
            if (!$customerId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'ID client manquant'
                )));
            }

            $sql = 'SELECT id_data, link_document, type_document, statut_document, dte_upload, dte_expiration, comment_document
                    FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data 
                    WHERE id_customer = ' . (int)$customerId;
            
            if ($excludeDocument) {
                $sql .= ' AND id_data != ' . (int)$excludeDocument;
            }
            
            $sql .= ' ORDER BY dte_upload DESC';

            $documents = Db::getInstance()->executeS($sql);

            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => true,
                'documents' => $documents ? $documents : array()
            )));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * Méthode AJAX pour lier deux documents
     */
    public function ajaxProcessLinkDocuments()
    {
        try {
            $documentId = (int)Tools::getValue('document_id');
            $linkedDocumentId = (int)Tools::getValue('linked_document_id');
            
            if (!$documentId || !$linkedDocumentId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'IDs de documents manquants'
                )));
            }

            if ($documentId == $linkedDocumentId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Un document ne peut pas être lié à lui-même'
                )));
            }

            // Vérifier que les deux documents existent et appartiennent au même client
            $doc1 = Db::getInstance()->getRow('SELECT id_customer FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data WHERE id_data = ' . $documentId);
            $doc2 = Db::getInstance()->getRow('SELECT id_customer FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data WHERE id_data = ' . $linkedDocumentId);

            if (!$doc1 || !$doc2) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Un ou plusieurs documents introuvables'
                )));
            }

            if ($doc1['id_customer'] != $doc2['id_customer']) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Les documents doivent appartenir au même client'
                )));
            }

            // Vérifier s'il existe déjà une association
            $existingLink = Db::getInstance()->getRow('
                SELECT id_association FROM ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association 
                WHERE (id_document_principal = ' . $documentId . ' AND id_document_associe = ' . $linkedDocumentId . ') 
                OR (id_document_principal = ' . $linkedDocumentId . ' AND id_document_associe = ' . $documentId . ')
            ');

            if ($existingLink) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Ces documents sont déjà liés'
                )));
            }

            // Récupérer la date d'expiration du document sélectionné (qui devient principal)
            $principalDoc = Db::getInstance()->getRow('SELECT dte_expiration FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data WHERE id_data = ' . $linkedDocumentId);
            
            // Créer l'association - le document sélectionné devient principal, le document actuel devient associé
            $result = Db::getInstance()->insert('snt_doc_checker_doc_association', array(
                'id_document_principal' => $linkedDocumentId,
                'id_document_associe' => $documentId,
                'date_add' => date('Y-m-d H:i:s')
            ));

            if ($result) {
                // Copier la date d'expiration du document principal (sélectionné) vers le document associé (actuel)
                if (!empty($principalDoc['dte_expiration']) && $principalDoc['dte_expiration'] !== '0000-00-00') {
                    Db::getInstance()->update('snt_doc_checker_data', 
                        array('dte_expiration' => pSQL($principalDoc['dte_expiration'])),
                        'id_data = ' . $documentId
                    );
                } else {
                    // Si le document principal n'a pas de date, effacer la date du document associé
                    Db::getInstance()->update('snt_doc_checker_data', 
                        array('dte_expiration' => '0000-00-00'),
                        'id_data = ' . $documentId
                    );
                }
                
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => true,
                    'message' => 'Documents liés avec succès'
                )));
            } else {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Erreur lors de la liaison des documents'
                )));
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * Méthode AJAX pour délier un document
     */
    public function ajaxProcessUnlinkDocument()
    {
        try {
            $documentId = (int)Tools::getValue('document_id');
            
            if (!$documentId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'ID de document manquant'
                )));
            }

            // Supprimer toutes les associations de ce document
            $result = Db::getInstance()->delete('snt_doc_checker_doc_association', 
                'id_document_principal = ' . $documentId . ' OR id_document_associe = ' . $documentId
            );

            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => true,
                'message' => 'Document délié avec succès'
            )));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * Méthode AJAX pour définir la date d'expiration d'un document
     */
    public function ajaxProcessSetExpirationDate()
    {
        try {
            $documentId = (int)Tools::getValue('document_id');
            $expirationDate = Tools::getValue('expiration_date');
            
            if (!$documentId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'ID de document manquant'
                )));
            }

            if (!$expirationDate) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Date d\'expiration manquante'
                )));
            }

            // Valider le format de date
            if (!DateTime::createFromFormat('Y-m-d', $expirationDate)) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Format de date invalide'
                )));
            }

            // Mettre à jour la date d'expiration
            $result = Db::getInstance()->update('snt_doc_checker_data', 
                array('dte_expiration' => pSQL($expirationDate)),
                'id_data = ' . $documentId
            );

            if ($result) {
                // Vérifier si ce document est principal et mettre à jour le document associé
                $associatedDoc = Db::getInstance()->getRow('
                    SELECT id_document_associe 
                    FROM ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association 
                    WHERE id_document_principal = ' . $documentId
                );
                
                if ($associatedDoc) {
                    // Mettre à jour la date d'expiration du document associé
                    Db::getInstance()->update('snt_doc_checker_data', 
                        array('dte_expiration' => pSQL($expirationDate)),
                        'id_data = ' . (int)$associatedDoc['id_document_associe']
                    );
                }
                
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => true,
                    'message' => 'Date d\'expiration mise à jour avec succès'
                )));
            } else {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour de la date d\'expiration'
                )));
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * Méthode AJAX pour définir la date de naissance d'un client
     */
    public function ajaxProcessSetBirthDate()
    {
        try {
            $customerId = (int)Tools::getValue('id_customer');
            $birthDate = Tools::getValue('birth_date');

            if (!$customerId) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'ID client manquant'
                )));
            }

            if (!$birthDate) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Date de naissance manquante'
                )));
            }

            // Valider le format de date
            if (!DateTime::createFromFormat('Y-m-d', $birthDate)) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Format de date invalide'
                )));
            }

            // Valider que la date est dans le passé
            $birthDateTime = new DateTime($birthDate);
            $now = new DateTime();
            if ($birthDateTime >= $now) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'La date de naissance doit être dans le passé'
                )));
            }

            // Mettre à jour la date de naissance
            $result = SntDocCheckerCustomer::updateBirthDate($customerId, $birthDate);

            if ($result) {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => true,
                    'message' => 'Date de naissance mise à jour avec succès'
                )));
            } else {
                header('Content-Type: application/json');
                die(json_encode(array(
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour de la date de naissance'
                )));
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * Récupère les commandes du client éligibles à l'approbation depuis la page
     * de validation d'un document.
     *
     * Conditions :
     *  - la commande appartient au client ;
     *  - son statut actuel est "Importé vers Oasis v2" (SNT_DOC_CHECKER_ORDER_STATUS_OASIS) ;
     *  - elle a eu à un moment le statut "Demande de pièces : Catégorie C" ou "... Catégorie D" ;
     *  - elle contient au moins un article de catégorie C ou D.
     *
     * @param int $id_customer
     * @return array
     */
    public function getEligibleCustomerOrders($id_customer)
    {
        $id_customer = (int)$id_customer;
        if (!$id_customer) {
            return array();
        }

        $statusPiecesC = (int)Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C');
        $statusPiecesD = (int)Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D');
        $statusOasis   = (int)Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_OASIS');

        $piecesStatuses = array_filter(array($statusPiecesC, $statusPiecesD));

        // Sans les statuts nécessaires, aucun filtrage possible
        if (empty($piecesStatuses) || !$statusOasis) {
            return array();
        }

        $categorieC = (int)Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C');
        $categorieD = (int)Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D');
        $restrictedCategories = array_filter(array($categorieC, $categorieD));

        if (empty($restrictedCategories)) {
            return array();
        }

        // Commandes du client au statut actuel "Importé vers Oasis v2"
        // ayant eu un statut "Demande de pièces C/D" dans leur historique
        $sql = 'SELECT DISTINCT o.id_order, o.reference, o.date_add
                FROM ' . _DB_PREFIX_ . 'orders o
                INNER JOIN ' . _DB_PREFIX_ . 'order_history oh ON oh.id_order = o.id_order
                WHERE o.id_customer = ' . $id_customer . '
                AND o.current_state = ' . $statusOasis . '
                AND oh.id_order_state IN (' . implode(',', array_map('intval', $piecesStatuses)) . ')
                AND o.id_order NOT IN (
                    SELECT id_order FROM ' . _DB_PREFIX_ . 'snt_doc_checker_unblocked_orders
                )
                ORDER BY o.date_add DESC
                LIMIT 20';

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return array();
        }

        $orders = array();
        foreach ($rows as $row) {
            $order = new Order((int)$row['id_order']);
            if (!Validate::isLoadedObject($order)) {
                continue;
            }

            // Vérifier qu'au moins un article est de catégorie C ou D
            $hasRestrictedProduct = false;
            foreach ($order->getProducts() as $product) {
                $features = Product::getFeaturesStatic((int)$product['product_id']);
                foreach ($features as $feature) {
                    if (in_array((int)$feature['id_feature_value'], $restrictedCategories)) {
                        $hasRestrictedProduct = true;
                        break 2;
                    }
                }
            }

            if (!$hasRestrictedProduct) {
                continue;
            }

            $orders[] = array(
                'id_order'  => (int)$row['id_order'],
                'reference' => $row['reference'],
                'date_add'  => Tools::displayDate($row['date_add'], null, true),
                'url'       => $this->getOrderViewLink((int)$row['id_order']),
            );
        }

        return $orders;
    }

    /**
     * Génère le lien vers la fiche d'une commande dans le back-office, compatible
     * PrestaShop 1.7 (route Symfony admin_orders_view) et 1.6 (contrôleur legacy).
     *
     * @param int $idOrder
     * @return string
     */
    private function getOrderViewLink($idOrder)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return $this->context->link->getAdminLink('AdminOrders', true, array(
                'route' => 'admin_orders_view',
                'orderId' => (int)$idOrder,
            ));
        }

        return $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int)$idOrder;
    }

    /**
     * Méthode AJAX : applique le statut "Commande approuvée [Doc Checker]" à une commande,
     * sans envoi d'email au client.
     */
    public function ajaxProcessApplyApprovedStatus()
    {
        try {
            $idOrder = (int)Tools::getValue('id_order');
            $statusApproved = (int)Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_APPROVED');

            if (!$idOrder) {
                header('Content-Type: application/json');
                die(json_encode(array('success' => false, 'message' => 'ID de commande manquant')));
            }

            if (!$statusApproved) {
                header('Content-Type: application/json');
                die(json_encode(array('success' => false, 'message' => 'Le statut "Commande approuvée [Doc Checker]" n\'est pas configuré dans le back-office.')));
            }

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                header('Content-Type: application/json');
                die(json_encode(array('success' => false, 'message' => 'Commande introuvable')));
            }

            // Ne pas ré-appliquer si la commande est déjà à ce statut
            if ((int)$order->current_state === $statusApproved) {
                header('Content-Type: application/json');
                die(json_encode(array('success' => false, 'message' => 'La commande est déjà au statut "Commande approuvée".')));
            }

            $idEmployee = (isset($this->context->employee) && Validate::isLoadedObject($this->context->employee))
                ? (int)$this->context->employee->id
                : 0;

            $history = new OrderHistory();
            $history->id_order = $idOrder;
            $history->id_employee = $idEmployee;
            $history->changeIdOrderState($statusApproved, $idOrder);
            // add() sans email : changement de statut silencieux
            $history->add();

            // Forcer la mise à jour du current_state
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'orders` SET `current_state` = ' . $statusApproved . ' WHERE `id_order` = ' . $idOrder
            );

            header('Content-Type: application/json');
            die(json_encode(array('success' => true, 'message' => 'Statut "Commande approuvée" appliqué avec succès.')));
        } catch (Exception $e) {
            header('Content-Type: application/json');
            die(json_encode(array('success' => false, 'message' => $e->getMessage())));
        }
    }

    /**
     * Méthode AJAX : débloque une commande via l'API de l'ERP (Oasis).
     *
     * Déroulé :
     *  1. Recherche de la référence ERP (Oasis) à partir de la référence web Presta
     *     (GET {erp_url}/io/api/v1/orders?filter[prestashop_id]=REF&display=[reference]).
     *  2. Déblocage de la commande côté ERP
     *     (POST {erp_url}/api/{token}/json/DebloquerCommande avec num_commande=REF_OASIS).
     *
     * En cas d'échec d'une des deux étapes, aucun changement n'est effectué et
     * un message d'erreur est retourné.
     */
    public function ajaxProcessUnblockErpOrder()
    {
        header('Content-Type: application/json');

        try {
            $idOrder = (int)Tools::getValue('id_order');
            $erpUrl = Configuration::get('SNT_DOC_CHECKER_ERP_URL');
            $erpToken = Configuration::get('SNT_DOC_CHECKER_ERP_TOKEN');

            if (!$idOrder) {
                die(json_encode(array('success' => false, 'message' => 'ID de commande manquant')));
            }

            if (empty($erpUrl) || empty($erpToken)) {
                die(json_encode(array('success' => false, 'message' => 'La configuration ERP (URL + clé API) est incomplète. Renseignez l\'onglet "ERP / API" du module.')));
            }

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                die(json_encode(array('success' => false, 'message' => 'Commande introuvable')));
            }

            // Référence web Presta = prestashop_id côté ERP
            $prestaReference = $order->reference;

            // Étape 1 : récupérer la référence Oasis (ERP)
            $errorMessage = '';
            $oasisReference = $this->getErpOrderReference($idOrder, $prestaReference, $errorMessage);
            if ($oasisReference === null) {
                die(json_encode(array('success' => false, 'message' => $errorMessage)));
            }

            // Étape 2 : débloquer la commande côté ERP
            $errorMessage = '';
            $unblockResponse = $this->unblockErpOrder($idOrder, $prestaReference, $oasisReference, $errorMessage);
            if ($unblockResponse === false) {
                die(json_encode(array(
                    'success' => false,
                    'message' => $errorMessage,
                    'oasis_reference' => $oasisReference,
                )));
            }

            // Mémoriser la commande débloquée pour ne plus la proposer dans l'encart
            // (le statut Presta ne change pas).
            $this->recordUnblockedOrder($idOrder, $prestaReference, $oasisReference);

            die(json_encode(array(
                'success' => true,
                'message' => 'Commande débloquée dans l\'ERP.',
                'presta_reference' => $prestaReference,
                'oasis_reference' => $oasisReference,
                'erp_response' => $unblockResponse,
            )));
        } catch (Exception $e) {
            die(json_encode(array('success' => false, 'message' => $e->getMessage())));
        }
    }

    /**
     * Appel ERP n°1 : récupère la référence Oasis (ERP) d'une commande à partir
     * de sa référence web Presta (prestashop_id côté ERP).
     *
     * @param int    $idOrder         ID de la commande Presta (pour le log)
     * @param string $prestaReference Référence web Presta
     * @param string &$errorMessage   Rempli en cas d'échec
     * @return string|null Référence ERP (Oasis) ou null en cas d'échec
     */
    private function getErpOrderReference($idOrder, $prestaReference, &$errorMessage)
    {
        $erpUrl = rtrim(Configuration::get('SNT_DOC_CHECKER_ERP_URL'), '/');
        $erpToken = Configuration::get('SNT_DOC_CHECKER_ERP_TOKEN');

        // Brackets conservés littéralement (attendus tels quels par l'API ERP).
        // Le slash final après "orders" est requis par l'ERP (sinon redirection 302).
        $url = $erpUrl.'/io/api/v1/orders/'
            .'?filter[prestashop_id]='.rawurlencode($prestaReference)
            .'&display=[reference]';

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'x-api-key: '.$erpToken,
                'Accept: application/json',
            ),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'GET',
            // Suivre une éventuelle redirection (l'ERP en émet, ex. slash final)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ));

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $curlError = curl_error($ch);
        curl_close($ch);

        $oasisReference = null;
        $success = false;
        $errorMessage = '';

        if ($response === false) {
            $errorMessage = 'Erreur réseau lors de la recherche de la commande dans l\'ERP : '.$curlError;
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = 'L\'ERP a répondu HTTP '.$httpCode.' lors de la recherche de la commande.'
                .($redirectUrl ? ' Redirection vers : '.$redirectUrl : '');
        } else {
            $data = json_decode($response, true);
            if (!is_array($data) || empty($data['value']) || empty($data['value'][0]['reference'])) {
                $errorMessage = 'Aucune référence ERP trouvée pour la commande '.$prestaReference.'.';
            } else {
                $oasisReference = $data['value'][0]['reference'];
                $success = true;
            }
        }

        // Le token est ici dans un en-tête (pas dans l'URL) : rien à masquer
        $this->logErpCall('search', $idOrder, $prestaReference, $oasisReference, $url, $httpCode, $redirectUrl, ($response === false ? '' : $response), $errorMessage, $success);

        return $success ? $oasisReference : null;
    }

    /**
     * Appel ERP n°2 : débloque une commande côté ERP à partir de sa référence Oasis.
     *
     * @param int    $idOrder         ID de la commande Presta (pour le log)
     * @param string $prestaReference Référence web Presta (pour le log)
     * @param string $oasisReference  Référence ERP (Oasis)
     * @param string &$errorMessage   Rempli en cas d'échec
     * @return string|false Réponse brute de l'ERP, ou false en cas d'échec
     */
    private function unblockErpOrder($idOrder, $prestaReference, $oasisReference, &$errorMessage)
    {
        $erpUrl = rtrim(Configuration::get('SNT_DOC_CHECKER_ERP_URL'), '/');
        $erpToken = Configuration::get('SNT_DOC_CHECKER_ERP_TOKEN');

        // Ici le token fait partie de l'URL (et non d'un en-tête).
        // Le slash final est requis par l'ERP (sinon redirection 302).
        $url = $erpUrl.'/api/'.rawurlencode($erpToken).'/json/DebloquerCommande/';

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array('num_commande' => $oasisReference)),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
            CURLOPT_TIMEOUT => 30,
        ));

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $curlError = curl_error($ch);
        curl_close($ch);

        // URLs masquées pour le log : on ne stocke jamais le token en clair
        // (il figure dans le chemin de l'URL et donc aussi dans la cible de redirection)
        $tokenMask = array(rawurlencode($erpToken), $erpToken);
        $logUrl = str_replace($tokenMask, '***', $url);
        $redirectUrl = str_replace($tokenMask, '***', $redirectUrl);

        $success = false;
        $errorMessage = '';
        $returnBody = false;

        if ($response === false) {
            $errorMessage = 'Erreur réseau lors du déblocage de la commande dans l\'ERP : '.$curlError;
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = 'L\'ERP a répondu HTTP '.$httpCode.' lors du déblocage de la commande.'
                .($redirectUrl ? ' Redirection vers : '.$redirectUrl : '');
        } else {
            // La réponse attendue est du JSON : { "meta": { "status": "success", ... }, "data": "1" }
            // Si le JSON contient un bloc meta, on exige meta.status = "success".
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['meta']['status']) && Tools::strtolower($data['meta']['status']) !== 'success') {
                $errorMessage = !empty($data['meta']['message'])
                    ? 'Échec du déblocage côté ERP : '.$data['meta']['message']
                    : 'Échec du déblocage côté ERP.';
            } else {
                $success = true;
                $returnBody = $response;
            }
        }

        $this->logErpCall('unblock', $idOrder, $prestaReference, $oasisReference, $logUrl, $httpCode, $redirectUrl, ($response === false ? '' : $response), $errorMessage, $success);

        return $success ? $returnBody : false;
    }

    /**
     * Enregistre un appel API ERP dans la table de logs (ps_snt_doc_checker_erp_logs).
     *
     * @param string      $callType        'search' ou 'unblock'
     * @param int         $idOrder         ID commande Presta
     * @param string      $prestaReference Référence web Presta
     * @param string|null $oasisReference  Référence ERP (Oasis) si connue
     * @param string      $url             URL appelée (token déjà masqué le cas échéant)
     * @param int         $httpCode        Code HTTP retourné
     * @param string      $redirectUrl     Cible de redirection (3xx) le cas échéant
     * @param string      $responseBody    Corps de réponse brut
     * @param string      $errorMessage    Message d'erreur le cas échéant
     * @param bool        $success         Succès de l'appel
     */
    private function logErpCall($callType, $idOrder, $prestaReference, $oasisReference, $url, $httpCode, $redirectUrl, $responseBody, $errorMessage, $success)
    {
        $employeeId = 0;
        $employeeName = '';
        if (isset($this->context->employee) && Validate::isLoadedObject($this->context->employee)) {
            $employeeId = (int)$this->context->employee->id;
            $employeeName = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        }

        // Limiter la taille du corps de réponse stocké
        $body = (string)$responseBody;
        if (Tools::strlen($body) > 5000) {
            $body = Tools::substr($body, 0, 5000).' […tronqué]';
        }

        Db::getInstance()->insert('snt_doc_checker_erp_logs', array(
            'call_type'        => pSQL($callType),
            'id_order'         => (int)$idOrder,
            'presta_reference' => pSQL($prestaReference),
            'oasis_reference'  => ($oasisReference !== null && $oasisReference !== '') ? pSQL($oasisReference) : null,
            'request_url'      => pSQL($url, true),
            'http_code'        => (int)$httpCode,
            'redirect_url'     => pSQL($redirectUrl, true),
            'response_body'    => pSQL($body, true),
            'error_message'    => ($errorMessage !== '') ? pSQL($errorMessage, true) : null,
            'success'          => $success ? 1 : 0,
            'id_employee'      => $employeeId,
            'employee_name'    => pSQL($employeeName),
            'date_add'         => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Mémorise une commande débloquée dans l'ERP (table persistante) afin qu'elle
     * ne soit plus proposée dans l'encart "Commandes du client à approuver".
     * Le statut PrestaShop de la commande n'est pas modifié.
     *
     * @param int         $idOrder
     * @param string      $prestaReference
     * @param string|null $oasisReference
     */
    private function recordUnblockedOrder($idOrder, $prestaReference, $oasisReference)
    {
        $employeeId = 0;
        $employeeName = '';
        if (isset($this->context->employee) && Validate::isLoadedObject($this->context->employee)) {
            $employeeId = (int)$this->context->employee->id;
            $employeeName = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        }

        Db::getInstance()->insert(
            'snt_doc_checker_unblocked_orders',
            array(
                'id_order'         => (int)$idOrder,
                'presta_reference' => pSQL($prestaReference),
                'oasis_reference'  => ($oasisReference !== null && $oasisReference !== '') ? pSQL($oasisReference) : null,
                'id_employee'      => $employeeId,
                'employee_name'    => pSQL($employeeName),
                'date_add'         => date('Y-m-d H:i:s'),
            ),
            false,
            true,
            Db::INSERT_IGNORE
        );
    }

    public function postProcess()
    {
        // Create document
        if (Tools::isSubmit('submitAddDocument'))
        {
            $id_customer = Tools::getValue('id_customer');
            $type_document = Tools::getValue('type_document');
            $statut_document = Tools::getValue('statut_document');
            $comment_document = Tools::getValue('comment_document');
            $id_document = uniqid();

            if (empty($type_document)) {
                $this->errors[] = 'Vous devez sélectionner un type de document.';
                return;
            }

            // Validation : vérifier qu'un client est sélectionné
            if (empty($id_customer) || $id_customer == 0) {
                $this->errors[] = 'Vous devez sélectionner un client pour ce document.';
                return;
            }

            // Gestion de l'upload de fichier
            $link_document = '';
            if (isset($_FILES['link_document']) && $_FILES['link_document']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = _PS_MODULE_DIR_.'snt_doc_checker/uploads/';
                
                // Créer le dossier uploads s'il n'existe pas
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Générer un nom de fichier selon le format spécifié
                $file_extension = pathinfo($_FILES['link_document']['name'], PATHINFO_EXTENSION);
                $id_document = uniqid(); // Assuming id_document is generated here
                $file_name = sprintf('%s-%d-%s.%s', $type_document, $id_customer, $id_document, $file_extension);
                $upload_file = $upload_dir . $file_name;

                
                // Déplacer le fichier uploadé
                if (move_uploaded_file($_FILES['link_document']['tmp_name'], $upload_file)) {
                    $link_document = $file_name; // Stocker seulement le nom du fichier
                } else {
                    $this->errors[] = 'Erreur lors de l\'upload du fichier.';
                    return;
                }

            } else {
                $this->errors[] = 'Aucun fichier sélectionné ou erreur d\'upload.';
                return;
            }

            $data = array(
                'id_customer' => (int)$id_customer,
                'link_document' => pSQL($link_document),
                'type_document' => pSQL($type_document),
                'id_document' => pSQL($id_document),
                'statut_document' => pSQL($statut_document),
                'comment_document' => pSQL($comment_document),
                'dte_upload' => date('Y-m-d H:i:s'),
            );

            if (!$this->s3Utils->putS3($upload_file)) {
                $this->errors[] = 'Erreur lors de l\'upload vers S3.';
                return;
            }

            Db::getInstance()->insert('snt_doc_checker_data', $data);

            // delete file from local
            unlink($upload_file);
            

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMenuTabsDocChecker'));
        }

        // Update document
        if (Tools::isSubmit('submitUpdate') || Tools::isSubmit('submitUpdateStay'))
        {
            $id_data = Tools::getValue('id_data');
            $link_document = Tools::getValue('link_document');
            $type_document = Tools::getValue('type_document');
            $statut_document = Tools::getValue('statut_document');
            $comment_document = Tools::getValue('comment_document');
            $send_email = Tools::getValue('send_email');

            // Récupérer l'ancien statut pour comparaison
            $old_data = Db::getInstance()->getRow('SELECT statut_document, id_customer, dte_expiration FROM '._DB_PREFIX_.'snt_doc_checker_data WHERE id_data = '.(int)$id_data);
            $old_statut = $old_data['statut_document'];
            $id_customer = $old_data['id_customer'];

            // Validation: La date de naissance du client est OBLIGATOIRE
            // sauf pour un refus (le document peut justement être refusé faute de date de naissance)
            if ($statut_document != 'refused' && !SntDocCheckerCustomer::hasBirthDate($id_customer)) {
                $this->errors[] = 'La date de naissance du client est obligatoire pour enregistrer ce document. Veuillez la renseigner.';
                return;
            }

            // Validation: La date d'expiration est OBLIGATOIRE pour valider un document (statut "valid")
            if ($statut_document == 'valid') {
                $dte_expiration = $old_data['dte_expiration'];
                if (empty($dte_expiration) || $dte_expiration == '0000-00-00') {
                    $this->errors[] = 'La date d\'expiration est obligatoire pour valider un document. Veuillez la définir avant de passer le statut en "Validé".';
                    return;
                }
            }

            $data = array(
                'link_document' => pSQL($link_document),
                'type_document' => pSQL($type_document),
                'statut_document' => pSQL($statut_document),
                'comment_document' => pSQL($comment_document),
                'dte_update' => date('Y-m-d'),
            );
            
            Db::getInstance()->update('snt_doc_checker_data', $data, 'id_data = '.(int)$id_data);

            // Synchroniser le statut avec le document lié (recto/verso)
            if ($statut_document != $old_statut) {
                // Vérifier si ce document est principal (a un document associé)
                $associatedDoc = Db::getInstance()->getRow('
                    SELECT id_document_associe
                    FROM ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association
                    WHERE id_document_principal = ' . (int)$id_data
                );

                if ($associatedDoc) {
                    // Mettre à jour le statut du document associé
                    Db::getInstance()->update('snt_doc_checker_data',
                        array(
                            'statut_document' => pSQL($statut_document),
                            'dte_update' => date('Y-m-d')
                        ),
                        'id_data = ' . (int)$associatedDoc['id_document_associe']
                    );
                } else {
                    // Vérifier si ce document est associé (a un document principal)
                    $principalDoc = Db::getInstance()->getRow('
                        SELECT id_document_principal
                        FROM ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association
                        WHERE id_document_associe = ' . (int)$id_data
                    );

                    if ($principalDoc) {
                        // Mettre à jour le statut du document principal
                        Db::getInstance()->update('snt_doc_checker_data',
                            array(
                                'statut_document' => pSQL($statut_document),
                                'dte_update' => date('Y-m-d')
                            ),
                            'id_data = ' . (int)$principalDoc['id_document_principal']
                        );
                    }
                }
            }

            // Envoyer un email si demandé et si le statut a changé
            if ($send_email && $statut_document != $old_statut && !empty($id_customer)) {
                // Récupérer l'email du client
                $customer = Db::getInstance()->getRow('SELECT email FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
                
                if ($customer && !empty($customer['email'])) {
                    $this->sendEmail($statut_document, $id_data, $this->getDocType($type_document), $customer['email']);
                }
            }

            // "Enregistrer et rester" : revenir sur la page de modification du document
            if (Tools::isSubmit('submitUpdateStay')) {
                Tools::redirectAdmin(self::$currentIndex.'&id_data='.(int)$id_data.'&updatesnt_doc_checker_data&token='.$this->token);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMenuTabsDocChecker'));
        }

        // Delete document
        if (Tools::isSubmit('deletesnt_doc_checker_data'))
        {
            $id_data = Tools::getValue('id_data');
            Db::getInstance()->delete('snt_doc_checker_data', 'id_data = '.(int)$id_data);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMenuTabsDocChecker'));
        }
        
        parent::postProcess();
    }

    /**
     * Envoie un email au client selon le nouveau statut du document
     */
    public function sendEmail($newStatut, $id_doc, $type_doc, $userEmail)
    {
        $baseUrl = Tools::getHttpHost(true).__PS_BASE_URI__;
        $uploaderUrl = $baseUrl . 'module/snt_doc_checker/user_uploader';
        
        switch ($newStatut) {
            case "pending":
                // Document soumis / en attente
                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),
                    'doc_submited', 
                    'Votre document a été soumis',
                    array(
                        '{id_doc}'     => $id_doc,
                        '{type_doc}'   => $type_doc,
                        '{statut_doc}' => 'En attente',
                        '{date_update}'   => date('j/m/Y') . ' à ' . date("H:i"),
                        '{message}'    => '<p>Bonjour,<br>
                        Nous avons bien reçu votre pièce justificative. Elle est actuellement en cours d\'examen par notre service administratif.</p>
                        <p>Nous vous tiendrons informé de la validation ou du refus de votre document dans les plus brefs délais.</p>
                        <p>Vous pouvez consulter l\'état de vos documents depuis votre espace client.</p>'
                    ),
                    $userEmail,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    _PS_MODULE_DIR_ . 'snt_doc_checker/mails'
                );
                break;

            case "valid":
                // Document accepté
                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),
                    'doc_accepted', 
                    'Votre document a été accepté',
                    array(
                        '{id_doc}'     => $id_doc,
                        '{type_doc}'   => $type_doc,
                        '{statut_doc}' => 'Accepté',
                        '{date_update}'   => date('j/m/Y') . ' à ' . date("H:i"),
                        '{message}'    => '<p>Bonjour,<br>
                        Nous avons procédé au contrôle de votre pièce justificative, cette dernière est conforme à la réglementation en vigueur et a été validée par notre service administratif.</p>
                        <p style="font-family:Open sans, arial, sans-serif;font-size: 18px;font-weight:600;line-height:25px;text-align:left;color:#363A41;">Vous avez déjà effectué une commande ?</p>
                        <p>Nous allons pouvoir procéder à l\'expédition de votre commande dans les plus brefs délais.</p>
                        <p style="font-family:Open sans, arial, sans-serif;font-size: 18px;font-weight:600;line-height:25px;text-align:left;color:#363A41;">Vous n\'avez pas encore passé de commande ?</p>
                        <p>Vous pouvez dès à présent effectuer vos achats sur <a href="' . $baseUrl . '">notre site</a>.</p>'
                    ),
                    $userEmail,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    _PS_MODULE_DIR_ . 'snt_doc_checker/mails'
                );
                break;

            case "refused":
                // Document refusé
                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),
                    'doc_refused', 
                    'Votre document a été refusé',
                    array(
                        '{id_doc}'     => $id_doc,
                        '{type_doc}'   => $type_doc,
                        '{statut_doc}' => 'Refusé',
                        '{date_update}'   => date('j/m/Y') . ' à ' . date("H:i"),
                        '{message}'    => '<p>Bonjour,<br>
                        <b>Nous avons procédé au contrôle de votre pièce justificative, malheureusement, cette dernière n\'est pas conforme à la réglementation en vigueur.</b></p>
                        <p>Cela peut provenir d\'un type de fichier non pris en compte, d\'une numérisation de mauvaise qualité, d\'une date de validité dépassée ou encore de documents non officiels etc…</p>
                        <p>Nous vous invitons à nous renvoyer vos pièces justificatives depuis votre espace client <a href="' . $uploaderUrl . '">ici</a>.</p>
                        <p style="text-align: center;"><a style="text-align: center; color: #fff; text-decoration: none; background: #25b9d7; padding: 10px 15px;" href="' . $uploaderUrl . '">Envoyer de nouvelles pièces</a></p>'
                    ),
                    $userEmail,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    _PS_MODULE_DIR_ . 'snt_doc_checker/mails'
                );
                break;

            case "expired":
                // Document expiré
                Mail::Send(
                    (int)(Configuration::get('PS_LANG_DEFAULT')),
                    'doc_expired', 
                    'Votre document a expiré',
                    array(
                        '{id_doc}'     => $id_doc,
                        '{type_doc}'   => $type_doc,
                        '{statut_doc}' => 'Expiré',
                        '{date_update}'   => date('j/m/Y') . ' à ' . date("H:i"),
                        '{message}'    => '<p>Bonjour,<br>
                        Votre pièce justificative a atteint sa date d\'expiration et n\'est plus valide.</p>
                        <p>Pour continuer à bénéficier de nos services, nous vous invitons à nous transmettre une nouvelle version à jour de ce document.</p>
                        <p>Vous pouvez soumettre vos nouvelles pièces justificatives depuis votre espace client <a href="' . $uploaderUrl . '">ici</a>.</p>
                        <p style="text-align: center;"><a style="text-align: center; color: #fff; text-decoration: none; background: #25b9d7; padding: 10px 15px;" href="' . $uploaderUrl . '">Envoyer de nouvelles pièces</a></p>'
                    ),
                    $userEmail,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    _PS_MODULE_DIR_ . 'snt_doc_checker/mails'
                );
                break;

            default:
                // Statut inconnu, ne pas envoyer d'email
                return false;
        }
        
        return true;
    }
}
?>