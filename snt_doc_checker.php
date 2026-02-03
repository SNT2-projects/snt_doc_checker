<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/s3/S3Utils.php');


class Snt_doc_checker extends Module
{
    protected $config_form = false;
    private $s3Utils;
    private $s3Key;
    private $s3Secret; 
    private $s3Bucket;
    private $s3StorageType;
    private $awsRegion;

    public function __construct()
    {
        $this->name = 'snt_doc_checker';
        $this->tab = 'administration';
        $this->version = '1.2.0';
        $this->author = 'SNT2';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('SNT Doc Checker');
        $this->description = $this->l('Module de vérification des documents');
        
        // Get S3 configuration
        $this->s3Key = Configuration::get('SNT_DOC_CHECKER_S3_KEY');
        $this->s3Secret = Configuration::get('SNT_DOC_CHECKER_S3_SECRET');
        $this->s3Bucket = Configuration::get('SNT_DOC_CHECKER_S3_BUCKET');
        $this->s3StorageType = Configuration::get('SNT_DOC_CHECKER_S3_STORAGE_TYPE');
        $this->awsRegion = Configuration::get('SNT_DOC_CHECKER_AWS_REGION');

        // Check if all S3 config values are set
        if (!$this->s3Key || !$this->s3Secret || !$this->s3Bucket || !$this->s3StorageType || !$this->awsRegion) {
            $this->warning = $this->l('S3 configuration is incomplete. Please check your settings.');
        } else {
            $this->s3Utils = new S3Utils();
        }

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }


    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('displayMyAccountBlock') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayTagCommentOrder') &&
            $this->installTab('AdminParentModulesSf', 'AdminMenuTabsDocChecker', 'Doc Checker') &&
            $this->cleanLegacyTableDocuments() &&
            $this->copyEmailTemplates();
    }

    public function installTab($parent_class, $class_name, $name)  {
        $tab = new Tab();
        $tab->name[$this->context->language->id] = $name; 
        $tab->class_name = $class_name;
        $tab->id_parent = (int) Tab::getIdFromClassName($parent_class);
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Copie les templates d'emails vers le dossier /mails/fr de PrestaShop
     */
    public function copyEmailTemplates()
    {
        $templateFiles = [
            'pieces_categorie_c.html',
            'pieces_categorie_c.txt',
            'pieces_categorie_d.html',
            'pieces_categorie_d.txt'
        ];

        $sourceDir = dirname(__FILE__) . '/mails/fr/';
        $destinationDir = _PS_ROOT_DIR_ . '/mails/fr/';

        // Vérifier que le dossier de destination existe
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                return false;
            }
        }

        // Copier chaque template
        foreach ($templateFiles as $file) {
            $sourceFile = $sourceDir . $file;
            $destinationFile = $destinationDir . $file;

            if (file_exists($sourceFile)) {
                if (!copy($sourceFile, $destinationFile)) {
                    // Log l'erreur si la copie échoue
                    error_log("SNT Doc Checker: Échec de la copie du template $file");
                    return false;
                }
            } else {
                // Log si le fichier source n'existe pas
                error_log("SNT Doc Checker: Template source $file non trouvé");
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }


    public function getContent()
    {
        $output = '';

        // Gestion des appels AJAX
        if (Tools::getValue('ajax')) {
            $this->handleAjaxRequest();
            return;
        }


        if (((bool)Tools::isSubmit('submitSnt_doc_checkerModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('module_version', $this->version);

        // Vérifie si l'ancien module (tag_checker) est installé pour permettre l'import
        $isLegacyModuleInstalled = Module::isInstalled('tag_checker');
        $this->context->smarty->assign('is_legacy_module_installed', $isLegacyModuleInstalled);
        
        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }


    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSnt_doc_checkerModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }


	protected function getConfigForm()
	{
		return [
			'form' => [
				'legend' => [
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs',
				],
				'tabs' => [
					'messages' => $this->l('Messages'),
					'restrictions' => $this->l('Restrictions'),
                    's3' => $this->l('S3'),
                    'automation' => $this->l('Automatisation'),
                    'logs' => $this->l('Logs')
				],
				'input' => array_merge(
                    $this->getConfigFormMessages(),
					$this->getConfigFormRestriction(),
                    $this->getConfigFormS3(),
                    $this->getConfigFormAutomation(),
                    $this->getConfigFormLogs()
				),
				'submit' => [
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right'
				]
			]
		];
	}


    protected function getConfigFormS3()
    {
        return [
            [
                'tab' => 's3',
                'type' => 'text',
                'label' => $this->l('S3 Key'),
                'name' => 'SNT_DOC_CHECKER_S3_KEY',
                'desc' => $this->l('S3 Key pour l\'upload des documents'),
                'rows' => 10,
                'cols' => 100
            ],
            [
                'tab' => 's3',
                'type' => 'text',
                'label' => $this->l('S3 Secret'),
                'name' => 'SNT_DOC_CHECKER_S3_SECRET',
                'desc' => $this->l('S3 Secret pour l\'upload des documents'),
                'rows' => 10,
                'cols' => 100
            ],
            [
                'tab' => 's3',
                'type' => 'text',
                'label' => $this->l('S3 Bucket'),
                'name' => 'SNT_DOC_CHECKER_S3_BUCKET',
                'desc' => $this->l('S3 Bucket pour l\'upload des documents'),
            ],
            [
                'tab' => 's3',
                'type' => 'text',
                'label' => $this->l('S3 Region'),
                'name' => 'SNT_DOC_CHECKER_AWS_REGION',
                'desc' => $this->l('S3 Region pour l\'upload des documents'),
            ],
            [
                'tab' => 's3',
                'type' => 'text',
                'label' => $this->l('S3 Storage Type'),
                'name' => 'SNT_DOC_CHECKER_S3_STORAGE_TYPE',
                'desc' => $this->l('Type de stockage S3'),
            ]
        ];
    }


	protected function getConfigFormMessages()
	{
		return [
			[
				'tab' => 'messages',
				'type' => 'switch',
				'label' => $this->l('Activer les messages en front'),
				'name' => 'SNT_DOC_CHECKER_MESSAGES_FRONT',
				'is_bool' => true,
				'desc' => $this->l('Activer ou désactiver l\'affichage des messages en front office'),
				'values' => [
					[
						'id' => 'active_on',
						'value' => true,
						'label' => $this->l('Oui')
					],
					[
						'id' => 'active_off',
						'value' => false,
						'label' => $this->l('Non')
					]
				],
			],
			[
				'tab' => 'messages',
				'type' => 'textarea',
				'label' => $this->l('Message tunnel de commande ( Catégorie C )'),
				'name' => 'SNT_DOC_CHECKER_MESSAGE_TUNNEL_C',
				'desc' => $this->l('Message affiché dans le tunnel de commande'),
				'rows' => 5,
				'cols' => 100,
                'autoload_rte' => true,
			],
			[
				'tab' => 'messages',
				'type' => 'textarea',
				'label' => $this->l('Message tunnel de commande ( Catégorie D )'),
				'name' => 'SNT_DOC_CHECKER_MESSAGE_TUNNEL_D',
				'desc' => $this->l('Message affiché dans le tunnel de commande'),
				'rows' => 5,
				'cols' => 100,
                'autoload_rte' => true,
			]
		];
	}

    protected function getConfigFormRestriction()
    {
        return [
            [
                'tab' => 'restrictions',
                'type' => 'text',
                'label' => $this->l('ID de la catégorie C à restreindre'),
                'name' => 'SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C',
                'desc' => $this->l('ID de la catégorie C à restreindre'),
                'size' => 50
            ],
            [
                'tab' => 'restrictions',
                'type' => 'text',
                'label' => $this->l('ID de la catégorie D à restreindre'),
                'name' => 'SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D',
                'desc' => $this->l('ID de la catégorie D à restreindre'),
                'size' => 50
            ]
        ];
    }

    protected function getConfigFormAutomation()
    {
        return [
            [
                'tab' => 'automation',
                'type' => 'switch',
                'label' => $this->l('Activer le check quotidien de l\'expiration des documents'),
                'name' => 'SNT_DOC_CHECKER_DAILY_CHECK',
                'is_bool' => true,
                'desc' => $this->l('Activer ou désactiver la vérification quotidienne automatique de l\'expiration des documents'),
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Oui')
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Non')
                    ]
                ]
            ],
            [
                'tab' => 'automation',
                'type' => 'text',
                'label' => $this->l('Clé de sécurité CRON'),
                'name' => 'SNT_DOC_CHECKER_CRON_SECURE_KEY',
                'desc' => $this->l('Clé de sécurité pour protéger l\'accès à l\'URL du CRON. Laissez vide pour en générer une automatiquement.'),
                'size' => 50
            ],
            [
                'tab' => 'automation',
                'type' => 'text',
                'label' => $this->l('URL du CRON'),
                'name' => 'SNT_DOC_CHECKER_CRON_URL',
                'readonly' => true,
                'desc' => $this->l('URL à appeler pour déclencher la vérification automatique des documents expirés'),
                'size' => 100
            ],
            [
                'tab' => 'automation',
                'type' => 'text',
                'label' => $this->l('Fréquence recommandée'),
                'name' => 'SNT_DOC_CHECKER_CRON_TASK',
                'desc' => $this->l('Exemple de configuration CRON pour exécution quotidienne à 2h du matin'),
                'size' => 50,
                'readonly' => true
            ]
        ];
    }


    protected function getConfigFormLogs()
    {
        return [
            [
                'tab' => 'logs',
                'type' => 'switch',
                'label' => $this->l('Activer les logs'),
                'name' => 'SNT_DOC_CHECKER_LOGS',
                'is_bool' => true,
                'desc' => $this->l('Activer ou désactiver les logs'),
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Oui')
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Non')
                    ]
                ]
            ]
        ];
    }



    protected function getConfigFormValues()
    {
        return array(
            // Messages
            'SNT_DOC_CHECKER_MESSAGES_FRONT' => Configuration::get('SNT_DOC_CHECKER_MESSAGES_FRONT', true),
            'SNT_DOC_CHECKER_MESSAGE_TUNNEL_C' => Configuration::get('SNT_DOC_CHECKER_MESSAGE_TUNNEL_C', ''),
            'SNT_DOC_CHECKER_MESSAGE_TUNNEL_D' => Configuration::get('SNT_DOC_CHECKER_MESSAGE_TUNNEL_D', ''),
            
            // Restrictions
            'SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C' => Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C', ''),
            'SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D' => Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D', ''),
            
            // S3
            'SNT_DOC_CHECKER_S3_KEY' => Configuration::get('SNT_DOC_CHECKER_S3_KEY', ''),
            'SNT_DOC_CHECKER_S3_SECRET' => Configuration::get('SNT_DOC_CHECKER_S3_SECRET', ''),
            'SNT_DOC_CHECKER_S3_BUCKET' => Configuration::get('SNT_DOC_CHECKER_S3_BUCKET', ''),
            'SNT_DOC_CHECKER_AWS_REGION' => Configuration::get('SNT_DOC_CHECKER_AWS_REGION', ''),
            'SNT_DOC_CHECKER_S3_STORAGE_TYPE' => Configuration::get('SNT_DOC_CHECKER_S3_STORAGE_TYPE', ''),
            
            // Automatisation
            'SNT_DOC_CHECKER_DAILY_CHECK' => Configuration::get('SNT_DOC_CHECKER_DAILY_CHECK', false),
            'SNT_DOC_CHECKER_CRON_SECURE_KEY' => $this->getCronSecureKey(),
            'SNT_DOC_CHECKER_CRON_URL' => $this->generateCronUrl(),
            'SNT_DOC_CHECKER_CRON_TASK' => $this->generateCronCommand(),

            // Logs
            'SNT_DOC_CHECKER_LOGS' => Configuration::get('SNT_DOC_CHECKER_LOGS', false),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Génère ou récupère la clé de sécurité du cron
     */
    private function getCronSecureKey()
    {
        $secureKey = Configuration::get('SNT_DOC_CHECKER_CRON_SECURE_KEY');
        
        if (empty($secureKey)) {
            $secureKey = $this->generateSecureKey();
            Configuration::updateValue('SNT_DOC_CHECKER_CRON_SECURE_KEY', $secureKey);
        }
        
        return $secureKey;
    }

    /**
     * Génère une clé de sécurité aléatoire
     */
    private function generateSecureKey()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Génère l'URL complète du cron
     */
    private function generateCronUrl()
    {
        $secureKey = $this->getCronSecureKey();
        $baseUrl = Tools::getHttpHost(true) . __PS_BASE_URI__;
        
        return $baseUrl . 'index.php?fc=module&module=snt_doc_checker&controller=cron&secure_key=' . $secureKey;
    }

    /**
     * Génère la commande cron recommandée
     */
    private function generateCronCommand()
    {
        $cronUrl = $this->generateCronUrl();
        return '0 2 * * * wget -q -O - "' . $cronUrl . '" >/dev/null 2>&1';
    }

    /**
     * Gère les requêtes AJAX
     */
    protected function handleAjaxRequest()
    {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'count_documents':
                $this->ajaxCountDocuments();
                break;
            case 'import_batch':
                $this->ajaxImportBatch();
                break;
            default:
                $this->ajaxResponse(false, 'Action non reconnue');
        }
    }

    /**
     * Compte le nombre de documents à importer via AJAX
     */
    protected function ajaxCountDocuments()
    {
        try {
            $this->logMessage("Début du comptage des documents à importer");
            
            // Vérifier si la table de l'ancien module existe
            $tableExists = Db::getInstance()->executeS("SHOW TABLES LIKE 'ps_tag_checker_document'");

            if (empty($tableExists)) {
                $this->logMessage("Table de l'ancien module n'existe pas");
                $this->ajaxResponse(false, 'La table de l\'ancien module n\'existe pas.');
                return;
            }

            // Compter les documents non supprimés et non déjà importés
            // Utiliser la même logique que dans ajaxImportBatch
            $count = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM ps_tag_checker_document tcd
                WHERE tcd.statut_document != "deleted"
                AND NOT EXISTS (
                    SELECT 1 FROM ps_snt_doc_checker_data sdc 
                    WHERE sdc.id_document = tcd.id_document 
                    AND sdc.id_customer = tcd.id_customer
                )
            ');

            $this->logMessage("Nombre de documents à importer: " . $count);
            
            // Compter aussi les documents déjà importés pour information
            $importedCount = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM ps_tag_checker_document tcd
                WHERE tcd.statut_document != "deleted"
                AND EXISTS (
                    SELECT 1 FROM ps_snt_doc_checker_data sdc 
                    WHERE sdc.id_document = tcd.id_document 
                    AND sdc.id_customer = tcd.id_customer
                )
            ');

            $this->logMessage("Nombre de documents déjà importés: " . $importedCount);

            $this->ajaxResponse(true, 'Documents comptés', array(
                'total' => (int)$count,
                'already_imported' => (int)$importedCount,
                'total_in_source' => (int)$count + (int)$importedCount
            ));

        } catch (Exception $e) {
            $this->logMessage('Erreur lors du comptage: ' . $e->getMessage());
            $this->ajaxResponse(false, 'Erreur lors du comptage: ' . $e->getMessage());
        }
    }

    /**
     * Importe un lot de documents via AJAX
     */
    protected function ajaxImportBatch()
    {
        try {
            $offset = (int)Tools::getValue('offset', 0);
            $limit = (int)Tools::getValue('limit', 10);
            
            // Log détaillé du début du traitement
            $this->logMessage("Début du traitement du lot - Offset: $offset, Limit: $limit");

            // Récupérer un lot de documents non encore importés
            $tagCheckerDocs = Db::getInstance()->executeS('
                SELECT tcd.* 
                FROM ps_tag_checker_document tcd
                WHERE tcd.statut_document != "deleted"
                AND NOT EXISTS (
                    SELECT 1 FROM ps_snt_doc_checker_data sdc 
                    WHERE sdc.id_document = tcd.id_document 
                    AND sdc.id_customer = tcd.id_customer
                )
                ORDER BY tcd.id_document ASC
                LIMIT '.(int)$limit
            );

            // Vérifier si nous avons des documents à traiter
            if (empty($tagCheckerDocs)) {
                $this->logMessage("Lot vide - Aucun document à traiter");
                $this->ajaxResponse(true, 'Lot vide', array(
                    'imported' => 0,
                    'errors' => 0,
                    'error_details' => array(),
                    'processed' => 0,
                    'is_empty' => true
                ));
                return;
            }

            $this->logMessage("Documents trouvés dans le lot: " . count($tagCheckerDocs));

            $importedCount = 0;
            $errorCount = 0;
            $errors = array();

            foreach ($tagCheckerDocs as $doc) {
                try {
                    $this->logMessage("Traitement du document ID: " . $doc['id_document'] . ", Customer: " . $doc['id_customer']);
                    
                    // Vérifier si le document n'existe pas déjà (double vérification)
                    $exists = Db::getInstance()->getValue('
                        SELECT id_data 
                        FROM ps_snt_doc_checker_data 
                        WHERE id_document = ' . (int)$doc['id_document'] . ' 
                        AND id_customer = ' . (int)$doc['id_customer']
                    );
                    
                    if ($exists) {
                        $this->logMessage("Document déjà importé - ID: " . $doc['id_document']);
                        continue;
                    }

                    $resultImportDb = false;
                    $resultUploadS3 = false;

                    // Importer en base de données
                    $resultImportDb = Db::getInstance()->insert('snt_doc_checker_data', array(
                        'link_document' => pSQL(str_replace('/documents_client/', '', $doc['link_document'])),
                        'type_document' => pSQL($doc['type_document']),
                        'id_customer' => (int)$doc['id_customer'],
                        'id_document' => (int)$doc['id_document'],
                        'statut_document' => pSQL($doc['statut_document']),
                        'comment_document' => pSQL($doc['comment_document']),
                        'dte_upload' => pSQL($doc['dte_upload']),
                        'dte_update' => date('Y-m-d H:i:s')
                    ));

                    if ($resultImportDb) {
                        $this->logMessage("Insertion DB réussie pour document ID: " . $doc['id_document']);
                        
                        // Upload sur S3 si l'insertion en base a réussi et si S3 est configuré
                        if ($this->s3Utils) {
                            // Chemin vers les fichiers de l'ancien module
                            $filePath = _PS_MODULE_DIR_.'tag_checker/controllers/front/'.$doc['link_document'];
                            if (file_exists($filePath)) {
                                $this->logMessage("Début upload S3 pour fichier: " . $doc['link_document']);
                                $resultUploadS3 = $this->s3Utils->putS3($filePath);
                                
                                if ($resultUploadS3) {
                                    $this->logMessage("Upload S3 réussi pour fichier: " . $doc['link_document']);
                                } else {
                                    $this->logMessage("Erreur upload S3 pour fichier: " . $doc['link_document']);
                                }
                                
                                // Ajouter un délai pour éviter les rate limits S3
                                usleep(10000); // 0.01 seconde
                            } else {
                                // Le fichier n'existe pas, on considère que c'est un succès partiel
                                $resultUploadS3 = true;
                                $error_msg = 'Fichier non trouvé: ' . $doc['link_document'];
                                $errors[] = $error_msg;
                                $this->logMessage($error_msg);
                            }
                        } else {
                            // S3 non configuré, on considère que c'est OK
                            $resultUploadS3 = true;
                            $this->logMessage("S3 non configuré, saut de l'upload");
                        }

                        if ($resultUploadS3) {
                            $importedCount++;
                            $this->logMessage("Document importé avec succès - ID: " . $doc['id_document']);
                        } else {
                            $errorCount++;
                            $error_msg = 'Erreur upload S3 pour: ' . $doc['link_document'];
                            $errors[] = $error_msg;
                            $this->logMessage($error_msg);
                        }
                    } else {
                        $errorCount++;
                        $error_msg = 'Erreur insertion DB pour document ID: ' . $doc['id_document'];
                        $errors[] = $error_msg;
                        $this->logMessage($error_msg);
                    }

                } catch (Exception $e) {
                    $errorCount++;
                    $error_msg = 'Erreur pour document ID ' . $doc['id_document'] . ': ' . $e->getMessage();
                    $errors[] = $error_msg;
                    $this->logMessage($error_msg);
                }
            }

            $this->logMessage("Fin du traitement du lot - Importés: $importedCount, Erreurs: $errorCount");

            $this->ajaxResponse(true, 'Lot traité', array(
                'imported' => $importedCount,
                'errors' => $errorCount,
                'error_details' => $errors,
                'processed' => count($tagCheckerDocs),
                'is_empty' => false
            ));

        } catch (Exception $e) {
            $this->logMessage('Erreur lors de l\'importation du lot: ' . $e->getMessage());
            $this->ajaxResponse(false, 'Erreur lors de l\'importation du lot: ' . $e->getMessage());
        }
    }

    /**
     * Méthode de logging pour le débogage
     */
    private function logMessage($message)
    {
        $logsEnabled = Configuration::get('SNT_DOC_CHECKER_LOGS', false);
        if ($logsEnabled) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message\n";
            
            // Écrire dans un fichier de log
            $logFile = _PS_MODULE_DIR_ . 'snt_doc_checker/logs/import_debug.log';
            
            // Créer le dossier logs s'il n'existe pas
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Nettoyer les logs anciens si le fichier devient trop volumineux
            $this->cleanOldLogs($logFile);
            
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Nettoie les logs anciens pour éviter que le fichier devienne trop volumineux
     */
    private function cleanOldLogs($logFile)
    {
        if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5MB
            $lines = file($logFile, FILE_IGNORE_NEW_LINES);
            
            // Garder seulement les 1000 dernières lignes
            if (count($lines) > 1000) {
                $lines = array_slice($lines, -1000);
                file_put_contents($logFile, implode("\n", $lines) . "\n", LOCK_EX);
            }
        }
    }
    
    /**
     * Ajoute une méthode pour vider les logs manuellement
     */
    public function clearLogs()
    {
        $logFile = _PS_MODULE_DIR_ . 'snt_doc_checker/logs/import_debug.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        return true;
    }

    /**
     * Nettoie les documents orphelins dans la table de l'ancien module
     */
    public function cleanLegacyTableDocuments()
    {
        // Vérifie si la table de l'ancien module existe
        $tableExists = Db::getInstance()->executeS("SHOW TABLES LIKE 'ps_tag_checker_document'");
        if (empty($tableExists)) {
            return true;
        }

        Db::getInstance()->execute('DELETE FROM ps_tag_checker_document WHERE id_customer = 0');
        return true;

    }
    
    /**
     * Envoie une réponse AJAX
     */
    protected function ajaxResponse($success, $message, $data = array())
    {
        $response = array(
            'success' => $success,
            'message' => $message
        );
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Hook back-office header (utilisé pour debug uniquement)
     * @return string
     */
    public function hookDisplayBackOfficeHeader()
    {

        // upload on S3
        /*
        $result = $this->s3Utils->putS3(_PS_MODULE_DIR_.'snt_doc_checker/demo.pdf');
        if ($result) {
            echo "<h1>fichier uploadé</h1>";
        } else {
            echo "<h1>erreur upload</h1>";
        }
        */


        // afficher tous les fichiers
        /*
        $result = $this->s3Utils->listAllS3Files();
        foreach ($result['Contents'] as $file) {
            echo $file['Key'] . " | " . $file['LastModified'] . " | " . $file['Size'] . " | " . $file['ETag'] . " | " . $file['StorageClass'] . "<br/>";
        }
        
        // supprimer tous les fichiers
        foreach ($result['Contents'] as $file) {
            $this->s3Utils->deleteS3($file['Key']);
        }
        die();
        */


        // lire un fichier
        /*
        $result = $this->s3Utils->getS3('pi-254-253.jpeg');
        echo $result;
        die();
        */

        // supprimer tous les fichiers
        /*
        foreach ($result['Contents'] as $file) {
            $this->s3Utils->deleteS3($file['Key']);
        }
        die();
        */

        // supprimer un fichier
        /*
        $result = $this->s3Utils->deleteS3('demo.pdf/demoeee.pdf');
        echo $result;
        die();
        */

        // supprimer tous les fichiers
        // lister les fichiers
        /*
        $result = $this->s3Utils->listS3();
        foreach ($result["Contents"] as $file) {
            echo $file['Key'] . " | " . $file['LastModified'] . " | " . $file['Size'] . " | " . $file['ETag'] . " | " . $file['StorageClass'] . "<br/>";
        }
        foreach ($result["Contents"] as $file) {
            $this->s3Utils->deleteS3($file['Key']);
        }
        */


        // compter les fichiers
        /*
        $result = $this->s3Utils->countS3Files();
        echo $result;
        die();
        */
    }



    public function hookHeader()
    {
        /*
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        */
    }

    public function hookDisplayCustomerAccount()
    {
        $this->context->link->getModuleLink('snt_doc_checker', 'user_uploader');
        $this->context->smarty->assign([
            'ns_page_name' => Configuration::get('NS_DOC_CHECKER_PAGENAME'),
            'ns_page_link' => $this->context->link->getModuleLink('snt_doc_checker', 'user_uploader')
        ]);

        return $this->display(__FILE__, 'card_user_uploader.tpl');
    }





    public function hookDisplayMyAccountBlock()
    {
        return $this->display(__FILE__, 'block_user_uploader.tpl');
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'snt_doc_checker-style',
            $this->_path.'views/css/front.css',
            [
                'media' => 'all',
                'priority' => 999,
            ]
        );
    }


    public function getDocType($docShortName)
    {
        $types = array(
            'pi' => 'Carte Nationale d\'Identité',
            'pc' => 'Permis de chasse',
            'lfft' => 'Licence de la FF de Tir',
            'lffb' => 'Licence de la FF de Biathlon',
            'lffbt' => 'Licence de la FF de ball-trap',
            'cc' => 'Carte de collectionneur',
            'pp' => 'Passeport',
            'pcd' => 'Permis de conduire',
            'vpc' => 'Validation du permis de chasse',
            'autre' => 'Autre document'
        );
        
        return isset($types[$docShortName]) ? $types[$docShortName] : 'Autre document';
    }


    public function hookDisplayAdminOrder($params)
    {
        $order = new Order($params['id_order']);
        $products = $order->getProducts();
        $customer = new Customer($order->id_customer);
        
        // Vérifier si la commande contient des produits de catégorie C ou D
        $hasRestrictedProducts = false;
        $restrictedCategories = array(
            Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C'),
            Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D')
        );
        
        foreach ($products as $product) {
            // Récupérer les caractéristiques du produit
            $productFeatures = Product::getFeaturesStatic($product['product_id']);
            
            if (!empty($productFeatures)) {
                foreach ($productFeatures as $feature) {
                    if (in_array((int)$feature['id_feature_value'], $restrictedCategories)) {
                        $hasRestrictedProducts = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$hasRestrictedProducts) {
            $this->context->smarty->assign('no_restricted_products', true);
            return $this->display(__FILE__, 'views/templates/admin/order_documents.tpl');
        }
        
        // Récupérer les documents du client
        $documents = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT * FROM '._DB_PREFIX_.'snt_doc_checker_data 
            WHERE id_customer = ' . (int)$customer->id . ' 
            AND statut_document != "deleted" 
            ORDER BY dte_upload DESC'
        );
        
        if (empty($documents)) {
            $this->context->smarty->assign('no_documents', true);
            $this->context->smarty->assign('customer', $customer);
            return $this->display(__FILE__, 'views/templates/admin/order_documents.tpl');
        }


       
        
        // Vérifier les documents sans date d'expiration
        $hasDocumentsWithoutExpiration = false;
        foreach ($documents as $doc) {
            if (empty($doc['expiration_date']) && $doc['statut_document'] != 'refused') {
                $hasDocumentsWithoutExpiration = true;
                break;
            }
        }



         
        
        // Formater les documents pour l'affichage

        $formattedDocuments = array();
        foreach ($documents as $doc) {

            $docType = $this->getDocType($doc['type_document']);

            
            // Formatage du statut
            $statusClass = '';
            $statusName = '';
            
            switch ($doc['statut_document']) {
                case 'valid':
                    $statusClass = 'success';
                    $statusName = 'Validé';
                    break;
                case 'refused':
                    $statusClass = 'danger';
                    $statusName = 'Refusé';
                    break;
                case 'pending':
                    $statusClass = 'warning';
                    $statusName = 'En attente';
                    break;
                case 'expired':
                    $statusClass = 'danger';
                    $statusName = 'Expiré';
                    break;
                default:
                    $statusClass = 'secondary';
                    $statusName = 'Inconnu';
            }

            
            $formattedDocuments[] = array(
                'id' => $doc['id_data'],
                'type' => $docType,
                'status' => $statusName,
                'status_class' => $statusClass,
                'upload_date' => $doc['dte_upload'],
                'dte_expiration' => $doc['dte_expiration'] ?: 'Non spécifiée',
                'edit_url' => $this->context->link->getAdminLink('AdminMenuTabsDocChecker') . '&updatesnt_doc_checker_data&id_data=' . $doc['id_data'] . '&token=' . Tools::getAdminTokenLite('AdminMenuTabsDocChecker')
            );
        }

        
        $this->context->smarty->assign(array(
            'documents' => $formattedDocuments,
            'has_documents' => true,
            'customer' => $customer,
            'has_documents_without_expiration' => $hasDocumentsWithoutExpiration,
            'has_restricted_products' => true,

        ));
        
        return $this->display(__FILE__, 'views/templates/admin/order_documents.tpl');
    }


    public function hookDisplayTagCommentOrder()
    {

        // if SNT_DOC_CHECKER_MESSAGES_FRONT is true
        if (Configuration::get('SNT_DOC_CHECKER_MESSAGES_FRONT') == true)
        {
            $products  = Context::getContext()->cart->getProducts();
            $bOk       = false;
            $ids       = array();

            foreach ($products as $product) {
                foreach ($product['features'] as $id_features) {

                    array_push($ids, $id_features['id_feature_value']);
                    // D
                    if ((int)$id_features['id_feature_value'] == Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D')) {
                        $commentaire = $this->getCommentaire("TUNNEL_D");
                        $this->context->smarty->assign('commentaire', $commentaire);
                        $bOk = true;
                    }

                    // C
                    if ((int)$id_features['id_feature_value'] == Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C') && ($bOk == false)) {
                        $commentaire = $this->getCommentaire("TUNNEL_C");
                        $this->context->smarty->assign('commentaire', $commentaire);
                    }
                }
            }

            // Si il y a des produits de catégorie C ou D.
            if (in_array(Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_D'), $ids) || in_array(Configuration::get('SNT_DOC_CHECKER_RESTRICTED_CATEGORIE_C'), $ids)) {
                return $this->display(__FILE__, 'views/templates/front/order_comment.tpl');
            } else {
                return "";
            }
        }
    }


    public function getCommentaire($type)
    {
        return Configuration::get('SNT_DOC_CHECKER_MESSAGE_' . $type);
    }


}