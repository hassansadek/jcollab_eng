<?php $this->start('modal') ?>
<div class="modal fade modal-blue" id="edit" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
    </div>
  </div>
</div>
<?php $this->end() ?>
<div class="hr"></div>

<?php if ( isset( $this->data['Bonretour']['id'] ) AND !empty( $this->data['Bonretour']['id'] ) ): ?>

<?php if ( !empty( $this->data['Bonretour']['etat'] ) ): ?>
  <div class="row">
    <div class="col-lg-12">
      <div class="alert alert-default text-right" style="color:white;background-color: <?php echo $this->App->getEtatFicheColor( $this->data['Bonretour']['etat'] ) ?>;padding: 10px;border-color: <?php echo $this->App->getEtatFicheColor( $this->data['Bonretour']['etat'] ) ?>">
        <strong>Statut bon de retour &ensp; : &ensp;</strong>  <?php echo $this->App->getEtatFiche( $this->data['Bonretour']['etat'] ) ?>
      </div>
    </div>
  </div>
<?php endif ?>

<div class="row" style="margin-bottom: 10px;text-align: right;">
  <div class="col-lg-12">
    <a href="<?php echo $this->Html->url(['action' => 'index']) ?>" class="btn btn-primary btn-sm" ><i class="fa fa-reply"></i> Vers la liste </a>
    
    <a href="<?php echo $this->Html->url(['action'=>'mail',$this->data['Bonretour']['id']]) ?>" class="edit btn btn-warning btn-sm"><i class="fa fa-envelope-o"></i> Envoi par mail </a>
    <?php if ( isset( $this->data['Bonretour']['id'] ) AND $globalPermission['Permission']['i'] ): ?>
      <?php if ( $MOBILE ): ?>
        <a href="<?php echo $this->Html->url(['action'=>'generatepdf',$this->data['Bonretour']['id']]) ?>" class="PrintThisPage btn btn-info btn-sm"><i class="fa fa-file-pdf-o"></i> Imprimer</a>
      <?php else: ?>
        <a target="_blank" href="<?php echo $this->Html->url(['action'=>'ticket',$this->data['Bonretour']['id']]) ?>" class="btn btn-info btn-sm"><i class="fa fa-file-pdf-o"></i> Imprimer Ticket</a>
        <a target="_blank" href="<?php echo $this->Html->url(['action'=>'pdf',$this->data['Bonretour']['id']]) ?>" class="btn btn-info btn-sm"><i class="fa fa-file-pdf-o"></i> Imprimer A4</a>
      <?php endif ?>
    <?php endif ?>
    
    <?php if ( isset( $this->data['Bonretour']['id'] ) AND $this->data['Bonretour']['etat'] == -1 ): ?>
      <a href="<?php echo $this->Html->url(['action'=>'changestate',$this->data['Bonretour']['id'],1 ]) ?>" class="changestate btn btn-warning btn-sm"><i class="fa fa-check-square-o"></i> Terminer la saisie</a>
    <?php endif ?>

    <?php if ( isset( $this->data['Bonretour']['id'] ) AND $this->data['Bonretour']['etat'] == 1 ): ?>
      <a href="<?php echo $this->Html->url(['action'=>'changestate',$this->data['Bonretour']['id'],2 ]) ?>" class="changestate btn btn-success btn-sm"><i class="fa fa-refresh"></i> Valider </a>
      <a href="<?php echo $this->Html->url(['action'=>'changestate',$this->data['Bonretour']['id'],-1 ]) ?>" class="changestate btn btn-warning btn-sm"><i class="fa fa-edit"></i> Modifier </a>
    <?php endif ?>

    <?php if ( isset( $this->data['Bonretour']['id'] ) AND $this->data['Bonretour']['etat'] == 2 ): ?>
      <?php if ( empty( $this->data['Bonretour']['bonavoir_id'] ) ): ?>
        <a href="<?php echo $this->Html->url(['action'=>'avoir',$this->data['Bonretour']['id'] ]) ?>" class="changestate btn btn-success btn-sm"><i class="fa fa-file"></i> Générer un avoir</a>
      <?php else: ?>
        <a href="<?php echo $this->Html->url(['controller'=>'bonavoirs','action'=>'view',$this->data['Bonretour']['bonavoir_id'] ]) ?>" class="btn btn-danger btn-sm"><i class="fa fa-file"></i> Voir l'avoir</a>
      <?php endif ?>
    <?php endif ?>

  </div>
</div>

