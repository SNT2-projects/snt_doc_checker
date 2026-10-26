{* Template pour l'aperçu du document avec navigation, rotation et zoom *}
<div class="document-preview-container">
    {if isset($document_data) && $document_data}
        {* Bannière d'alerte si la date de naissance n'est pas renseignée (mode édition) *}
        {if isset($is_edit_mode) && $is_edit_mode && isset($has_birth_date) && !$has_birth_date}
            <div class="alert alert-danger" style="margin-bottom: 15px;">
                <strong><i class="icon-exclamation-triangle"></i> ATTENTION:</strong>
                La date de naissance du client n'est pas renseignée.
                Elle est <strong>obligatoire</strong> pour pouvoir enregistrer les modifications sur ce document (sauf pour le passer au statut "Refusé").
                <button type="button" class="btn btn-warning btn-sm pull-right" onclick="openBirthDateModal({$document_data.id_customer}, '')">
                    <i class="icon-calendar"></i> Définir la date de naissance
                </button>
                <div class="clearfix"></div>
            </div>
        {/if}

        {* Bannière d'info si la date d'expiration n'est pas définie (mode édition) *}
        {if isset($is_edit_mode) && $is_edit_mode}
            {if !isset($document_data.dte_expiration) || !$document_data.dte_expiration || $document_data.dte_expiration == '0000-00-00'}
                <div class="alert alert-warning" style="margin-bottom: 15px;">
                    <i class="icon-warning"></i> <strong>Information:</strong>
                    La date d'expiration n'est pas définie. Elle sera <strong>obligatoire</strong> pour valider ce document (statut "Validé").
                </div>
            {/if}
        {/if}

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-eye"></i> Aperçu du document
                <div class="pull-right">
                    {if isset($file_extension)}
                        {assign var="image_extensions" value=['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']}
                        {if in_array($file_extension|lower, $image_extensions)}
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-default" id="zoom-in" title="Zoom +">
                                    <i class="icon-plus"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="zoom-out" title="Zoom -">
                                    <i class="icon-minus"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="zoom-fit" title="Ajuster à la fenêtre">
                                    <i class="icon-resize-full"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="rotate-left" title="Rotation gauche">
                                    <i class="icon-rotate-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="rotate-right" title="Rotation droite">
                                    <i class="icon-rotate-right"></i>
                                </button>
                            </div>
                        {/if}
                    {/if}
                </div>
            </div>
            <div class="panel-body">
                {if isset($temporary_url) && $temporary_url}
                    {* Aperçu selon le type de fichier *}
                    {if isset($file_extension)}
                        {* Images *}
                        {assign var="image_extensions" value=['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']}
                        {if in_array($file_extension|lower, $image_extensions)}
                            <div class="document-preview-image-container">
                                <div class="image-viewer" id="image-viewer">
                                    <img src="{$temporary_url}" alt="Aperçu du document" id="preview-image" class="preview-image">
                                </div>
                                <div class="image-controls">
                                    <div class="zoom-info">
                                        <span id="zoom-level">100%</span>
                                    </div>
                                </div>
                            </div>
                        {* PDF *}
                        {elseif $file_extension|lower == 'pdf'}
                            <div class="document-preview-pdf-container">
                            <iframe src="{$temporary_url}" width="100%" height="600px" frameborder="0" style="border: 1px solid #ddd; border-radius: 4px;"></iframe>
                            </div>
                        {else}
                            {* Autres types de fichiers *}
                            <div class="document-preview-unknown">
                                <div class="alert alert-warning">
                                    <i class="icon-question"></i> Type de fichier non supporté pour la prévisualisation
                                </div>
                                <a href="{$temporary_url}" target="_blank" class="btn btn-primary">
                                    <i class="icon-download"></i> Télécharger le fichier
                                </a>
                            </div>
                        {/if}
                    {else}
                        {* Extension non détectée *}
                        <div class="document-preview-unknown">
                            <div class="alert alert-warning">
                                <i class="icon-question"></i> Type de fichier non détecté
                            </div>
                            <a href="{$temporary_url}" target="_blank" class="btn btn-primary">
                                <i class="icon-download"></i> Télécharger le fichier
                            </a>
                        </div>
                    {/if}
                    
                    {* Informations sur le lien temporaire *}
                    <div class="document-info" style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
                        <small class="text-muted">
                            <i class="icon-info"></i> 
                            Lien temporaire valide 60 minutes | 
                            Nom du fichier: {$file_name|escape:'html':'UTF-8'}
                        </small>
                    </div>
                {else}
                    {* Pas de lien temporaire disponible *}
                    <div class="alert alert-danger">
                        <i class="icon-exclamation-triangle"></i> 
                        Impossible de générer un lien d'aperçu pour ce document.
                        {if isset($document_data.link_document) && $document_data.link_document}
                            <br>Fichier: {$document_data.link_document|escape:'html':'UTF-8'}
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
        
        {* Informations du client *}
        {if isset($customer_data) && $customer_data}
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-user"></i> Informations du client
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <td><strong>ID Client:</strong></td>
                                    <td>{$customer_data.id_customer|escape:'html':'UTF-8'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Prénom:</strong></td>
                                    <td>{$customer_data.firstname|escape:'html':'UTF-8'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nom:</strong></td>
                                    <td>{$customer_data.lastname|escape:'html':'UTF-8'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{$customer_data.email|escape:'html':'UTF-8'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date de création:</strong></td>
                                    <td>{$customer_data.date_add|escape:'html':'UTF-8'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type de compte:</strong></td>
                                    <td>
                                        <span class="label label-{if $customer_data.is_guest == '1'}warning{else}primary{/if}">
                                            {if $customer_data.is_guest == '1'}Invité{else}Compte permanent{/if}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Statut:</strong></td>
                                    <td>
                                        <span class="label label-{if $customer_data.active == '1'}success{else}danger{/if}">
                                            {if $customer_data.active == '1'}Actif{else}Inactif{/if}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Date de naissance:</strong></td>
                                    <td>
                                        {if isset($customer_birth_info) && $customer_birth_info && !empty($customer_birth_info.birth_date) && $customer_birth_info.birth_date != '0000-00-00'}
                                            {$customer_birth_info.birth_date|date_format:"%d/%m/%Y"}
                                            <span class="label label-success"><i class="icon-check"></i> Renseignée</span>
                                            {if isset($is_edit_mode) && $is_edit_mode}
                                                <button type="button" class="btn btn-xs btn-primary" style="margin-left: 5px;" onclick="openBirthDateModal({$customer_data.id_customer}, '{$customer_birth_info.birth_date}')">
                                                    <i class="icon-pencil"></i> Modifier
                                                </button>
                                            {/if}
                                        {else}
                                            <span class="label label-danger"><i class="icon-warning"></i> Non renseignée</span>
                                            {if isset($is_edit_mode) && $is_edit_mode}
                                                <button type="button" class="btn btn-xs btn-warning" onclick="openBirthDateModal({$customer_data.id_customer}, '')">
                                                    <i class="icon-calendar"></i> Définir
                                                </button>
                                            {/if}
                                        {/if}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        {else}
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-user"></i> Informations du client
                </div>
                <div class="panel-body">
                    <div class="alert alert-warning">
                        Aucune information client disponible.
                    </div>
                </div>
            </div>
        {/if}
        
        {* Informations détaillées du document *}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-info"></i> Informations du document
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>Nom du fichier:</strong></td>
                                <td>{$file_name|escape:'html':'UTF-8'}</td>
                            </tr>
                            <tr>
                                <td><strong>Type de document:</strong></td>
                                <td class="document-type-label" data-type="{$document_data.type_document|escape:'html':'UTF-8'}">{$document_data.type_document|escape:'html':'UTF-8'}</td>
                            </tr>
                            <tr>
                                <td><strong>Statut:</strong></td>
                                <td>
                                    <span class="label label-{if $document_data.statut_document == 'valid'}success{elseif $document_data.statut_document == 'pending'}warning{elseif $document_data.statut_document == 'refused'}danger{elseif $document_data.statut_document == 'expired'}danger{else}default{/if}">
                                        {if $document_data.statut_document == 'valid'}validé{elseif $document_data.statut_document == 'pending'}en attente{elseif $document_data.statut_document == 'refused'}refusé{elseif $document_data.statut_document == 'expired'}expiré{else}inconnu{/if}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>Date d'upload:</strong></td>
                                <td>{$document_data.dte_upload|escape:'html':'UTF-8'}</td>
                            </tr>
                            <tr>
                                <td><strong>Dernière mise à jour:</strong></td>
                                <td>{$document_data.dte_update|escape:'html':'UTF-8'}</td>
                            </tr>
                            <tr>
                                <td><strong>Extension:</strong></td>
                                <td>{$file_extension|upper|escape:'html':'UTF-8'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                {if isset($document_data.comment_document) && $document_data.comment_document}
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Commentaire:</h4>
                            <div class="well">
                                {$document_data.comment_document|escape:'html':'UTF-8'|nl2br}
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
        
        {* Gestion des dates d'expiration et liens *}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-calendar"></i> Gestion de l'expiration et des liens entre documents
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Date d'expiration</h4>
                        {if isset($is_document_associated) && $is_document_associated}
                            {* Document associé - date gérée par le document principal *}
                            <div class="alert alert-warning">
                                <strong>La date d'expiration est présente sur un document attaché.</strong>
                                {if isset($document_data.dte_expiration) && $document_data.dte_expiration && $document_data.dte_expiration != '0000-00-00'}
                                    <br><br><strong>Date d'expiration actuelle:</strong> {$document_data.dte_expiration|date_format:"%d/%m/%Y"}
                                    {assign var="expiration_date" value=$document_data.dte_expiration|date_format:"%Y-%m-%d"}
                                    {assign var="current_date" value=$smarty.now|date_format:"%Y-%m-%d"}
                                    {if $expiration_date < $current_date}
                                        <br><span class="label label-danger">Document expiré</span>
                                    {elseif ($expiration_date|strtotime) - ($current_date|strtotime) <= 2592000}
                                        <br><span class="label label-warning">Expire bientôt (moins de 30 jours)</span>
                                    {else}
                                        <br><span class="label label-success">Valide</span>
                                    {/if}
                                {/if}
                            </div>
                        {else}
                            {* Document principal ou sans liaison *}
                            {if isset($document_data.dte_expiration) && $document_data.dte_expiration && $document_data.dte_expiration != '0000-00-00'}
                                <div class="alert alert-info">
                                    <i class="icon-calendar"></i> 
                                    <strong>Date d'expiration:</strong> {$document_data.dte_expiration|date_format:"%d/%m/%Y"}
                                    {assign var="expiration_date" value=$document_data.dte_expiration|date_format:"%Y-%m-%d"}
                                    {assign var="current_date" value=$smarty.now|date_format:"%Y-%m-%d"}
                                    {if $expiration_date < $current_date}
                                        <br><span class="label label-danger">Document expiré</span>
                                    {elseif ($expiration_date|strtotime) - ($current_date|strtotime) <= 2592000}
                                        <br><span class="label label-warning">Expire bientôt (moins de 30 jours)</span>
                                    {else}
                                        <br><span class="label label-success">Valide</span>
                                    {/if}
                                    {if isset($is_document_principal) && $is_document_principal}
                                        <br><br><span class="label label-primary"><i class="icon-info"></i> Ce document partage sa date d'expiration avec un autre document</span>
                                    {/if}
                                </div>
                            {else}
                                <div class="alert alert-warning">
                                    Aucune date d'expiration définie
                                    {if isset($is_document_principal) && $is_document_principal}
                                        <br><br><span class="label label-primary"><i class="icon-info"></i> Ce document partage sa date d'expiration avec un autre document</span>
                                    {/if}
                                </div>
                            {/if}
                        {/if}
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Document lié</h4>
                        {if isset($linked_document) && $linked_document}
                            <div class="alert alert-success">
                                <strong class="badge badge-success">
                                    <i class="icon-link"></i> 
                                    Liaison active
                                </strong>
                                {if isset($is_document_principal) && $is_document_principal}
                                    <span class="label label-info" style="margin-left: 10px;">
                                        <i class="icon-paperclip"></i> Document associé
                                    </span>
                                {elseif isset($is_document_associated) && $is_document_associated}
                                    <span class="label label-primary" style="margin-left: 10px;">
                                        <i class="icon-star"></i> Document principal
                                    </span>
                                {/if}
                                <br><br>
                                <b class="linked-document-type" data-type="{$linked_document.type_document|escape:'html':'UTF-8'}">{$linked_document.type_document|escape:'html':'UTF-8'}</b><br>{$linked_document.link_document|basename|escape:'html':'UTF-8'}
                                <br>
                                <small class="text-muted">Date d'upload: {$linked_document.dte_upload|date_format:"%d/%m/%Y %H:%M"}</small>
                                {if isset($is_document_principal) && $is_document_principal}
                                    <br><small class="text-muted"><i class="icon-info"></i> Ce document gère la date d'expiration du document associé</small>
                                {elseif isset($is_document_associated) && $is_document_associated}
                                    <br><small class="text-muted"> Le document principal gère votre date d'expiration</small>
                                {/if}
                                <br>
                                <div style="margin-top: 5px;">
                                    <button type="button" class="btn btn-xs btn-info" onclick="previewLinkedDocument({$linked_document.id_data})">
                                        <i class="icon-eye"></i> Aperçu
                                    </button>
                                    {if isset($is_edit_mode) && $is_edit_mode}
                                        <button type="button" class="btn btn-xs btn-danger" onclick="unlinkDocument({$document_data.id_data})">
                                            <i class="icon-unlink"></i> Délier
                                        </button>
                                    {/if}
                                </div>
                            </div>
                        {else}
                            <div class="alert alert-info">
                                Aucun document lié
                            </div>
                        {/if}
                    </div>
                </div>
                
                {* Actions disponibles en mode édition seulement *}
                {if isset($is_edit_mode) && $is_edit_mode}
                    <div class="row">
                        <div class="col-md-12">
                            {* Afficher les boutons seulement si le document n'est pas lié *}
                            {if (!isset($is_document_principal) || !$is_document_principal) && (!isset($is_document_associated) || !$is_document_associated)}
                                <div class="btn-group">
                                    <button type="button" style="text-transform: uppercase; display: flex; gap:4px;" class="btn btn-primary" onclick="setExpirationDate({$document_data.id_data})">
                                        <i class="icon-calendar"></i> Ce document a une date : définir la date d'expiration du document
                                    </button>
                                    <button type="button" style="text-transform: uppercase; display: flex; gap:4px;" class="btn btn-danger" onclick="openLinkDocumentModal({$document_data.id_data}, {$document_data.id_customer})">
                                        <i class="icon-link"></i> Ce document n'a pas de date : lier le document à un autre document
                                    </button>
                                </div>
                            {else}
                                {* Document lié - afficher uniquement le bouton délier *}
                                <div class="btn-group">
                                    {if isset($is_document_principal) && $is_document_principal}
                                        <button type="button" style="display: flex; gap:4px;" class="btn btn-primary" onclick="setExpirationDate({$document_data.id_data})">
                                            <i class="icon-calendar"></i> Modifier la date d'expiration
                                        </button>
                                    {/if}
                                </div>
                                {if isset($is_document_associated) && $is_document_associated}
                                    <div class="alert alert-info" style="margin-top: 10px;">
                                         Ce document est associé. Seul le document principal peut définir et modifier la date d'expiration.
                                    </div>
                                {/if}
                            {/if}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    {else}
        <div class="alert alert-warning">
            <i class="icon-exclamation-triangle"></i> Aucune donnée de document disponible.
        </div>
    {/if}
</div>

{* Inclusion de PDFObject.js pour la prévisualisation PDF *}
{if isset($file_extension) && $file_extension|lower == 'pdf'}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.2.8/pdfobject.min.js"></script>
{/if}

{* Modal pour la sélection de documents à lier *}
<div class="modal fade" id="linkDocumentModal" tabindex="-1" role="dialog" aria-labelledby="linkDocumentModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="linkDocumentModalLabel">
                    <i class="icon-link"></i> Sélectionner un document à lier
                </h4>
            </div>
            <div class="modal-body">
                <div id="linkDocumentLoader" class="text-center" style="display: none;">
                    <i class="icon-spinner icon-spin"></i> Chargement des documents...
                </div>
                <div id="linkDocumentContent">
                    <h4 class="bold">Sélectionnez un document du même client à lier avec le document actuel. </h4>
                    <strong class="text-danger">Le document sélectionné gérera la date d'expiration.</strong>
                    <div id="documentsGrid" class="row">
                        {* Les documents seront chargés via AJAX *}
                    </div>
                </div>
                <div id="linkDocumentError" class="alert alert-danger" style="display: none;">
                    <i class="icon-exclamation-triangle"></i> <span id="linkDocumentErrorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="icon-times"></i> Annuler
                </button>
            </div>
        </div>
    </div>
</div>

{* Modal pour définir la date d'expiration *}
<div class="modal fade" id="expirationDateModal" tabindex="-1" role="dialog" aria-labelledby="expirationDateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="expirationDateModalLabel">
                    <i class="icon-calendar"></i> Définir la date d'expiration
                </h4>
            </div>
            <div class="modal-body">
                <form id="expirationDateForm">
                    <div class="form-group">
                        <label for="expirationDate">Date d'expiration:</label>
                        <input type="date" class="form-control" id="expirationDate" name="expiration_date" required>
                        <small class="help-block">Sélectionnez la date d'expiration du document</small>
                    </div>
                    <input type="hidden" id="documentIdForExpiration" name="document_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="icon-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveExpirationDate()">
                    <i class="icon-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

{* Modal pour définir la date de naissance du client *}
<div class="modal fade" id="birthDateModal" tabindex="-1" role="dialog" aria-labelledby="birthDateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="birthDateModalLabel">
                    <i class="icon-calendar"></i> Définir la date de naissance du client
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    La date de naissance est liée au client et s'appliquera à tous ses documents.
                </div>
                <form id="birthDateForm">
                    <div class="form-group">
                        <label for="birthDate">Date de naissance:</label>
                        <input type="date" class="form-control" id="birthDate" name="birth_date" required>
                        <small class="help-block">Sélectionnez la date de naissance du client</small>
                    </div>
                    <input type="hidden" id="customerIdForBirthDate" name="id_customer">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="icon-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveBirthDate()">
                    <i class="icon-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.document-preview-container {
    margin-bottom: 20px;
}

.document-preview-container .panel {
    margin-bottom: 15px;
}

.document-preview-container .panel-heading {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.document-preview-container .alert {
    margin-bottom: 15px;
}

.document-preview-container .btn {
    margin-right: 5px;
}

.document-preview-container .table td {
    vertical-align: middle;
}

.document-preview-container .label {
    font-size: 12px;
    padding: 4px 8px;
}

.document-preview-container .well {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

/* Styles pour la prévisualisation d'image */
.document-preview-image-container {
    text-align: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    min-height: 400px;
    position: relative;
}

.image-viewer {
    width: 600px;
    height: 400px;
    display: inline-block;
    position: relative;
    overflow: hidden;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.preview-image {
    display: block;
    max-width: 100%;
    max-height: 600px;
    transition: transform 0.1s ease;
    cursor: grab;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    will-change: transform;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
}

.preview-image:active {
    cursor: grabbing;
    transition: none;
}

.preview-image:hover {
    cursor: grab;
}

.image-controls {
    margin-top: 15px;
}

.zoom-info {
    display: inline-block;
    padding: 5px 10px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    color: #666;
}

/* Styles pour la prévisualisation PDF */
.document-preview-pdf-container {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
}

.pdf-viewer {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    min-height: 600px;
}

#pdf-container {
    width: 100%;
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .document-preview-image-container {
        padding: 10px;
    }
    
    .pdf-viewer {
        padding: 10px;
    }
    
    .preview-image {
        max-height: 400px;
    }
    
    #pdf-container {
        height: 400px;
    }
}

/* Styles pour les modals et la gestion des liens */
.document-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.document-card:hover {
    border-color: #337ab7;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.document-card.selected {
    border-color: #337ab7;
    background-color: #f0f8ff;
    box-shadow: 0 0 10px rgba(51, 122, 183, 0.3);
}

.document-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.document-type {
    font-weight: bold;
    color: #337ab7;
}

.document-status {
    font-size: 12px;
}

.document-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.document-preview-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 10px;
    float: left;
}

.document-actions {
    margin-top: 10px;
}

.modal-lg .modal-dialog {
    width: 90%;
    max-width: 1000px;
}

.link-document-grid {
    max-height: 400px;
    overflow-y: auto;
}

/* Styles pour les alertes d'expiration */
.expiration-status {
    display: inline-block;
    margin-left: 10px;
}

.document-link-info {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-top: 10px;
}

.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Styles pour les labels de statut principal/associé */
.document-status-label {
    display: inline-block;
    margin-left: 10px;
    font-size: 11px;
    padding: 3px 6px;
}

.document-status-label i {
    margin-right: 3px;
}

/* Styles pour les boutons désactivés */
.btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn[disabled]:hover {
    opacity: 0.6;
}

/* Amélioration des alertes d'expiration */
.expiration-warning {
    border-left: 4px solid #f39c12;
    background-color: #fdf2e9;
}

.expiration-managed-by-principal {
    border-left: 4px solid #3498db;
    background-color: #ebf3fd;
}

/* Style pour les documents principaux */
.principal-document-info {
    background-color: #e8f6ff;
    border: 1px solid #bee5eb;
    border-radius: 4px;
    padding: 8px;
    margin: 5px 0;
}

/* Style pour les documents associés */
.associated-document-info {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 8px;
    margin: 5px 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fileExtension = '{$file_extension|lower}';
    var temporaryUrl = '{$temporary_url}';
    
    // Variables globales pour les images
    var currentZoom = 1;
    var currentRotation = 0;
    var isDragging = false;
    var startX, startY, translateX = 0, translateY = 0;
    
    // Remplacer les types de documents par leurs labels lisibles
    var documentTypeElements = document.querySelectorAll('.linked-document-type, .document-type-label');
    documentTypeElements.forEach(function(element) {
        var documentType = element.getAttribute('data-type');
        if (documentType) {
            element.textContent = getDocumentTypeLabel(documentType);
        }
    });

    {* Validation côté client : bloquer l'enregistrement si la date de naissance du client n'est pas renseignée,
       sauf pour un refus (le document peut justement être refusé faute de date de naissance) *}
    {if isset($is_edit_mode) && $is_edit_mode}
        var hasBirthDate = {if isset($has_birth_date) && $has_birth_date}true{else}false{/if};
        var birthDateCustomerId = {if isset($document_data.id_customer)}{$document_data.id_customer}{else}0{/if};
        var saveButton = document.querySelector('button[name="submitUpdate"], button[name="submitUpdateStay"]');
        var docForm = saveButton ? saveButton.closest('form') : null;
        if (docForm) {
            docForm.addEventListener('submit', function(e) {
                var statutField = docForm.querySelector('[name="statut_document"]');
                var isRefus = statutField && statutField.value === 'refused';
                if (!hasBirthDate && !isRefus) {
                    e.preventDefault();
                    alert("Impossible d'enregistrer : la date de naissance du client est obligatoire.\n\nVeuillez la renseigner avant d'enregistrer ce document.");
                    if (typeof openBirthDateModal === 'function' && birthDateCustomerId) {
                        openBirthDateModal(birthDateCustomerId, '');
                    }
                }
            });
        }
    {/if}

    // Gestion des images
    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].indexOf(fileExtension) !== -1) {
        var image = document.getElementById('preview-image');
        var zoomInBtn = document.getElementById('zoom-in');
        var zoomOutBtn = document.getElementById('zoom-out');
        var zoomFitBtn = document.getElementById('zoom-fit');
        var rotateLeftBtn = document.getElementById('rotate-left');
        var rotateRightBtn = document.getElementById('rotate-right');
        var zoomLevel = document.getElementById('zoom-level');
        
        // Zoom
        zoomInBtn.addEventListener('click', function() {
            currentZoom = Math.min(currentZoom * 1.2, 5);
            updateImageTransform();
        });
        
        zoomOutBtn.addEventListener('click', function() {
            currentZoom = Math.max(currentZoom / 1.2, 0.1);
            updateImageTransform();
        });
        
        zoomFitBtn.addEventListener('click', function() {
            currentZoom = 1;
            currentRotation = 0;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        });
        
        // Rotation
        rotateLeftBtn.addEventListener('click', function() {
            currentRotation -= 90;
            updateImageTransform();
        });
        
        rotateRightBtn.addEventListener('click', function() {
            currentRotation += 90;
            updateImageTransform();
        });
        
        // Drag and drop
        image.addEventListener('mousedown', startDragging);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDragging);
        
        // Touch events pour mobile
        image.addEventListener('touchstart', startDragging);
        document.addEventListener('touchmove', drag);
        document.addEventListener('touchend', stopDragging);
        
        function updateImageTransform() {
            image.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) rotate(' + currentRotation + 'deg) scale(' + currentZoom + ')';
            zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
        }
        
        function startDragging(e) {
            isDragging = true;
            var clientX = e.clientX || e.touches[0].clientX;
            var clientY = e.clientY || e.touches[0].clientY;
            startX = clientX - translateX;
            startY = clientY - translateY;
            image.style.cursor = 'grabbing';
            e.preventDefault();
        }
        
        function drag(e) {
            if (isDragging) {
                e.preventDefault();
                var clientX = e.clientX || e.touches[0].clientX;
                var clientY = e.clientY || e.touches[0].clientY;
                translateX = clientX - startX;
                translateY = clientY - startY;
                
                // Utiliser requestAnimationFrame pour une animation fluide
                requestAnimationFrame(function() {
                    updateImageTransform();
                });
            }
        }
        
        function stopDragging() {
            isDragging = false;
            image.style.cursor = 'grab';
        }
    }
});

// Variables globales pour la gestion des liens
var currentDocumentId = 0;
var currentCustomerId = 0;
var selectedDocumentForLink = null;

// Fonction pour ouvrir la modal de liaison de documents
function openLinkDocumentModal(documentId, customerId) {
    currentDocumentId = documentId;
    currentCustomerId = customerId;
    selectedDocumentForLink = null;
    
    // Afficher la modal
    $('#linkDocumentModal').modal('show');
    
    // Charger les documents du client
    loadCustomerDocuments(customerId, documentId);
}

// Fonction pour charger les documents du client
function loadCustomerDocuments(customerId, excludeDocumentId) {
    $('#linkDocumentLoader').show();
    $('#linkDocumentContent').hide();
    $('#linkDocumentError').hide();
    
    $.ajax({
        url: '{$link->getAdminLink("AdminMenuTabsDocChecker")}',
        type: 'POST',
        data: {
            ajax: true,
            action: 'getCustomerDocuments',
            id_customer: customerId,
            exclude_document: excludeDocumentId
        },
        dataType: 'json',
        success: function(response) {
            $('#linkDocumentLoader').hide();
            
            if (response.success && response.documents) {
                displayCustomerDocuments(response.documents);
                $('#linkDocumentContent').show();
            } else {
                showLinkError(response.message || 'Erreur lors du chargement des documents');
            }
        },
        error: function() {
            $('#linkDocumentLoader').hide();
            showLinkError('Erreur de communication avec le serveur');
        }
    });
}

function getDocumentTypeLabel(type) {
    switch (type) {
        case 'pi':
            return 'Carte Nationale d\'Identité';
        case 'pc':
            return 'Permis de chasse';
        case 'vpc':
            return 'Validation du permis de chasse';
        case 'lfft':
            return 'Licence de la Fédération Française de Tir';
        case 'lffb':
            return 'Licence de la Fédération Française de Biathlon';
        case 'lffbt':
            return 'Licence de la Fédération Française de ball-trap';
        case 'cc':
            return 'Carte de collectionneur';
        case 'pp':
            return 'Passeport';
        case 'pcd':
            return 'Permis de conduire';
        default:
            return 'Autre document';
    }
}


// Fonction pour afficher les documents du client
function displayCustomerDocuments(documents) {
    var grid = $('#documentsGrid');
    grid.empty();
    
    if (documents.length === 0) {
        grid.html('<div class="col-md-12"><div class="alert alert-info">Aucun autre document trouvé pour ce client.</div></div>');
        return;
    }
    
    documents.forEach(function(doc) {
        var statusClass = '';
        var statusText = '';
        
        switch(doc.statut_document) {
            case 'valid':
                statusClass = 'success';
                statusText = 'Validé';
                break;
            case 'pending':
                statusClass = 'warning';
                statusText = 'En attente';
                break;
            case 'refused':
                statusClass = 'danger';
                statusText = 'Refusé';
                break;
            case 'expired':
                statusClass = 'danger';
                statusText = 'Expiré';
                break;
            default:
                statusClass = 'default';
                statusText = 'Inconnu';
        }
        
        // Déterminer le badge de date d'expiration
        var expirationBadge = '';
        var expirationBadgeClass = '';
        var expirationBadgeText = '';
        
        if (doc.dte_expiration && doc.dte_expiration !== '0000-00-00') {
            expirationBadgeClass = 'success';
            expirationBadgeText = 'Date d\'expiration définie';
        } else {
            expirationBadgeClass = 'danger';
            expirationBadgeText = 'Date d\'expiration non définie';
        }
        
        expirationBadge = '<span class="label label-' + expirationBadgeClass + '" style="margin-left: 5px;">' + expirationBadgeText + '</span>';
        
        var cardHtml = '<div class="col-md-6">' +
            '<div class="document-card" data-document-id="' + doc.id_data + '" onclick="selectDocumentForLink(' + doc.id_data + ', this)">' +
                '<div class="document-card-header">' +
                    '<span class="document-type">' + getDocumentTypeLabel(doc.type_document) + '</span>' +
                    '<span class="label label-' + statusClass + ' document-status">' + statusText + '</span>' +
                '</div>' +
                '<div class="document-filename">' + doc.link_document + '</div>' +
                '<div class="document-info">' +
                    'Uploadé le: ' + formatDate(doc.dte_upload) +
                    (doc.dte_expiration && doc.dte_expiration !== '0000-00-00' ? '<br>Expire le: ' + formatDate(doc.dte_expiration) : '') +
                    '<br>' + expirationBadge +
                '</div>' +
                '<div class="document-actions">' +
                    '<button type="button" class="btn btn-xs btn-info" onclick="event.stopPropagation(); previewDocument(' + doc.id_data + ')">' +
                        '<i class="icon-eye"></i> Aperçu' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        grid.append(cardHtml);
    });
    
    // Ajouter le bouton de confirmation
    var confirmButton = '<div class="col-md-12 text-center" style="margin-top: 20px;">' +
        '<button type="button" class="btn btn-success" id="confirmLinkBtn" onclick="confirmDocumentLink()" disabled>' +
            '<i class="icon-link"></i> Confirmer la liaison' +
        '</button>' +
    '</div>';
    
    grid.append(confirmButton);
}

// Fonction pour sélectionner un document pour la liaison
function selectDocumentForLink(documentId, element) {
    // Désélectionner tous les autres documents
    $('.document-card').removeClass('selected');
    
    // Sélectionner le document cliqué
    $(element).addClass('selected');
    selectedDocumentForLink = documentId;
    
    // Activer le bouton de confirmation
    $('#confirmLinkBtn').prop('disabled', false);
}

// Fonction pour confirmer la liaison entre documents
function confirmDocumentLink() {
    if (!selectedDocumentForLink) {
        showLinkError('Veuillez sélectionner un document à lier');
        return;
    }
    
    $.ajax({
        url: '{$link->getAdminLink("AdminMenuTabsDocChecker")}',
        type: 'POST',
        data: {
            ajax: true,
            action: 'linkDocuments',
            document_id: currentDocumentId,
            linked_document_id: selectedDocumentForLink
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#linkDocumentModal').modal('hide');
                location.reload(); // Recharger la page pour voir les changements
            } else {
                showLinkError(response.message || 'Erreur lors de la liaison des documents');
            }
        },
        error: function() {
            showLinkError('Erreur de communication avec le serveur');
        }
    });
}

// Fonction pour délier un document
function unlinkDocument(documentId) {
    if (!confirm('Êtes-vous sûr de vouloir délier ce document ?')) {
        return;
    }
    
    $.ajax({
        url: '{$link->getAdminLink("AdminMenuTabsDocChecker")}',
        type: 'POST',
        data: {
            ajax: true,
            action: 'unlinkDocument',
            document_id: documentId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur: ' + (response.message || 'Impossible de délier le document'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur');
        }
    });
}

// Fonction pour ouvrir la modal de définition de date d'expiration
function setExpirationDate(documentId) {
    $('#documentIdForExpiration').val(documentId);
    $('#expirationDate').val('');
    $('#expirationDateModal').modal('show');
}

// Fonction pour sauvegarder la date d'expiration
function saveExpirationDate() {
    var documentId = $('#documentIdForExpiration').val();
    var expirationDate = $('#expirationDate').val();

    if (!expirationDate) {
        alert('Veuillez sélectionner une date d\'expiration');
        return;
    }

    $.ajax({
        url: '{$link->getAdminLink("AdminMenuTabsDocChecker")}',
        type: 'POST',
        data: {
            ajax: true,
            action: 'setExpirationDate',
            document_id: documentId,
            expiration_date: expirationDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#expirationDateModal').modal('hide');
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur: ' + (response.message || 'Impossible de définir la date d\'expiration'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur');
        }
    });
}

// Fonction pour ouvrir la modal de définition de date de naissance
function openBirthDateModal(customerId, existingDate) {
    $('#customerIdForBirthDate').val(customerId);
    $('#birthDate').val(existingDate || '');
    $('#birthDateModal').modal('show');
}

// Fonction pour sauvegarder la date de naissance
function saveBirthDate() {
    var customerId = $('#customerIdForBirthDate').val();
    var birthDate = $('#birthDate').val();

    if (!birthDate) {
        alert('Veuillez sélectionner une date de naissance');
        return;
    }

    // Valider que la date est dans le passé
    var selectedDate = new Date(birthDate);
    var today = new Date();
    if (selectedDate >= today) {
        alert('La date de naissance doit être dans le passé');
        return;
    }

    $.ajax({
        url: '{$link->getAdminLink("AdminMenuTabsDocChecker")}',
        type: 'POST',
        data: {
            ajax: true,
            action: 'setBirthDate',
            id_customer: customerId,
            birth_date: birthDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#birthDateModal').modal('hide');
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur: ' + (response.message || 'Impossible de définir la date de naissance'));
            }
        },
        error: function() {
            alert('Erreur de communication avec le serveur');
        }
    });
}

// Fonction pour prévisualiser un document lié
function previewLinkedDocument(documentId) {
    var url = '{$link->getAdminLink("AdminMenuTabsDocChecker")}&updatesnt_doc_checker_data&id_data=' + documentId;
    window.open(url, '_blank');
}

// Fonction pour prévisualiser un document depuis la modal
function previewDocument(documentId) {
    var url = '{$link->getAdminLink("AdminMenuTabsDocChecker")}&updatesnt_doc_checker_data&id_data=' + documentId;
    window.open(url, '_blank');
}

// Fonction utilitaire pour formater les dates
function formatDate(dateString) {
    if (!dateString || dateString === '0000-00-00 00:00:00') {
        return 'Non définie';
    }
    
    var date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour afficher les erreurs dans la modal de liaison
function showLinkError(message) {
    $('#linkDocumentErrorText').text(message);
    $('#linkDocumentError').show();
    $('#linkDocumentContent').hide();
}
</script>