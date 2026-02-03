{*
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
*}

<div class="panel">
	<div style="display: flex; gap: 10px; align-items: center; justify-content: space-between;">
		<div style="display: flex; gap: 10px; align-items: center;">
			<div>
				<img width="100" class="rounded" style="border-radius: 10px;box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;" src="{$module_dir|escape:'html':'UTF-8'}logo.png" />
			</div>
			<div>
				<h2 style="font-weight: 800;">{l s='Module de vérification de documents clients' mod='snt_doc_checker'}</h2>
				<h4>{l s='Développé par SNT2' mod='snt_doc_checker'}</h4>
				<p><span class="badge badge-success">Version {$module_version}</span></p>
			</div>
		</div>
		{if $is_legacy_module_installed}
		<div>
			<button type="button" id="import-documents-btn" class="btn btn-success">
				<i class="icon-download"></i>
				{l s='Importer depuis l\'ancien module' mod='snt_doc_checker'}
			</button>
			<button type="button" id="cancel-import-btn" class="btn btn-danger" style="display: none; margin-left: 10px;">
				<i class="icon-stop"></i>
				{l s='Annuler' mod='snt_doc_checker'}
			</button>
		</div>
		{/if}
	</div>
</div>

{if $is_legacy_module_installed}
<div class="panel" id="import-progress-panel" style="display: none;">
	<h3>{l s='Progression de l\'importation' mod='snt_doc_checker'}</h3>
	<div class="progress" style="height: 30px; margin-bottom: 20px;">
		<div id="import-progress-bar" class="progress-bar progress-bar-success progress-bar-striped active" 
			 role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; line-height: 30px;">
			<span id="progress-text">0%</span>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<div class="alert alert-info">
				<strong>{l s='Total:' mod='snt_doc_checker'}</strong> <span id="total-documents">0</span>
			</div>
		</div>
		<div class="col-md-3">
			<div class="alert alert-success">
				<strong>{l s='Importés:' mod='snt_doc_checker'}</strong> <span id="imported-documents">0</span>
			</div>
		</div>
		<div class="col-md-3">
			<div class="alert alert-warning">
				<strong>{l s='Erreurs:' mod='snt_doc_checker'}</strong> <span id="error-documents">0</span>
			</div>
		</div>
		<div class="col-md-3">
			<div class="alert alert-primary">
				<strong>{l s='Restants:' mod='snt_doc_checker'}</strong> <span id="remaining-documents">0</span>
			</div>
		</div>
	</div>
	<div id="import-logs" style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
		<h4>{l s='Logs d\'importation:' mod='snt_doc_checker'}</h4>
		<div id="log-content"></div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	var importInProgress = false;
	var importCancelled = false;
	var totalDocuments = 0;
	var importedCount = 0;
	var errorCount = 0;
	var batchSize = 10;
	var consecutiveEmptyBatches = 0;
	var maxConsecutiveEmptyBatches = 3; // Arrêter après 3 lots vides consécutifs

	$('#import-documents-btn').click(function() {
		if (importInProgress) return;
		
		if (!confirm('{l s='Êtes-vous sûr de vouloir importer tous les documents de l\'ancien module ?' mod='snt_doc_checker'}')) {
			return;
		}

		startImport();
	});

	$('#cancel-import-btn').click(function() {
		importCancelled = true;
		$(this).prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> {l s='Annulation...' mod='snt_doc_checker'}');
		addLog('Import annulé par l\'utilisateur', 'warning');
	});

	function startImport() {
		importInProgress = true;
		importCancelled = false;
		totalDocuments = 0;
		importedCount = 0;
		errorCount = 0;
		consecutiveEmptyBatches = 0;

		$('#import-documents-btn').prop('disabled', true);
		$('#cancel-import-btn').show();
		$('#import-progress-panel').show();
		$('#log-content').empty(); // Vider les logs précédents
		
		addLog('Démarrage de l\'importation...', 'info');
		
		// Compter d'abord le nombre total de documents
		countDocuments();
	}

	function countDocuments() {
		$.ajax({
			url: window.location.href,
			method: 'POST',
			data: {
				ajax: 1,
				action: 'count_documents'
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					totalDocuments = response.total;
					$('#total-documents').text(totalDocuments);
					$('#remaining-documents').text(totalDocuments);
					
					if (totalDocuments > 0) {
						addLog('Total de ' + totalDocuments + ' documents à importer', 'info');
						processNextBatch();
					} else {
						addLog('Aucun document à importer', 'warning');
						endImport();
					}
				} else {
					addLog('Erreur: ' + response.message, 'error');
					endImport();
				}
			},
			error: function(xhr, status, error) {
				addLog('Erreur de communication avec le serveur: ' + error, 'error');
				endImport();
			}
		});
	}

	function processNextBatch() {
		if (importCancelled) {
			endImport();
			return;
		}

		// Vérifier si nous avons eu trop de lots vides consécutifs
		if (consecutiveEmptyBatches >= maxConsecutiveEmptyBatches) {
			addLog('Arrêt de l\'import: trop de lots vides consécutifs (' + consecutiveEmptyBatches + ')', 'warning');
			endImport();
			return;
		}

		// Calculer le numéro de lot pour l'affichage
		var batchNumber = Math.floor(importedCount / batchSize) + 1;
		addLog('Traitement du lot ' + batchNumber + '...', 'info');

		$.ajax({
			url: window.location.href,
			method: 'POST',
			data: {
				ajax: 1,
				action: 'import_batch',
				offset: 0, // On n'utilise plus l'offset car la requête SQL gère déjà les doublons
				limit: batchSize
			},
			dataType: 'json',
			timeout: 30000, // Timeout de 30 secondes
			success: function(response) {
				if (response.success) {
					// Vérifier si le lot est vide
					if (response.is_empty || response.processed === 0) {
						consecutiveEmptyBatches++;
						addLog('Lot vide détecté (' + consecutiveEmptyBatches + '/' + maxConsecutiveEmptyBatches + ')', 'warning');
						
						// Continuer avec le lot suivant après un délai plus long
						setTimeout(function() {
							processNextBatch();
						}, 500);
						return;
					}

					// Réinitialiser le compteur de lots vides
					consecutiveEmptyBatches = 0;

					// Mettre à jour les compteurs
					importedCount += response.imported;
					errorCount += response.errors;

					updateProgress();
					
					// Logs détaillés
					var logMessage = 'Lot traité: ' + response.processed + ' documents traités, ' + 
									response.imported + ' importés, ' + response.errors + ' erreurs';
					addLog(logMessage, response.imported > 0 ? 'success' : 'warning');

					// Afficher les détails des erreurs si présentes
					if (response.error_details && response.error_details.length > 0) {
						for (var i = 0; i < Math.min(response.error_details.length, 3); i++) {
							addLog('- ' + response.error_details[i], 'error');
						}
						if (response.error_details.length > 3) {
							addLog('- ... et ' + (response.error_details.length - 3) + ' autres erreurs', 'error');
						}
					}

					// Continuer avec le lot suivant après un délai
					setTimeout(function() {
						processNextBatch();
					}, 100);
					
				} else {
					addLog('Erreur: ' + response.message, 'error');
					endImport();
				}
			},
			error: function(xhr, status, error) {
				if (status === 'timeout') {
					addLog('Timeout lors du traitement du lot - Nouvelle tentative...', 'warning');
					// Réessayer après un délai
					setTimeout(function() {
						processNextBatch();
					}, 1000);
				} else {
					addLog('Erreur de communication avec le serveur: ' + error, 'error');
					endImport();
				}
			}
		});
	}

	function updateProgress() {
		var processed = importedCount + errorCount;
		var percentage = totalDocuments > 0 ? Math.round((processed / totalDocuments) * 100) : 0;
		var remaining = Math.max(0, totalDocuments - processed);

		$('#import-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
		$('#progress-text').text(percentage + '%');
		$('#imported-documents').text(importedCount);
		$('#error-documents').text(errorCount);
		$('#remaining-documents').text(remaining);
	}

	function addLog(message, type) {
		var className = 'text-info';
		if (type === 'success') className = 'text-success';
		else if (type === 'error') className = 'text-danger';
		else if (type === 'warning') className = 'text-warning';

		var timestamp = new Date().toLocaleTimeString();
		$('#log-content').append('<div class="' + className + '">[' + timestamp + '] ' + message + '</div>');
		
		// Garder seulement les 100 derniers logs pour éviter les problèmes de performance
		var logElements = $('#log-content div');
		if (logElements.length > 100) {
			logElements.slice(0, logElements.length - 100).remove();
		}
		
		$('#import-logs').scrollTop($('#import-logs')[0].scrollHeight);
	}

	function endImport() {
		importInProgress = false;
		$('#import-documents-btn').prop('disabled', false);
		$('#cancel-import-btn').hide().prop('disabled', false).html('<i class="icon-stop"></i> {l s='Annuler' mod='snt_doc_checker'}');
		
		if (!importCancelled) {
			var message = 'Import terminé: ' + importedCount + ' documents importés';
			if (errorCount > 0) {
				message += ', ' + errorCount + ' erreurs';
			}
			addLog(message, importedCount > 0 ? 'success' : 'warning');
			
			// Recompter les documents restants
			setTimeout(function() {
				countDocuments();
			}, 1000);
		}
	}
});
</script>
{/if}
