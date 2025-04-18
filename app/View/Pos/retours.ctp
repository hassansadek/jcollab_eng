<div class="modal-header">
	<h4 class="modal-title">
		Retour produits
	</h4>
</div>
<div class="modal-body ">
	<div class="row">
		<?php if ( empty( $retours ) ): ?>
			<div class="col-md-12">
				<div class="alert alert-danger text-center p-2" style="font-weight: bold;font-size: 20px;">Liste des articles est vide !</div>
			</div>
		<?php else: ?>
			<div class="col-md-6" style="border:1px solid #e5e5e5;">
				<div class="table-responsive" style="min-height: auto;max-height: 450px;overflow-y: scroll;">
					<table class="table table-striped table-bordered table-hover" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th class="actions"></th>
								<th nowrap="">Référence</th>
								<th nowrap="">Date</th>
								<th nowrap="">Remise(%)</th>
								<th nowrap="">Net à payer</th>
								<th class="actions"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($retours as $dossier): ?>
								<tr>
									<td nowrap=""><a class="modifierticket btn btn-danger btn-sm btn-block" href="<?php echo $this->Html->url(['action'=>'modifierticket',$dossier['Salepoint']['id']]) ?>"><i class="fa fa-reply"></i> Modifier</a></td>
									<td nowrap=""><a class="getdetailticket" href="<?php echo $this->Html->url(['action'=>'ticketdetails',$dossier['Salepoint']['id']]) ?>"><?php echo h($dossier['Salepoint']['reference']); ?></a></td>
									<td nowrap=""><?php echo h($dossier['Salepoint']['date']); ?></td>
									<td nowrap="" class="text-right"><?php echo number_format($dossier['Salepoint']['remise'], 2, ',', ' '); ?>%</td>
									<td nowrap="" class="text-right"><?php echo number_format($dossier['Salepoint']['total_apres_reduction'], 2, ',', ' '); ?></td>
									<td nowrap="" class="actions">
										<a class="getdetailticket btn btn-primary btn-sm btn-block" href="<?php echo $this->Html->url(['action'=>'ticketdetails',$dossier['Salepoint']['id']]) ?>"><i class="fa fa-eye"></i> Détails</a>
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