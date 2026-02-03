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

require_once _PS_MODULE_DIR_.'snt_doc_checker/models/SntDocCheckerData.php';

class Snt_Doc_CheckerCronModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;

    /**
     * Méthode principale du contrôleur cron
     */
    public function initContent()
    {
        parent::initContent();
        
        try {
            // Vérification de la clé de sécurité
            if (!$this->validateSecurityKey()) {
                $this->returnResponse(false, 'Unauthorized access - Invalid security key', 401);
                return;
            }

            // Vérification que le module est activé
            if (!Module::isEnabled('snt_doc_checker')) {
                $this->returnResponse(false, 'Module snt_doc_checker is not enabled', 503);
                return;
            }

            // Exécuter la tâche de vérification d'expiration
            $result = $this->processDocumentExpiration();
            
            $this->returnResponse(true, 'Expiration check completed successfully', 200, $result);

        } catch (Exception $e) {
            // Logger l'erreur
            PrestaShopLogger::addLog(
                'SNT Doc Checker Cron Error: ' . $e->getMessage(),
                3,
                null,
                'SntDocChecker',
                null,
                true
            );
            
            $this->returnResponse(false, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Valide la clé de sécurité fournie dans l'URL
     */
    private function validateSecurityKey()
    {
        $providedKey = Tools::getValue('secure_key');
        $configuredKey = Configuration::get('SNT_DOC_CHECKER_CRON_SECURE_KEY');
        
        if (empty($configuredKey)) {
            PrestaShopLogger::addLog(
                'SNT Doc Checker Cron: No security key configured',
                2,
                null,
                'SntDocChecker'
            );
            return false;
        }
        
        if (empty($providedKey) || $providedKey !== $configuredKey) {
            PrestaShopLogger::addLog(
                'SNT Doc Checker Cron: Invalid security key provided. IP: ' . Tools::getRemoteAddr(),
                2,
                null,
                'SntDocChecker'
            );
            return false;
        }
        
        return true;
    }

    /**
     * Traite la vérification d'expiration des documents
     */
    private function processDocumentExpiration()
    {
        $startTime = microtime(true);
        $processedDocuments = 0;
        $expiredDocuments = 0;
        $updatedAssociatedDocuments = 0;
        $errors = array();
        
        try {
            // Récupérer tous les documents avec une date d'expiration valide et non encore expirés
            $documentsToCheck = $this->getDocumentsToCheck();
            
            $processedDocuments = count($documentsToCheck);
            
            foreach ($documentsToCheck as $document) {
                try {
                    $documentId = (int)$document['id_data'];
                    $expirationDate = $document['dte_expiration'];
                    $currentStatus = $document['statut_document'];
                    
                    // Vérifier si le document a expiré
                    if ($this->isDocumentExpired($expirationDate)) {
                        // Mettre à jour le statut à "expired"
                        $updateResult = $this->updateDocumentStatus($documentId, 'expired');
                        
                        if ($updateResult) {
                            $expiredDocuments++;
                            
                            // Vérifier et mettre à jour les documents associés
                            $associatedCount = $this->updateAssociatedDocuments($documentId);
                            $updatedAssociatedDocuments += $associatedCount;
                            
                            // Logger l'expiration
                            $this->logDocumentExpiration($documentId, $document['id_customer'], $expirationDate);
                            
                        } else {
                            $errors[] = "Failed to update document ID: " . $documentId;
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing document ID " . $documentId . ": " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "Global error in expiration process: " . $e->getMessage();
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Logger le résultat de l'exécution
        $this->logCronExecution($processedDocuments, $expiredDocuments, $updatedAssociatedDocuments, $executionTime, $errors);
        
        return array(
            'processed_documents' => $processedDocuments,
            'expired_documents' => $expiredDocuments,
            'updated_associated_documents' => $updatedAssociatedDocuments,
            'execution_time_ms' => $executionTime,
            'errors' => $errors
        );
    }

    /**
     * Récupère tous les documents à vérifier
     */
    private function getDocumentsToCheck()
    {
        $sql = '
            SELECT id_data, id_customer, dte_expiration, statut_document, type_document, link_document
            FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data 
            WHERE dte_expiration IS NOT NULL 
            AND dte_expiration != "0000-00-00" 
            AND dte_expiration != "" 
            AND statut_document NOT IN ("expired", "refused", "deleted")
            ORDER BY dte_expiration ASC
        ';
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Vérifie si un document a expiré
     */
    private function isDocumentExpired($expirationDate)
    {
        if (empty($expirationDate) || $expirationDate === '0000-00-00') {
            return false;
        }
        
        $currentDate = date('Y-m-d');
        return $expirationDate < $currentDate;
    }

    /**
     * Met à jour le statut d'un document
     */
    private function updateDocumentStatus($documentId, $newStatus)
    {
        return Db::getInstance()->update(
            'snt_doc_checker_data',
            array(
                'statut_document' => pSQL($newStatus),
                'dte_update' => date('Y-m-d H:i:s'),
                'comment_document' => pSQL('Document expiré automatiquement par le système le ' . date('d/m/Y à H:i'))
            ),
            'id_data = ' . (int)$documentId
        );
    }

    /**
     * Met à jour les documents associés lorsqu'un document principal expire
     */
    private function updateAssociatedDocuments($principalDocumentId)
    {
        $updatedCount = 0;
        
        try {
            // Vérifier si ce document est principal et a des documents associés
            $associatedDocuments = Db::getInstance()->executeS('
                SELECT d.id_data, d.statut_document 
                FROM ' . _DB_PREFIX_ . 'snt_doc_checker_data d
                INNER JOIN ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association a ON a.id_document_associe = d.id_data
                WHERE a.id_document_principal = ' . (int)$principalDocumentId . '
                AND d.statut_document NOT IN ("expired", "refused", "deleted")
            ');
            
            foreach ($associatedDocuments as $assocDoc) {
                $updateResult = $this->updateDocumentStatus($assocDoc['id_data'], 'expired');
                if ($updateResult) {
                    $updatedCount++;
                }
            }
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'SNT Doc Checker Cron: Error updating associated documents for principal ' . $principalDocumentId . ': ' . $e->getMessage(),
                3,
                null,
                'SntDocChecker'
            );
        }
        
        return $updatedCount;
    }

    /**
     * Enregistre l'expiration d'un document dans les logs
     */
    private function logDocumentExpiration($documentId, $customerId, $expirationDate)
    {
        try {
            Db::getInstance()->insert('snt_doc_checker_logs', array(
                'id_employee' => 0, // 0 pour le système automatique
                'employee_name' => 'SYSTEM_CRON',
                'id_document' => (int)$documentId,
                'id_customer' => (int)$customerId,
                'action' => 'EXPIRED_BY_CRON',
                'details' => 'Document automatiquement expiré. Date d\'expiration: ' . $expirationDate,
                'date_add' => date('Y-m-d H:i:s'),
                'ip_address' => Tools::getRemoteAddr()
            ));
        } catch (Exception $e) {
            // En cas d'erreur de log, ne pas faire échouer le processus principal
            PrestaShopLogger::addLog(
                'SNT Doc Checker Cron: Failed to log document expiration for document ' . $documentId . ': ' . $e->getMessage(),
                2,
                null,
                'SntDocChecker'
            );
        }
    }

    /**
     * Enregistre les résultats de l'exécution du cron
     */
    private function logCronExecution($processed, $expired, $associatedUpdated, $executionTime, $errors)
    {
        $logMessage = sprintf(
            'Cron execution completed - Processed: %d, Expired: %d, Associated updated: %d, Time: %sms',
            $processed,
            $expired,
            $associatedUpdated,
            $executionTime
        );
        
        if (!empty($errors)) {
            $logMessage .= ' - Errors: ' . implode('; ', $errors);
        }
        
        PrestaShopLogger::addLog(
            $logMessage,
            1, // Info level
            null,
            'SntDocChecker',
            null,
            true
        );
    }

    /**
     * Retourne une réponse formatée
     */
    private function returnResponse($success, $message, $httpCode = 200, $data = null)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = array(
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_url' => $_SERVER['REQUEST_URI']
        );
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        die(json_encode($response, JSON_PRETTY_PRINT));
    }
} 