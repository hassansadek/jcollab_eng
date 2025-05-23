
<div class="hr"></div>

<div class="portlet light bordered">
	<div class="portlet-title">
		<div class="caption">
			Table de référence
		</div>
		<div class="actions">

		</div>
	</div>
	<div class="portlet-body">

	  <div class="row">
	  <div class="col-md-12">
	  <div class="formFilter">
		<?php $base_url = array('action' => 'indexAjax'); ?>
		<?php echo $this->Form->create("Filter",array('url' => $base_url, 'class' => 'filter form-horizontal','autocomplete'=>'off')) ?>
		<div class="row">
		<div class="col-md-12 smallForm">
			<div class="form-group row">
				<div class="col-md-4">
					<?php echo $this->Form->input('Filter.Diver.libelle',array('label' => false,'placeholder' => 'Recherche','class' => 'form-control')) ?>
				</div>
				<div class="col-md-3">
					<?php echo $this->Form->submit('Rechercher',array('class' => 'btn btn-primary','div' => false)) ?>
					<?php echo $this->Form->reset('Reset',array('class' => 'btnResetFilter btn btn-default','div' => false)) ?>
				</div>
			</div>
		</div>
		</div>
		<?php echo $this->Form->end() ?>
	  </div>
	  </div>
	<div class="col-md-12">
	  <div id="indexAjax">&nbsp;</div>
	</div>
	</div>
	  
	</div>
</div>

<?php $this->start('js') ?>
<script>
  var Init = function(){
    $('.date-picker').flatpickr({
      altFormat: "DD-MM-YYYY",
      dateFormat: "d-m-Y",
      allowInput: true,
      locale: "fr",
    });
  }
</script>
<?php echo $this->element('main-script'); ?>
<?php $this->end() ?>