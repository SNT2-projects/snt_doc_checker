{* Template pour l'affichage des documents dans la page de commande *}
<div class="card mt-2 d-print-none">
    <div class="card-header">
        <div class="card-header-title">
            <i class="material-icons mi-description">description</i>
            {l s='Documents du client' mod='snt_doc_checker'}
        </div>
    </div>
    <div class="card-body">
        {if isset($no_restricted_products)}
            <div class="alert alert-info">
                {l s='Cette commande ne contient pas de produits soumis à restriction.' mod='snt_doc_checker'}
            </div>
        {elseif isset($no_documents)}
            <div class="alert alert-danger">
                {l s='Le client n\'a pas encore soumis de documents alors que la commande contient des produits soumis à restriction.' mod='snt_doc_checker'}
                <br>
                <p><b>{l s='Client:' mod='snt_doc_checker'} {$customer->firstname} {$customer->lastname}</b></p>
            </div>
        {else}
            {if isset($has_restricted_products)}
                <div class="alert alert-warning">
                    {l s='Cette commande contient des produits soumis à restriction. Veuillez vérifier les pièces justificatives du client.' mod='snt_doc_checker'}
                </div>
            {/if}

            {if isset($has_documents_without_expiration) && $has_documents_without_expiration}
                <div class="alert alert-danger">
                    {l s='Certains documents ne possèdent pas de date d\'expiration. Veuillez compléter cette information pour une meilleure gestion des documents.' mod='snt_doc_checker'}
                </div>
            {/if}

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr class="column-headers">
                            <th>{l s='ID' mod='snt_doc_checker'}</th>
                            <th>{l s='Type de document' mod='snt_doc_checker'}</th>
                            <th>{l s='Statut' mod='snt_doc_checker'}</th>
                            <th>{l s='Date d\'upload' mod='snt_doc_checker'}</th>
                            <th>{l s='Date d\'expiration' mod='snt_doc_checker'}</th>
                            <th>{l s='Actions' mod='snt_doc_checker'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$documents item=document}
                            <tr>
                                <td>{$document.id}</td>
                                <td>{$document.type}</td>
                                <td>
                                    <span class="badge badge-{$document.status_class}">
                                        {$document.status}
                                    </span>
                                </td>
                                <td>{$document.upload_date|date_format:"%d/%m/%Y"}</td>
                                <td>{$document.dte_expiration}</td>
                                <td>
                                    <a href="{$document.edit_url}" class="btn btn-link"
                                        title="{l s='Éditer' mod='snt_doc_checker'}">
                                        <i class="material-icons">edit</i>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
</div>