<div class="portlet light bordered">
  <div class="portlet-title">
    <div class="caption">
      Information bon de retour
    </div>
    <div class="actions">

    </div>
  </div>
  <div class="portlet-body">
    <div class="row">
      <div class="col-md-12">
        <div class="table-scrollable">
          <table class="table table-bordered tableHeadInformation">
            <tbody>
              <tr>
                <td class="tableHead" nowrap="">Référence</td>
                <td nowrap="">
                  <?php echo $this->data['Bonretour']['reference'] ?>
                </td>
                <td class="tableHead" nowrap="">Date</td>
                <td nowrap=""> 
                  <?php echo $this->data['Bonretour']['date'] ?>
                </td>
              </tr>

              <tr>
                <td class="tableHead" nowrap="">Client</td>
                <td nowrap="">
                  <?php echo $this->data['Client']['designation'] ?>
                </td>
                <td class="tableHead" nowrap="">Depot</td>
                <td nowrap="">
                  <?php echo $this->data['Depot']['libelle'] ?>
                </td>
                
              </tr>
              
              <tr>
              <td class="tableHead" nowrap="">Montant TVA</td>
                <td nowrap="" class="text-right total_number"> 
                  <?php echo number_format($this->data['Bonretour']['montant_tva'], 2, ',', ' '); ?>
                </td>
                <td class="tableHead" nowrap="">Total à payer HT</td>
                <td nowrap="" class="text-right total_number"> 
                  <?php echo number_format($this->data['Bonretour']['total_a_payer_ht'], 2, ',', ' '); ?>
                </td>
                
              </tr>
              <tr>
              <td class="tableHead" nowrap="">Total à payer TTC</td>
                <td nowrap="" class="text-right total_number"> 
                  <?php echo number_format($this->data['Bonretour']['total_a_payer_ttc'], 2, ',', ' '); ?>
                </td>
                <td class="tableHead" nowrap=""></td>
                <td nowrap="" class="text-right total_number"> 
                </td>
                
              </tr>
 
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif ?>

<div class="portlet light bordered">
  <div class="portlet-title">
    <div class="caption">
      Liste des produits
    </div>
    <div class="actions">
      <?php if ( $globalPermission['Permission']['a'] AND isset( $this->data['Bonretour']['id'] ) AND $this->data['Bonretour']['etat'] == -1 ): ?>
        <a href="<?php echo $this->Html->url(['action' => 'editdetail',0,$this->data['Bonretour']['id']]) ?>" class="edit btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter produit </a>
      <?php endif ?>
    </div>
  </div>
  <div class="portlet-body">
    <div class="row">
      <div class="col-md-12">
        <div class="table-scrollable">
          <table class="table table-striped table-bordered  table-hover" cellpadding="0" cellspacing="0">
            <thead>
              <tr>
                <th nowrap="">Désignation</th>
                <th nowrap="">Quantité</th>
                <th nowrap="">Prix TTC</th>
                <th nowrap="">Total TTC</th>
                <?php if ( $this->data['Bonretour']['etat'] == -1 ): ?>
                <th class="actions">Actions</th>
                <?php endif ?>
              </tr>
            </thead>
            <tbody>
              <?php $total = 0;$total_ttc = 0;$total_qte = 0;$total_paquet = 0;$total_piece = 0;$total_prix = 0; ?>
              <?php foreach ($details as $tache): ?>

                <?php $total_qte = $total_qte + $tache['Bonretourdetail']['qte']; ?>
                <?php $total_paquet = $total_paquet + $tache['Bonretourdetail']['paquet']; ?>
                <?php $total = $total + $tache['Bonretourdetail']['ttc']; ?>
                <?php $total_ttc = $total_ttc + $tache['Bonretourdetail']['ttc']; ?>
                <?php $total_piece = $total_piece + $tache['Bonretourdetail']['total_unitaire']; ?>
                <?php $total_prix = $total_prix + $tache['Bonretourdetail']['prix_vente']; ?>
                
                <tr >
                  <td nowrap=""><?php echo h($tache['Produit']['libelle']); ?></td>
                  <td nowrap="" class="text-right"><?php echo h($tache['Bonretourdetail']['qte']); ?></td>
                  <td nowrap="" class="text-right"><?php echo number_format($tache['Bonretourdetail']['prix_vente'], 2, ',', ' '); ?></td>
                  <td nowrap="" class="text-right"><?php echo number_format($tache['Bonretourdetail']['ttc'], 2, ',', ' '); ?></td>
                  <?php if ( $this->data['Bonretour']['etat'] == -1 ): ?>
                  <td nowrap="" class="actions">
                      <?php if ( $globalPermission['Permission']['m1'] ): ?>
                        <a href="<?php echo $this->Html->url(['action' => 'editdetail', $tache['Bonretourdetail']['id'], $tache['Bonretourdetail']['bonretour_id']]) ?>" class="edit"><i class="fa fa-edit"></i></a>
                      <?php endif ?>
                      <?php if ( $globalPermission['Permission']['s'] ): ?>
                        <a href="<?php echo $this->Html->url(['action' => 'deletedetail', $tache['Bonretourdetail']['id'], $tache['Bonretourdetail']['bonretour_id']]) ?>" class="btnFlagDelete"><i class="fa fa-trash-o"></i></a>
                      <?php endif ?>
                  </td>
                  <?php endif ?>
                </tr>
              <?php endforeach; ?>
              <tr class="total">
                <td nowrap=""></td>
                <td nowrap="" class="text-right"><strong><?php //echo $total_qte ?></strong></td>
                <td nowrap="" class="text-right"><strong><?php //echo number_format($total_prix, 2, ',', ' ') ?></strong></td>
                <td nowrap="" class="text-right"><strong><?php echo number_format($total, 2, ',', ' ') ?></strong></td>
                <?php if ( $this->data['Bonretour']['etat'] == -1 ): ?>
                <td nowrap="" class="actions"></td>
                <?php endif ?>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->start('js') ?>
