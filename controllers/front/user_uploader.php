<?php

// Charger S3Utils avec vérification
$s3UtilsPath = _PS_MODULE_DIR_.'snt_doc_checker/s3/S3Utils.php';

if (file_exists($s3UtilsPath)) {
    require_once $s3UtilsPath;
} else {
    error_log("Erreur: Fichier S3Utils.php non trouvé: " . $s3UtilsPath);
}

class snt_doc_checkeruser_uploaderModuleFrontController extends ModuleFrontController
{
    // LIMITE DE DOCUMENT PAR UTILISATEUR;
    const MAX_DOC_BY_USER = 15;


    public function initContent()
    {
        parent::initContent();
        
        // Vérification de l'utilisateur
        if (!Context::getContext()->customer->isLogged()) {
            echo 'Vous devez être connecté pour accéder à cette page';
            Tools::redirect('index.php?controller=authentication&redirect=module&module=' . $this->module->name . '&action=user_uploader');
        }
        
        $this->context = Context::getContext();
        
        $this->initTable();
        $this->initForm();
    }

    
    public function getDocType($docShortName)
    {
        switch ($docShortName)
        {
            case "pi":
                return "Carte Nationale d’Identité";
                break;
            case "pc":
                return "Permis de chasse";
                break;
            case "vpc":
                return "Validation du permis de chasse";
                break; 
            case "lfft":
                return "Licence de la Fédération Française de Tir";
                break;
            case "lffb":
                return "Licence de la Fédération Française de Biathlon";
                break;
            case "lffbt":
                return "Licence de la Fédération Française de ball-trap";
                break;
            case "cc":
                return "Carte de collectionneur";
                break;
            case "pp":
                return "Passeport";
                break;
            case "pcd":
                return "Permis de conduire";
                break;  
            default:
                return "Autre document";
                break;
        }
    }

    public function initTable()
    {

        $customerId = Context::getContext()->customer->id;

        $docs = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT * FROM ps_snt_doc_checker_data Where id_customer=' . (int)$customerId . ';');

        $pieces      = array();

        foreach ($docs as $doc)
        {
            $docType = $this->getDocType($doc['type_document']);

            switch ($doc['statut_document'])
            {
                case "refused":
                    $docStatut = "refuse";
                    break;
                case "valid":
                    $docStatut = "valide";
                    break;
                case "expired":
                    $docStatut = "expire";
                    break;
                default:
                    $docStatut = "En cours de traitement";
                    break;
            }

            // Si le document n'est pas supprimé par l'admin
            if ($doc['statut_document'] != "deleted") {
                $pieces[$doc['id_document']]
                = array( 'type'        => $docType,
                         'statut'      => $docStatut,
                         'date'        => $doc['dte_upload'],
                         'commentaire' => $doc['comment_document']);
            }


        }

        // Tableau recap pièces
        $this->context->smarty->assign('pieces', $pieces);

        // lien mention légal form
        $linkMention =  Tools::getHttpHost(true).__PS_BASE_URI__ . "content/mentions-legales-2.html";
        $this->context->smarty->assign('linkMention', $linkMention);

    }

