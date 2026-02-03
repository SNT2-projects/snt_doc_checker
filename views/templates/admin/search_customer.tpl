<div class="panel">
    <div class="panel-heading">
        <i class="icon-search"></i>
        Rechercher un client
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label class="control-label">Rechercher un client :</label>
            <div class="input-group">
                <input type="text" id="customer_search" class="form-control" placeholder="Tapez le nom, prénom ou email du client...">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" id="search_btn">
                        <i class="icon-search"></i>
                    </button>
                </span>
            </div>
        </div>
        
        <div id="customer_results" style="display: none;">
            <h4>Résultats de la recherche :</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="customer_list">
                        <!-- Les résultats seront chargés ici -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="selected_customer" style="display: none;">
            <h4>Client sélectionné :</h4>
            <div class="alert alert-success">
                <strong>Client choisi :</strong> <span id="selected_customer_name"></span> (<span id="selected_customer_email"></span>)
                <button type="button" class="btn btn-xs btn-warning pull-right" id="change_customer">
                    <i class="icon-edit"></i> Changer
                </button>
            </div>
            <input type="hidden" name="id_customer" id="selected_customer_id" value="">
        </div>
        
        <div id="customer_required_error" class="alert alert-danger" style="display: none;">
            <strong>Attention :</strong> Vous devez sélectionner un client avant de pouvoir enregistrer le document.
        </div>
    </div>
</div>

<script type="text/javascript">
// Fonction globale pour trouver le bon champ id_customer dans le formulaire
function getCustomerFormField() {
    var field = $('input[name="id_customer"]');
    if (field.length === 0) {
        // Essayer d'autres sélecteurs possibles
        field = $('#id_customer');
        if (field.length === 0) {
            field = $('input[id*="id_customer"]');
        }
    }
    return field;
}

$(document).ready(function() {
    var searchTimeout;
    

    
    // Recherche en temps réel
    $('#customer_search').on('keyup', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(function() {
                searchCustomers(query);
            }, 300);
        } else {
            $('#customer_results').hide();
        }
    });
    
    // Bouton de recherche
    $('#search_btn').on('click', function() {
        var query = $('#customer_search').val();
        if (query.length >= 2) {
            searchCustomers(query);
        }
    });
    
    // Fonction de recherche AJAX
    function searchCustomers(query) {
        $.ajax({
            url: '{$ajax_url|escape:'html':'UTF-8'}',
            method: 'POST',
            data: {
                q: query,
                ajax: 1,
                action: 'searchCustomer'
            },
            dataType: 'json',
            beforeSend: function() {
                $('#customer_list').html('<tr><td colspan="5"><i class="icon-spinner icon-spin"></i> Recherche en cours...</td></tr>');
                $('#customer_results').show();
            },
            success: function(data) {
                var html = '';
                if (data && data.length > 0) {
                    $.each(data, function(index, customer) {
                        html += '<tr>';
                        html += '<td>' + customer.id_customer + '</td>';
                        html += '<td>' + (customer.firstname || '') + '</td>';
                        html += '<td>' + (customer.lastname || '') + '</td>';
                        html += '<td>' + (customer.email || '') + '</td>';
                        html += '<td><button type="button" class="btn btn-sm btn-primary select-customer" data-id="' + customer.id_customer + '" data-name="' + (customer.firstname || '') + ' ' + (customer.lastname || '') + '" data-email="' + (customer.email || '') + '">Sélectionner</button></td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="5">Aucun client trouvé</td></tr>';
                }
                $('#customer_list').html(html);
            },
            error: function(xhr, status, error) {
                $('#customer_list').html('<tr><td colspan="5" class="text-danger">Erreur lors de la recherche</td></tr>');
            }
        });
    }
    
    // Sélection d'un client
    $(document).on('click', '.select-customer', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).data('name');
        var customerEmail = $(this).data('email');
        
        // Mettre à jour notre champ de l'interface
        $('#selected_customer_id').val(customerId);
        $('#selected_customer_name').text(customerName);
        $('#selected_customer_email').text(customerEmail);
        
        // Mettre à jour aussi le champ du formulaire PrestaShop
        getCustomerFormField().val(customerId);
        
        $('#customer_results').hide();
        $('#selected_customer').show();
        $('#customer_search').val('');
    });
    
    // Changer de client
    $('#change_customer').on('click', function() {
        $('#selected_customer').hide();
        $('#selected_customer_id').val('');
        
        // Vider aussi le champ du formulaire PrestaShop
        getCustomerFormField().val('');
        
        $('#customer_search').focus();
    });
    
    // Validation du formulaire
    $('form').on('submit', function(e) {
        var customerId = $('#selected_customer_id').val();
        var formCustomerId = getCustomerFormField().val();
        
        // Synchroniser les deux champs si nécessaire
        if (customerId && !formCustomerId) {
            getCustomerFormField().val(customerId);
        } else if (!customerId && formCustomerId) {
            $('#selected_customer_id').val(formCustomerId);
        }
        
        // Vérifier qu'un client est sélectionné
        var finalCustomerId = getCustomerFormField().val();
        if (!finalCustomerId || finalCustomerId === '') {
            e.preventDefault();
            $('#customer_required_error').show();
            $('html, body').animate({
                scrollTop: $('#customer_required_error').offset().top - 100
            }, 500);
            return false;
        }
        $('#customer_required_error').hide();
    });
    
    // Cacher l'erreur quand un client est sélectionné
    $('#selected_customer_id, input[name="id_customer"], #id_customer').on('change', function() {
        if ($(this).val()) {
            $('#customer_required_error').hide();
        }
    });
});
</script>