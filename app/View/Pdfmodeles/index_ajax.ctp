<div class="table-scrollable">
	<table class="table table-striped table-bordered  table-hover" cellpadding="0" cellspacing="0">
		<thead>
			<tr>
				<th nowrap="">Image</th>
				<th nowrap="">Libellé</th>
				<th nowrap="">Date création</th>
				<th class="actions" nowrap="">
			</tr>
		</thead>
		<tbody>
			<?php foreach ($taches as $tache): ?>
				<tr>
					<td class="text-center">
						<?php if ( !empty( $tache['Pdfmodele']['image'] ) AND file_exists( WWW_ROOT."uploads/modeles/".$tache['Pdfmodele']['image'] ) ): ?>
							<a target="_blank" href="<?php echo $this->Html->url("../uploads/modeles/".$tache['Pdfmodele']['image']) ?>" class="imagelightbox">
								<img class="img-thumbnail" src="<?php echo $this->Html->url("../uploads/modeles/".$tache['Pdfmodele']['image']) ?>" style="width: 35px;height: 35px;" />
							</a>		
						<?php else: ?>
							<a target="_blank" href="<?php echo $this->Html->url("../img/no-image.png"); ?>" class="imagelightbox">
								<img class="img-thumbnail" src="<?php echo $this->Html->url("../img/no-image.png"); ?>" style="width: 35px;height: 35px;" />
							</a>
						<?php endif ?>
					</td>
					<td><?php echo h($tache['Pdfmodele']['libelle']); ?></td>
					<td><?php echo h($tache['Pdfmodele']['date_c']); ?></td>
					<td class="actions">
						<?php if ($globalPermission['Permission']['m1']): ?>
							<a href="<?php echo $this->Html->url(['action' => 'edit', $tache['Pdfmodele']['id']]) ?>" class="edit"><i class="fa fa-edit"></i></a>
						<?php endif ?>
						<?php if ($globalPermission['Permission']['s']): ?>
							<a href="<?php echo $this->Html->url(['action' => 'delete', $tache['Pdfmodele']['id']]) ?>" class="btnFlagDelete"><i class="fa fa-trash-o"></i></a>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
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
				    echo $this->Paginator->prev( '<', array( 'class' => 'page-link', 'tag' => 'li' ), null,  array( 'class' => 'page-link disabled', 'tag' => 'li','disabledTag' => 'a' ) );
				    echo $this->Paginator->numbers( array( 'class' => 'page-link', 'tag' => 'li', 'separator' => '', 'currentClass' => 'page-link active', 'currentTag' => 'a' ) );
				    echo $this->Paginator->next( '>', array( 'class' => 'page-link', 'tag' => 'li' ), null, array( 'class' => 'page-link disabled', 'tag' => 'li','disabledTag' => 'a' ) );
				?>
			</ul>
		</div>
	</div>
</div>