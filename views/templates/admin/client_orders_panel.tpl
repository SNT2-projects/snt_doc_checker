{* Encart : commandes du client à approuver depuis la page de validation d'un document *}
<div class="panel" id="clientOrdersPanel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i> Commandes du client à approuver
    </div>

    {if !$erp_configured}
        <div class="alert alert-warning" style="margin: 15px;">
            <i class="icon-warning"></i>
            La connexion à l'<strong>ERP</strong> n'est pas encore configurée.
            Renseignez l'<strong>URL de base</strong> et la <strong>clé API</strong> dans la configuration du module (onglet <strong>ERP / API</strong>) pour activer le déblocage des commandes.
        </div>
    {/if}

    {if $eligible_orders|@count == 0}
        <div class="alert alert-info" style="margin: 15px;">
            <i class="icon-info-circle"></i>
            Aucune commande de ce client n'est en attente d'approbation.
            <br>
            <small>
                (Commandes ayant eu le statut "Demande de pièces : Catégorie C/D", actuellement au statut
                "Importé vers Oasis v2" et contenant au moins un article de catégorie C ou D.)
            </small>
        </div>
    {else}
        <div class="table-responsive">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$eligible_orders item=order}
                        <tr id="clientOrderRow_{$order.id_order}">
                            <td>
                                <a href="{$order.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
                                    <strong>{$order.reference|escape:'html':'UTF-8'}</strong>
                                    <i class="icon-external-link"></i>
                                </a>
                            </td>
                            <td>{$order.date_add|escape:'html':'UTF-8'}</td>
                            <td class="text-right">
                                <button type="button"
                                        class="btn btn-success btn-sm js-approve-order"
                                        data-id-order="{$order.id_order}"
                                        {if !$erp_configured}disabled="disabled"{/if}>
                                    <i class="icon-unlock"></i> Débloquer la commande (ERP)
                                </button>
                                <span class="approve-order-result" style="display:none; margin-left:8px;"></span>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
</div>

<script type="text/javascript">
(function () {
    var unblockUrl = "{$unblock_order_ajax_url|escape:'javascript':'UTF-8'}";

    document.querySelectorAll('#clientOrdersPanel .js-approve-order').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idOrder = btn.getAttribute('data-id-order');
            var resultSpan = btn.parentNode.querySelector('.approve-order-result');

            if (!confirm('Débloquer cette commande dans l\'ERP ?')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="icon-refresh icon-spin"></i> Déblocage en cours...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', unblockUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                var data = {};
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    data = { success: false, message: 'Réponse invalide du serveur.' };
                }

                if (data.success) {
                    btn.className = 'btn btn-default btn-sm';
                    btn.innerHTML = '<i class="icon-check"></i> Débloquée';
                    btn.disabled = true;
                    if (resultSpan) {
                        var okText = data.message || 'Commande débloquée.';
                        if (data.oasis_reference) {
                            okText += ' (Réf. ERP : ' + data.oasis_reference + ')';
                        }
                        resultSpan.style.display = 'inline';
                        resultSpan.style.color = '#5cb85c';
                        resultSpan.innerHTML = '<i class="icon-check"></i> ' + okText;
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="icon-unlock"></i> Débloquer la commande (ERP)';
                    if (resultSpan) {
                        resultSpan.style.display = 'inline';
                        resultSpan.style.color = '#d9534f';
                        resultSpan.innerHTML = '<i class="icon-warning"></i> ' + (data.message || 'Erreur.');
                    }
                }
            };
            xhr.send('id_order=' + encodeURIComponent(idOrder));
        });
    });
})();
</script>
