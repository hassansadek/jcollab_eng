<div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
    <h4 class="modal-title" style="margin: 0;">
        Liste des commandes e-commerce (<?php echo count($ecommerces); ?>)
    </h4>

    <div style="display: flex; align-items: center; gap: 15px;">
        <div style="font-size: 13px; color: #666;">
            Dernière Synchronisation : <?php echo $created_at ?>
        </div>

        <div class="input-group-append">
            <!-- 
            <button class="btn btn-default btn-reset-ecommerce" type="button">
                <i class="fa fa-refresh" style="color: #007bff; font-size: 14px;"></i> Actualiser
            </button>
            -->

<!-- Synchronisation des commandes -->
<button id="sync-orders-btn" class="btn btn-primary">
    <i class="fa fa-refresh" style="color:rgb(242, 243, 236); font-size: 14px;"></i> Synchroniser les commandes
</button>

<script>
$(document).ready(function() {
    // const baseUrl = window.location.origin + window.location.pathname.split('/Pos')[0];
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/pos\/.*/i, '');


    $('#sync-orders-btn').on('click', function() {
        $.ajax({
            url: baseUrl + '/Pos/saveOrdersFromApi',
            method: 'POST',
            beforeSend: function() {
                $('#sync-orders-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Synchronisation...');
            },
            success: function() {
                alert("Commandes synchronisées avec succès.");
            },
            error: function(xhr) {
                alert("Erreur : " + xhr.responseText);
            },
            complete: function() {
                $('#sync-orders-btn').prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Synchroniser les commandes');
            }
        });
    });
});
</script>



         


        </div>
    </div>
</div>

<div class="modal-body">
    <div class="row">
        <?php if (empty($ecommerces)): ?>
            <div class="col-md-12">
                <div class="alert alert-danger text-center p-2" style="font-weight: bold;font-size: 20px;">
                    Liste des articles est vide !
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-6" style="border:1px solid #e5e5e5;">
                <div class="table-responsive" style="min-height: auto; max-height: 450px; overflow-y: scroll;">
                    <table class="table table-striped table-bordered table-hover" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Traitement</th>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Statut</th>
                                <th class="actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="EcommerceList">
                            <?php
							 foreach ($ecommerces as $dossier): ?>
                                <tr>
                                    <td nowrap="">
                                        <a class="traitercommande btn btn-warning btn-sm btn-block text-white"
                                           style="font-weight: bold;"
                                           attrUrl="<?php echo $this->Html->url(['action' => 'checkecommerce', $dossier['Ecommerce']['id']]); ?>"
                                           href="<?php echo $this->Html->url(['action' => 'traiterecommerce', $dossier['Ecommerce']['id']]); ?>">
                                            <i class="fa fa-cogs"></i> Traiter
                                        </a>
                                    </td>
                                    <td nowrap="">
                                        <a class="getdetail" href="<?php echo $this->Html->url(['action' => 'ecommercedetails', $dossier['Ecommerce']['id']]); ?>">
                                            <?php echo h($dossier['Ecommerce']['barcode']); ?>
                                        </a>
                                    </td>
                                    <td nowrap=""><?php echo h($dossier['Ecommerce']['date']); ?></td>
                                    <td nowrap=""><?php echo h($dossier['Client']['designation']); ?></td>
                                    <td nowrap=""><?php echo h($dossier['Ecommerce']['statut']); ?></td>
                                    <td class="actions">
                                        <a class="getdetail btn btn-primary btn-sm btn-block"
                                           href="<?php echo $this->Html->url(['action' => 'ecommercedetails', $dossier['Ecommerce']['id']]); ?>">
                                            <i class="fa fa-eye"></i> Détails
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6" style="border:1px solid #e5e5e5;">
                <div id="showdetail"></div>
            </div>
        <?php endif ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn default" data-dismiss="modal">Fermer</button>
</div>
