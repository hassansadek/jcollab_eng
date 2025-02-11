

<div class="hr"></div>

<div class="portlet light bordered">
	<div class="portlet-title">
		<div class="caption">
			Liste des pays
		</div>
		<div class="actions">
			<?php if ($globalPermission['Permission']['a']): ?>
			<a href="<?php echo $this->Html->url(['action' => 'edit']) ?>" class="edit btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter </a>
			<?php endif ?>
		</div>
	</div>
	<div class="portlet-body ">

	<table class="table table-striped table-bordered  table-hover" cellpadding="0" cellspacing="0">
	<thead>
	<tr>
		<th nowrap=""><?php //echo $this->Paginator->sort('libelle'); ?>Libellé</th>
		<th class="actions" nowrap="">
	</tr>
	</thead>
	<tbody>
	<?php foreach ($pays as $pay): ?>
	<tr>
		<td><?php echo h($pay['Pay']['libelle']); ?>&nbsp;</td>
		<td class="actions">
			<?php if ($globalPermission['Permission']['m1']): ?>
				<a href="<?php echo $this->Html->url(['action' => 'edit', $pay['Pay']['id']]) ?>" class="edit"><i class="fa fa-edit"></i></a>
			<?php endif ?>
			<?php if ($globalPermission['Permission']['s']): ?>
				<a href="<?php echo $this->Html->url(['action' => 'delete', $pay['Pay']['id']]) ?>" class="delete"><i class="fa fa-trash-o"></i></a>
			<?php endif ?>
		</td>
	</tr>
	<?php endforeach; ?>
	</tbody>
	</table>

	<div class="row">
		<div class="col-md-5 col-sm-12">
		<div class="dataTables_info" id="sample_1_info" role="status" aria-live="polite"><?php
		echo $this->Paginator->counter(array( 'format' => __('Page {:page} sur {:pages} |  {:current} résultats sur un total de {:count}.')
		)); ?></div>
		</div>
		<div class="col-md-7 col-sm-12">
			<div class="dataTables_paginate paging_bootstrap_full_number" id="sample_1_paginate">
				<ul class="pagination">
					 <?php 
					    echo $this->Paginator->prev( '<', array( 'class' => '', 'tag' => 'li' ), null, 
					    	array( 'class' => 'disabled', 'tag' => 'li','disabledTag' => 'a' ) );
					    echo $this->Paginator->numbers( array( 'tag' => 'li', 'separator' => '', 'currentClass' => 'active', 'currentTag' => 'a' ) );
					    echo $this->Paginator->next( '>', array( 'class' => '', 'tag' => 'li' ), null, 
					    	array( 'class' => 'disabled', 'tag' => 'li','disabledTag' => 'a' ) );
					?>
				</ul>
			</div>
		</div>
	</div>
		

	</div>
</div>

<?php $this->start('js') ?>
<script>
  $(function(){

	$('.delete').on('click',function(e) {
		e.preventDefault();
		var url = $(this).prop('href');
		bootbox.confirm("Etes-vous sûr de vouloir confirmer la suppression ?", function(result) {
			if( result ) {
				window.location.href = url;
			}
		});
	});

	$('.edit').on('click',function(e) {
		e.preventDefault();
		$.ajax({
			//type: 'POST',
			url: $(this).attr('href'),
			success: function(dt){
				$('#edit .modal-content').html(dt);
				$('#edit').modal('show');
			},
			error: function(dt){
				toastr.error("Il y a un probleme");
			},
			complete: function(){
				//Init();
			}
		});
	});

  });
</script>
<?php $this->end() ?>