<script>
$(function(){
  var dataFilter = null;
  var dataPage = 1;

  $('.PrintThisPage').on('click',function(e){
    e.preventDefault();
    var url = $(this).attr('href');
    toastr.error("Pour imprimer ce document veuillez se connectez via un ordinateur ! ");
    /*$.ajax({
      url: url,
      dataType:'JSON',
      success : function(dt){
        var lien = dt.url;
        window.open('https://docs.google.com/viewer?url='+lien+'&embedded=true', '_blank', 'location=yes'); 
      }
    });*/
  });

  var loadIndexAjax = function(url){
    $.ajax({
      url: url,
      success : function(dt){
        $('#PieceJointes').html(dt);
      }
    });
  }
  
  var Init = function(){
    $('.select2').select2();
    $('.date-picker').flatpickr({
      altFormat: "DD-MM-YYYY",
      dateFormat: "d-m-Y",
      allowInput: true,
      locale: "fr",
    });

    /* var depot_id = 1;
    var detail_id = $('#DetailID').val();
    if ( typeof detail_id != 'undefined' && detail_id != '' ) {
      var categorieproduit_id = $('#CategorieproduitID').val();
      if( typeof categorieproduit_id != 'undefined' && categorieproduit_id != '' ) getProduitByDepot(depot_id,categorieproduit_id);
      var produit_id = $("#ArticleID").val();
      if( typeof produit_id != 'undefined' && produit_id != '' ) getProduit(produit_id,depot_id);
    } */
    
  }

  Init();

  function getProduitByDepot(depot_id,categorieproduit_id) {
    $.ajax({
      dataType: "json",
      url: "<?php echo $this->Html->url(['action' => 'getProduitByDepot',$this->data['Bonretour']['id'] ]) ?>/"+depot_id+"/"+categorieproduit_id,
      success: function(dt){
        var value = $('#ArticleID').val();
        $('#ArticleID').empty();
        $('#ArticleID').append($('<option>').text('-- Votre choix').attr('value', ''));
        $.each(dt, function(i, obj){
          $('#ArticleID').append($('<option>').text(obj).attr('value', i));
        });
        $('#ArticleID').val( value ).trigger('change');
      },
      error: function(dt){
        toastr.error("Il y a un problème");
      }
    }); 
  }

  $('#edit').on('change','#CategorieproduitID',function(e){
    var categorieproduit_id = $('#CategorieproduitID').val();
    var depot_id = 1;
    getProduitByDepot(depot_id,categorieproduit_id);
  });

  $('#edit').on('input','#QteChange',function(e){
    calculeTotal();
  });

  $('#edit').on('input','#PaquetChange',function(e){
    calculeTotal();
  });

  $('#edit').on('input','#PrixVente',function(e){
    calculeTotal();
  });

  $('#edit').on('input','#Reduction',function(e){
    var total_before = $('#TotalBefore').val();
    var reste_a_payer = $('#RestePayer').data('reste');
    var reduction = $(this).val();
    var total_after = total_before-reduction;
    if ( total_after <= 0 ) total_after = 0;
    $('#TotalAfter').val(total_after);
    $('#RestePayer').val(total_after);
  });

  function calculeTotal() {
    var tva = $('#TVA').val();;
    var quantite = $('#QteChange').val();
    var paquet = $('#PaquetChange').val();
    var prix_vente = $('#PrixVente').val();

    if( tva == '' ) tva = 0;
    if( paquet == '' ) paquet = 1;
    if( quantite == '' ) quantite = 0; 
    if( prix_vente == '' ) prix_vente = 0; 

    var total_quantite = quantite*paquet;
    $('#TotalUnitaire').val(total_quantite);

    var total = total_quantite * prix_vente;
    $('#Total').val( total.toFixed(2) );

    var totaltva =  total * ( 1 + tva/100 );
    $('#TotalTVA').val( totaltva.toFixed(2) );

  }

  $('#edit').on('change','#ArticleID',function(e){
    var depot_id = 1;
    var produit_id = $(this).val();
    if ( produit_id == '' ) {
      $('#Total').val(0);
      $('#TotalTVA').val(0);
      $('#PrixVente').val(0);
    }
    getProduit(produit_id,depot_id);
  });

  function getProduit(produit_id,depot_id) {
      $.ajax({
        dataType:'JSON',
        url: "<?php echo $this->Html->url(['action' => 'getProduit']) ?>/"+produit_id+'/'+depot_id,
        success: function(dt){
          if (typeof(dt.Produit) != 'undefined'){
            var prix_vente = parseFloat(dt.Produit.prix_vente);
            var tva = parseFloat(dt.Produit.tva);
            $('#Total').val(0);
            $('#TotalTVA').val(0);
            if ( $('#PrixVente').val() == '' ) $('#PrixVente').val(prix_vente);
            $('#TVA').val(tva);
            calculeTotal();
          }
        },
        error: function(dt){
          $('#Total').val('');
          $('#PrixVente').val('');
          toastr.error("Il y a un probléme");
        },
        complete: function(){

        }
      });
  }

  $('#edit').on('submit','form',function(e){
    $('.saveBtn').attr('disabled',true);
    $('#Loading').slideDown();
  });

  $('#indexAjax').on('click','.edit',function(e){
    e.preventDefault();
    $.ajax({
      url: $(this).attr('href'),
      success: function(dt){
        $('#edit .modal-content').html(dt);
        $('#edit').modal('show');
      },
      error: function(dt){
        toastr.error("Il y a un problème");
      },
      complete: function(){
        Init();
      }
    });
  });
  
  $('.edit').on('click',function(e){
    e.preventDefault();
    $.ajax({
      url: $(this).attr('href'),
      success: function(dt){
        $('#edit .modal-content').html(dt);
        $('#edit').modal('show');
      },
      error: function(dt){
        toastr.error("Il y a un problème");
      },
      complete: function(){
        Init();
      }
    });
  });

  $('.changestate').on('click',function(e){
    e.preventDefault();
    var url = $(this).prop('href');
    bootbox.confirm("Etes-vous sûr de vouloir confirmer cette action ?", function(result) {
      if( result ){
        window.location = url;
      }
    });
  });

  $('.btnFlagDelete').on('click',function(e){
    e.preventDefault();
    var url = $(this).prop('href');
    bootbox.confirm("Etes-vous sûr de vouloir confirmer la suppression ?", function(result) {
      if( result ){
        window.location = url;
      }
    });
  });
  $('#edit').on('keyup','#code_barre',function(e){
    var code_barre = $('#code_barre').val();
    if (e.keyCode === 13) {
      var code_barre = $('#code_barre').val();
  		scaning(code_barre);
    }
    
  });
  $('#edit').on('submit','#ScanForm',function(e){
    
    //e.preventDefault();
    alert("ok");
        var code_barre = $('#code_barre').val();
        scaning(code_barre);
      });
  
    function scaning(code_barre) {
      if ( code_barre == '' || code_barre == '#' ) { toastr.error("Aucun code barre saisie !"); return; }
  
      
  
        $.ajax({
          url: "<?php echo $this->Html->url(['action' => 'scan']) ?>/"+code_barre,
          success: function(result){
            if ( result.error == true ) toastr.error(result.message);
            else{
              /* var quantite_sortie = result.data.quantite_sortie;
              var produit_id = result.data.produit_id;
              var stock = result.data.stock;
  
              var full_path = url+'/'+produit_id+'/'+stock+'/'+quantite_sortie;
             */	var quantite_sortie = result.data.quantite_sortie;
              var prix = result.data.prix;
            var produit_id = result.data.produit_id;
    
            $("#QteChange").val(quantite_sortie);
          $("#PrixVente").val(prix);
          $("#Total").val(prix*quantite_sortie);
          
        
          $("#ArticleID").val(produit_id).trigger('change');
          
  
            } 
          },
          error: function(dt){
            toastr.error("Il y a un problème");
          },
          complete: function(dt){
        $('#code_barre').val('');
          }
        });
    }
});
</script>
<?php $this->end() ?>