<?php $this->start('modal'); ?>
<div class="modal fade modal-blue" id="edit" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

    </div>
  </div>
</div>
<?php $this->end(); ?>
<div class="hr"></div>
<div class="portlet light bordered">
  
	<div class="portlet-title">
		<div class="caption">
			Liste des bons de retour
		</div>
		<div class="actions">
      <?php if ($globalPermission['Permission']['a']): ?>
        <a href="<?php echo $this->Html->url(['action' => 'editreception']); ?>" class="edit btn btn-primary btn-sm"><i class="fa fa-plus"></i> Retour bon de reception </a>
      <a href="<?php echo $this->Html->url(['action' => 'edit']); ?>" class="edit btn btn-primary btn-sm"><i class="fa fa-plus"></i> bon de retour direct</a>
      <?php endif; ?>
		</div>
	</div>
	<div class="portlet-body">
    <div class="row">
      <div class="col-md-12">
      <div class="formFilter">
        <?php $base_url = ['controller' => 'bonretourachats', 'action' => 'indexAjax']; ?>
        <?php echo $this->Form->create('Filter', ['url' => $base_url, 'class' => 'filter form-horizontal', 'autocomplete' => 'off']); ?>
        <div class="row">
        <div class="col-md-12 smallForm">
          <div class="form-group row">
            <div class="col-md-2">
              <?php echo $this->Form->input('Filter.Bonretourachat.reference', ['label' => false, 'placeholder' => 'Référence', 'class' => 'form-control']); ?>
            </div>
            <div class="col-md-2">
              <?php echo $this->Form->input('Filter.Bonretourachat.fournisseur_id', ['label' => false, 'empty' => '--Fournisseur', 'class' => 'select2 form-control']); ?>
            </div>
            <div class="col-md-3">
              <?php echo $this->Form->input('Filter.Bonretourachat.depot_id', ['label' => false, 'empty' => '--Dépot', 'class' => 'select2 form-control']); ?>
            </div>
            <div class="col-md-2">
              <?php echo $this->Form->input('Filter.Bonretourachat.etat', ['label' => false, 'empty' => '--Etat', 'class' => 'form-control', 'options' => $this->App->getEtatFiche()]); ?>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-md-3">
              <div class="d-flex align-items-end">
                <?php echo $this->Form->input('Filter.Bonretourachat.date1', ['label' => false, 'placeholder' => 'Date 1', 'class' => 'date-picker form-control', 'type' => 'text']); ?>
                <span class="input-group-addon">&nbsp;à&nbsp;</span>
                <?php echo $this->Form->input('Filter.Bonretourachat.date2', ['label' => false, 'placeholder' => 'Date 2', 'class' => 'date-picker form-control', 'type' => 'text']); ?>
              </div>
            </div>
            <div class="col-md-3">
              <?php echo $this->Form->submit('Rechercher', ['class' => 'btn btn-primary', 'div' => false]); ?>
              <?php echo $this->Form->reset('Reset', ['class' => 'btnResetFilter btn btn-default', 'div' => false]); ?>
            </div>
          </div>
        </div>
        </div>
        <?php echo $this->Form->end(); ?>
      </div>
      </div>
      <div class="col-md-12">
        <div id="indexAjax"></div>
      </div>
    </div>
  </div>
</div>

<?php $this->start('js'); ?>
<script>
  var Init = function(){
    $('.uniform').uniform();
    $('.select2').select2();
    $('.date-picker').flatpickr({
      altFormat: "DD-MM-YYYY",
      dateFormat: "d-m-Y",
      allowInput: true,
      locale: "fr",
    });
  }
</script>
<?php echo $this->element('main-script', ['form' => 'BonretourachatEditForm']); ?>); ?>
<?php $this->end(); ?>