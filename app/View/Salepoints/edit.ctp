<div class="modal-header">
	
	<h4 class="modal-title">
		<?php if ( isset($this->data['Salepoint']['id']) ): ?>
			Modifier un bon de livraison
		<?php else: ?>
			Nouveau bon de livraison
		<?php endif ?>
	</h4>
</div>
<div class="modal-body ">
<?php echo $this->Form->create('Salepoint',['id' => 'SalepointEditForm','class' => 'form-horizontal','autocomplete'=>'off']); ?>
	<div class="row">
		<?php echo $this->Form->input('id'); ?>
		<div class="col-md-12">
			<div class="form-group row">
				<label class="control-label col-md-2">Date</label>
				<div class="col-md-3">
					<?php echo $this->Form->input('date',['class' => 'date-picker form-control','label'=>false,'required' => true,'type'=>'text','default'=>date('d-m-Y')]); ?>
				</div>
				<label class="control-label col-md-1">Vendeur</label>
				<div class="col-md-4">
					<?php if ( in_array($role_id, $admins) ): ?>
						<?php if ( isset($this->data['Salepoint']['id']) ): ?>
							<?php echo $this->Form->input('user_id',['class'=>'form-control','label'=>false,'disabled'=>true,'empty'=>'--Vendeur']); ?>
						<?php else: ?>
							<?php echo $this->Form->input('user_id',['class'=>'select2 form-control','label'=>false,'required'=>true,'empty'=>'--Vendeur','default'=>$user_id]); ?>
						<?php endif ?>
					<?php else: ?>
						<?php echo $this->Form->input('user_id',['class'=>'form-control','label'=>false,'disabled'=>true,'value'=>$user_id]); ?>
						<?php echo $this->Form->hidden('user_id',['value'=>$user_id]); ?>
					<?php endif ?>
				</div>
			</div>
			<div class="form-group row">
				<label class="control-label col-md-2">Client</label>
				<div class="col-md-8">
					<?php if ( isset($this->data['Salepoint']['id']) ): ?>
						<?php echo $this->Form->input('client_id',['class'=>'form-control','label'=>false,'disabled'=>true,'empty'=>'--Client']); ?>
					<?php else: ?>
						<?php echo $this->Form->input('client_id',['class'=>'select2 form-control','label'=>false,'required'=>true,'empty'=>'--Client','id'=>'ClientId']); ?>
					<?php endif ?>
				</div>
			</div>
			<div class="form-group row">
				<label class="control-label col-md-2">Remise (%)</label>
				<div class="col-md-3">
					<?php echo $this->Form->input('remise',['class' => 'form-control','label'=>false,'required'=>true,'id'=>'Remise','default'=>0,'min'=>0,'max'=>100]); ?>
				</div>
				<label class="control-label col-md-2"></label>
				<div class="col-md-3">
					<?php if ( isset($this->data['Salepoint']['id']) ): ?>
						<?php echo $this->Form->input('active_remise',['class' => 'uniform form-control','label'=>"Activer la remise",'required' => false,'type'=>'checkbox']); ?>
					<?php else: ?>
						<?php echo $this->Form->input('active_remise',['class' => 'uniform form-control','label'=>"Activer la remise",'required' => false,'type'=>'checkbox','checked'=>true]); ?>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
<?php echo $this->Form->end(); ?>
</div>
<div class="modal-footer">
	<?php echo $this->Form->submit('Enregistrer',array('div' => false,'form' => 'SalepointEditForm','class' => 'saveBtn btn btn-success')) ?>
	<button type="button" class="btn default" data-dismiss="modal">Fermer</button>
</div>