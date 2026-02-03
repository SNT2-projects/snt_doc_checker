{* Template pour les informations de configuration du cron *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-clock-o"></i> Configuration du CRON - Vérification automatique des documents expirés
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <h4><i class="icon-info"></i> Information</h4>
            <p>Ce CRON permet de vérifier automatiquement les documents expirés et de mettre à jour leur statut.</p>
            <p><strong>Fréquence recommandée :</strong> Une fois par jour (de préférence la nuit)</p>
        </div>

        {if $cron_configured}
            <div class="row">
                <div class="col-md-6">
                    <h4><i class="icon-link"></i> URL du CRON</h4>
                    <div class="form-group">
                        <label>URL à appeler :</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{$cron_url}" readonly id="cronUrl">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="copyToClipboard('cronUrl')" title="Copier l'URL">
                                    <i class="icon-copy"></i>
                                </button>
                            </span>
                        </div>
                        <small class="help-block">Cette URL est protégée par une clé de sécurité.</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h4><i class="icon-key"></i> Clé de sécurité</h4>
                    <div class="form-group">
                        <label>Clé actuelle :</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{$secure_key}" readonly id="secureKey">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="copyToClipboard('secureKey')" title="Copier la clé">
                                    <i class="icon-copy"></i>
                                </button>
                                <button class="btn btn-warning" type="button" onclick="regenerateSecureKey()" title="Régénérer la clé">
                                    <i class="icon-refresh"></i>
                                </button>
                            </span>
                        </div>
                        <small class="help-block">Cette clé protège l'accès au CRON.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4><i class="icon-terminal"></i> Commande CRON recommandée</h4>
                    <div class="form-group">
                        <label>Ligne à ajouter dans votre crontab :</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{$cron_command}" readonly id="cronCommand">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="copyToClipboard('cronCommand')" title="Copier la commande">
                                    <i class="icon-copy"></i>
                                </button>
                            </span>
                        </div>
                        <small class="help-block">Cette commande exécute le CRON tous les jours à 2h du matin.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h4><i class="icon-test"></i> Test du CRON</h4>
                    <p>Vous pouvez tester le CRON en cliquant sur le bouton ci-dessous :</p>
                    <button class="btn btn-primary" onclick="testCron()" id="testCronBtn">
                        <i class="icon-play"></i> Tester le CRON
                    </button>
                    <div id="testResult" style="margin-top: 10px;"></div>
                </div>
                
                <div class="col-md-6">
                    <h4><i class="icon-chart"></i> Dernière exécution</h4>
                    {if $last_execution}
                        <p><strong>Date :</strong> {$last_execution.date}</p>
                        <p><strong>Documents traités :</strong> {$last_execution.processed}</p>
                        <p><strong>Documents expirés :</strong> {$last_execution.expired}</p>
                        <p><strong>Statut :</strong> 
                            <span class="label label-{if $last_execution.success}success{else}danger{/if}">
                                {if $last_execution.success}Succès{else}Erreur{/if}
                            </span>
                        </p>
                    {else}
                        <p class="text-muted">Aucune exécution enregistrée</p>
                    {/if}
                </div>
            </div>

        {else}
            <div class="alert alert-warning">
                <h4><i class="icon-warning"></i> Configuration requise</h4>
                <p>Le CRON n'est pas encore configuré. Veuillez :</p>
                <ol>
                    <li>Activer la vérification quotidienne dans l'onglet "Automatisation"</li>
                    <li>Enregistrer la configuration</li>
                    <li>Revenir sur cette page pour obtenir l'URL du CRON</li>
                </ol>
            </div>
        {/if}

        <div class="alert alert-info">
            <h4><i class="icon-list"></i> Que fait ce CRON ?</h4>
            <ul>
                <li><strong>Scanne tous les documents</strong> avec une date d'expiration définie</li>
                <li><strong>Identifie les documents expirés</strong> (date dépassée)</li>
                <li><strong>Met à jour leur statut</strong> vers "expired"</li>
                <li><strong>Applique le même traitement</strong> aux documents liés (recto/verso)</li>
                <li><strong>Enregistre l'activité</strong> dans les logs du système</li>
                <li><strong>Génère un rapport</strong> de l'exécution</li>
            </ul>
        </div>

        <div class="alert alert-warning">
            <h4><i class="icon-shield"></i> Sécurité</h4>
            <ul>
                <li>L'URL du CRON est protégée par une <strong>clé de sécurité unique</strong></li>
                <li>Seules les requêtes avec la bonne clé sont acceptées</li>
                <li>Vous pouvez régénérer la clé à tout moment</li>
                <li>Toutes les tentatives d'accès sont loggées</li>
            </ul>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    var element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999); // Pour mobile
    
    try {
        document.execCommand('copy');
        showNotification('Copié dans le presse-papiers !', 'success');
    } catch (err) {
        showNotification('Erreur lors de la copie', 'error');
    }
}

function testCron() {
    var btn = document.getElementById('testCronBtn');
    var result = document.getElementById('testResult');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="icon-spinner icon-spin"></i> Test en cours...';
    result.innerHTML = '';
    
    // Appel AJAX pour tester le CRON
    fetch('{$cron_url}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = '<div class="alert alert-success"><strong>Succès !</strong> ' + data.message + 
                    '<br><small>Documents traités: ' + (data.data ? data.data.processed_documents : 0) + 
                    ', Expirés: ' + (data.data ? data.data.expired_documents : 0) + '</small></div>';
            } else {
                result.innerHTML = '<div class="alert alert-danger"><strong>Erreur :</strong> ' + data.message + '</div>';
            }
        })
        .catch(error => {
            result.innerHTML = '<div class="alert alert-danger"><strong>Erreur de communication :</strong> ' + error.message + '</div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-play"></i> Tester le CRON';
        });
}

function regenerateSecureKey() {
    if (confirm('Êtes-vous sûr de vouloir régénérer la clé de sécurité ? Cela invalidera l\'ancienne URL du CRON.')) {
        // Implémentation de la régénération de clé
        showNotification('Fonctionnalité à implémenter', 'info');
    }
}

function showNotification(message, type) {
    // Affichage simple d'une notification
    var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    var notification = '<div class="alert ' + alertClass + ' fade in" style="position: fixed; top: 70px; right: 20px; z-index: 9999; min-width: 300px;">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' + message + '</div>';
    
    document.body.insertAdjacentHTML('beforeend', notification);
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert[style*="position: fixed"]');
        if (alerts.length > 0) {
            alerts[alerts.length - 1].remove();
        }
    }, 3000);
}
</script>

<style>
.input-group .form-control[readonly] {
    background-color: #f9f9f9;
    cursor: text;
}

.btn-group .btn {
    margin-right: 5px;
}

.alert ul {
    margin-bottom: 0;
}

.help-block {
    margin-top: 5px;
    font-size: 12px;
}

#testResult .alert {
    margin-bottom: 0;
}
</style> 