    // Envoie un mail si le document a été
    // correctement soumis.
    public function sendEmail($docNum, $docType, $userEmail)
    {
        $shopName = Configuration::get('PS_SHOP_NAME');
        $message = "<p>Bonjour,<br>Nous avons bien reçu votre pièce justificative, elle est désormais à l’étude par notre service administratif, nous reviendrons vers vous dans les plus brefs délais à l’issue de cet examen.</p>
                <p>L’équipe " . $shopName . "</p>";

        Mail::Send(
            (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
            'doc_submited',
            'Document correctement soumis',
            array(
                '{id_doc}'     => $docNum,
                '{type_doc}'   => $docType,
                '{statut_doc}' => 'En attente',
                '{date_doc}'   => date('j/m/Y') . ' à ' . date("H:i."),
                '{message}'    => $message
            ),
            $userEmail, // receiver email address
            NULL,       // receiver name
            NULL,       // from email address
            NULL,       // from name
            NULL,       // file attachment
            NULL,        // mode smtp
            _PS_MODULE_DIR_ . 'snt_doc_checker/mails' //custom template path
        );
    }


    // Check nb Doc
    protected function countNbDocUser()
    {
        $customerId = Context::getContext()->customer->id;

        $nbDoc = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS("
        SELECT DISTINCT count(id_document) FROM ps_snt_doc_checker_data Where id_customer=". $customerId . ";");
        
        if ($nbDoc[0]['count(id_document)'] + 1 > self::MAX_DOC_BY_USER) return FALSE;
        return TRUE;
    }

    public function initForm()
    {
        // Pas de commentaire au début
        $this->context->smarty->assign('commentaire_upload', "");

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/user_uploader.tpl');
    }


    public function postProcess()
    {
            // Submit
            if (Tools::isSubmit('form_submit') && isset($_POST))
            {
                $bValid = true;
                        // Limite de doc
                        
                        if ($this->countNbDocUser())
                        {
                        
                            // Checkbox
                            if (Tools::getValue('scales') == "on")
                            {
                                if ($this->isFileValid($_FILES['file_url']))
                                {
                                    $result = $this->storeFiles($_FILES['file_url']);
                                    if ($result === false) {
                                        $bValid = false;
                                    } else {
                                        $docInfos[] = $result;
                                    }
                    
                                    if ($_FILES['file_url2']['name'] != "")
                                    {
                                        if ($this->isFileValid($_FILES['file_url2']))
                                        {
                                            $result2 = $this->storeFiles($_FILES['file_url2']);
                                            if ($result2 === false) {
                                                $bValid = false;
                                            } else {
                                                $docInfos[] = $result2;
                                            }
                                        }
                                        else
                                        {
                                            $bValid = false;
                                        }
                                    }
                                }
                                else
                                {
                                    $bValid = false;
                                }
                            }
                            else
                            {
                                $bValid = false;
                            }
                        }
                        else
                        {
                            $bValid = false;
                            error_log("Erreur: Limite de document atteinte");
                        }

                if ($bValid) header("Location: " . "user_uploader?error=0");
                else         header("Location: " . "user_uploader?error=1");
            }
    }



    // Permet de vérifier que le fichier est valide.
    protected function isFileValid($file)
    {
        // Tab contenant les erreurs
        $errors    = array();

        $file_name = $file['name'];
        $file_size = $file['size'];


        // verif extension
        $extensions = array('pdf', 'png', 'jpeg', 'gif', 'jpg');

        $tmp            = explode('.', strtolower($file_name));
        $file_ext       = end($tmp);


        
        if( ! in_array($file_ext,$extensions) ) $errors[]="extension not allowed, please choose a JPEG or PNG file.";

        // verif size
        if($file_size > 5000000) { $errors[]='File size must be excately 5 MB'; }


        if (empty($errors)) return TRUE;
        else
        {
            header("Location: " . "user_uploader?error=1");
            return FALSE;
        }
    }

    protected function storeFiles($file)
    {
        // variable de retour
        $id_document = uniqid();

        $docInfos = [];

        $customerId = (int)Context::getContext()->customer->id;
        $docType = pSQL(Tools::getValue('doc-select'));

        $tmp = explode('.', strtolower($file['name']));
        $file_ext = end($tmp);

        $upload_dir = _PS_MODULE_DIR_.'snt_doc_checker/uploads/';
        
        // Créer le dossier uploads s'il n'existe pas
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = sprintf('%s-%d-%s.%s', $docType, $customerId, $id_document, $file_ext);
        $upload_file = $upload_dir . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $upload_file)) {
            // En cas d'échec du move_uploaded_file, on peut logger l'erreur
            error_log("Échec du téléchargement du fichier: " . $file['name']);
            return false;
        }

        $docInfos[] = $docType;
        $docInfos[] = $file_name;
        $docInfos[] = $id_document;

        try {
            // Vérifier que la classe S3Utils existe
            if (!class_exists('S3Utils')) {
                error_log("Erreur: La classe S3Utils n'est pas disponible");
                return false;
            }
            
            $s3Utils = new S3Utils();
            if ($s3Utils === null) {
                error_log("Erreur: Impossible d'instancier S3Utils");
                return false;
            }
            
            $uploadResult = $s3Utils->putS3($upload_file);
            if (!$uploadResult) {
                error_log("Erreur: Échec de l'upload vers S3 pour le fichier: " . $file_name);
                return false;
            }
        } catch (Exception $e) {
            error_log("Erreur S3Utils: " . $e->getMessage());
            return false;
        }

        // Insérer en base de données
        $insertResult = Db::getInstance()->insert('snt_doc_checker_data', array(
            'id_customer' => $customerId,
            'link_document' => $file_name,
            'id_document' => $id_document,
            'type_document' => $docType,
            'statut_document' => "pending",
            'comment_document' => "En cours de traitement...",
        ));

        if (!$insertResult) {
            error_log("Erreur: Échec de l'insertion en base de données pour le fichier: " . $file_name);
            // Si l'insertion échoue, on pourrait supprimer le fichier de S3 ici
            // $s3Utils->deleteS3($file_name);
            return false;
        }

        // delete file from local
        if (file_exists($upload_file)) {
            unlink($upload_file);
        }

        // Envoyer un email de confirmation
        try {
            $customer = Context::getContext()->customer;
            if ($customer && $customer->email) {
                $docTypeLabel = $this->getDocType($docType);
                $this->sendEmail($file_name, $docTypeLabel, $customer->email);
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email: " . $e->getMessage());
            // On n'arrête pas le processus si l'email échoue
        }

        return $docInfos;
    }
}

?>