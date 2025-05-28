<?php

class PosController extends AppController
{
    //public $idModule = 120;
    public $idModule = 130;
    public $uses = ['Produit', 'Role', 'Commandeglovo', 'Remiseclient', 'User', 'Salepoint', 'Sessionuser', 'Caisse', 'Attente', 'Commande', 'Parametreste', 'Ecommerce', 'Optionproduit', 'Typeconditionnementtproduit', 'Store', 'Salepointdetail','Ecommercedetail' ,'Client','Avance','Entree',];
    public $notice = 0;
    public $created_at;

    public function beforeFilter()
    {
        $this->Auth->allow('syncmanuel','syncmanuelecom');
      //  parent::beforeFilter();
    }

    public function index($salepoint_id = null, $caisse_id = null)
    {
        // Récupérer les informations de l'application 
        $result = $this->requestAction(array('controller' => 'Configs', 'action' => 'Getinfo'));
        $server_link    = $result['server_link'];
        $type_app       = $result['type_app'];

        $store_id_db = $result['store_id'];

        if ($type_app == 2) {
            $caisse_id      = $result['caisse_id'];
        }

        if ($caisse_id != null) {
            $this->Session->write('caisse_id', $caisse_id);
        }
 
        // Ajouter un loading de la page avant d'afficher le contenu de POS

        // 1- Sync des produits
       // $get_produits = $this->requestAction(array('controller' => 'Ingredients', 'action' => 'insertProduitsApi'));
        // 2 - Sync des clients
       // $get_clients = $this->requestAction(array('controller' => 'Clients', 'action' => 'insertClientsApi'));
        // 3 - Sync users
       // $get_users = $this->requestAction(array('controller' => 'Users', 'action' => 'insertUsersApi'));


        $depots = $this->Session->read('depots');
        $user_id = $this->Session->read('Auth.User.id');

        if (empty($salepoint_id)) {
            //add
            // $caisse_id = $this->Session->read('caisse_id');
            $options = ['conditions' => ['id' => $caisse_id]];
            $caisse = $this->Caisse->find('first', $options);
            $prefix = trim($caisse['Caisse']['prefix']);
            $prefix = $prefix.'-Prov';

            //$this->Salepoint->delete($salepoint["Salepoint"]["id"]);
            /* $salepoint = $this->Salepoint->find('first',[
                'conditions' => [
                    'Salepoint.etat' => -1,
                    'Salepoint.onhold' => -1,
                    'Salepoint.user_id' => $user_id
                ],
                'order' => ['Salepoint.id' => 'DESC']
            ]); */

            /* if ( isset( $salepoint['Salepoint']['id'] ) AND !empty( $salepoint['Salepoint']['id'] ) ) {
                return $this->redirect(array('action' => 'index',$salepoint['Salepoint']['id'] ));
            } else {  */
            $caisse_id = $this->Session->read('caisse_id');
            $session = $this->Sessionuser->find('first', ['conditions' => ['user_id' => $user_id,
                'caisse_id' => $caisse_id, ],
                'order' => ['Sessionuser.id desc'], ]);
            $session_nv = (!isset($session['Sessionuser']['id'])) ? 1 : 0;

            $session_en_cours = (!isset($session['Sessionuser']['date_fin'])) ? false : true;

            $session = $this->Sessionuser->find('first', ['conditions' => ['user_id' => $user_id,
                    'caisse_id' => $caisse_id, ],
                    'order' => ['Sessionuser.id desc'], ]);

            $session_cloture = (isset($session['Sessionuser']['date_fin']) and !empty($session['Sessionuser']['date_fin'])) ? 1 : 0;

            $caisse_old = $this->Session->read('caisse_old');

            if (($session_cloture == 1) or ($session_nv == 1 and $session_en_cours == false)) {
                ////////save session

                $caisse_old = $this->Session->read('caisse_id');
                $this->Session->write('caisse_old', $caisse_old);

                if ($type_app == 2) {
                    $store_id_ready = $store_id_db;
                }else{
                    $store_id_ready = $this->Session->read('Auth.User.StoreSession.id');
                }
                
                $data = [
                        'date_debut' => date('d-m-Y H:i:s'),
                        'user_id' => $user_id,
                        'site_id' => $store_id_ready,
                        'caisse_id' => ($caisse_id != null) ? $caisse_id : null,
                    ];
                $this->Sessionuser->save($data);
            }
            $salepoint = $this->Salepoint->find('first', [
                    'conditions' => [
                        'Salepoint.etat' => -1,
                        'Salepoint.onhold' => -1,
                        'OR' => [
                            ['Salepoint.commande_id !=' => 0],
                            ['Salepoint.ecommerce_id !=' => 0],
                        ],
                        'Salepoint.reference LIKE' => "%$prefix%",
                        'Salepoint.user_id' => $user_id,
                    ],
                    'contain' => ['Commande', 'Ecommerce', 'Client'],
                    'order' => ['Salepoint.id' => 'DESC'],
                ]);

            if (isset($salepoint['Salepoint']['id']) and !empty($salepoint['Salepoint']['id'])) {
                return $this->redirect(['action' => 'index', $salepoint['Salepoint']['id']]);
            }

            /*  $salepoint = $this->Salepoint->find('first',[
                'conditions' => [
                    'Salepoint.user_id' => $user_id ,
                    'Salepoint.reference LIKE' => "%$prefix%",
                    'Salepoint.etat !=' => 3,
                ],
                'order' => ['Salepoint.id' => 'DESC']
            ]);

            if ( isset( $salepoint['Salepoint']['id'] ) AND !empty( $salepoint['Salepoint']['id'] ) ) {
                return $this->redirect(array('action' => 'index',$salepoint['Salepoint']['id'] ));
            }  */

            $data['Salepoint']['user_id'] = $user_id;
            $data['Salepoint']['date'] = date('d-m-Y H:i:s');
            $caisse_id = $this->Session->read('caisse_id');
            $data['Salepoint']['caisse_id'] = ($caisse_id != null) ? $caisse_id : null;
            if ($this->Salepoint->save($data)) {
                return $this->redirect(['action' => 'index', $this->Salepoint->id]);
            }
        } else {
            /* $salepoint = $this->Salepoint->find('first',[
                'conditions' => [
                    'Salepoint.etat' => -1,
                    'Salepoint.onhold' => -1,
                    'Salepoint.reference' => "Prov" ,
                    'Salepoint.user_id' => $user_id
                ],
                'order' => ['Salepoint.id' => 'DESC']
            ]); */
            $salepoint = $this->Salepoint->find('first', [
                'conditions' => [
                    'Salepoint.id' => $salepoint_id,
                ],
                'contain' => ['Client'],
                'order' => ['Salepoint.id' => 'DESC'],
            ]);

            /* 	if ( !isset( $salepoint['Salepoint']['id'] ) AND empty( $salepoint['Salepoint']['id'] ) ) {

                   $salepoint = $this->Salepoint->find('first', [
                   'conditions' => [
                       'Salepoint.user_id' => $user_id ,
                       'Salepoint.id' => $salepoint_id ,
                       'etat' => -1
                   ]
               ]);
               } */
            if (isset($salepoint['Salepoint']['id']) and !empty($salepoint['Salepoint']['id'])) {
                $this->request->data = $salepoint;
            }/*  else {
                $data['Salepoint']['user_id'] = $user_id;
                $data['Salepoint']['date'] = date('d-m-Y');
                if($this->Salepoint->save($data)) return $this->redirect(array('action' => 'index',$this->Salepoint->id ));
            } */
        }

        $details = $this->Salepoint->Salepointdetail->find('all', [
            'conditions' => [
                'Salepointdetail.stat' => -1,
                'Salepointdetail.onhold' => -1,
                'Salepointdetail.salepoint_id' => $salepoint_id,
            ],
            'contain' => ['Produit'],
        ]);

        $last_ticket = $this->Salepoint->find('first', ['conditions' => ['Salepoint.user_id' => $user_id, 'Salepoint.etat' => [2, 3], 'Salepoint.print' => -1], 'order' => ['Salepoint.id' => 'DESC']]);
        $sessionclose = $this->Sessionuser->find('first', ['conditions' => ['Sessionuser.user_id' => $user_id, 'Sessionuser.print' => 1]]);

        $holdlist = $this->Attente->find('count', ['conditions' => ['Attente.user_id' => $user_id]]);

        $users = $this->User->findList([], true);
        $depots = $this->Salepoint->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);

        $first_key = key($depots);

        /////////permissions
        $user = $this->Session->read('Auth.User');
        $role_id = $user['role_id'];
        $this->loadModel('Permissionpos');

        $permissions = $this->Permissionpos->find('all', ['conditions' => ['role_id' => $role_id]]);
        $perm = [];
        $perm['Remise'] = false;
        $perm['Remise ticket'] = false;
        $perm['Offert'] = false;
        $perm['Annuler ticket'] = false;
        $perm['Cloture caisse'] = false;
        $perm['Activation cheque cadeau'] = false;
        $perm["Activation bon d'achat"] = false;
        $perm['Activation carte client'] = false;
        $perm['Correction mode paiement'] = false;
        $perm['Retour produit'] = false;
        $perm['Reimpression facture ou ticket'] = false;
        foreach ($permissions as $permission) {
            $perm[$permission['Permissionpos']['nom']] = ($permission['Permissionpos']['permission'] == 1) ? true : false;
        }

        ////////////

        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $fee = $salepoint['Salepoint']['fee'];
        $expedition = $salepoint['Salepoint']['expedition']; //$this->Session->read('expedition');
        $salepoint2 = $this->Salepoint->find('first', ['contain' => ['Ecommerce'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        $payment_method = isset($salepoint2['Ecommerce']['payment_method']) ? $salepoint2['Ecommerce']['payment_method'] : '';
        $this->set(compact('payment_method', 'perm', 'first_key', 'sessionclose', 'expedition', 'fee', 'details', 'users', 'user_id', 'holdlist', 'depots', 'last_ticket'));
        $this->getPath($this->idModule);
        $this->layout = 'pos';
    }

    /* public function checkecommerce($ecommerce_id = null,$salepoint_id = null) {
        $commande = $this->Ecommerce->find('first',[ 'conditions' => ['Ecommerce.id' => $ecommerce_id] ]);
        if ($commande["Ecommerce"]["statut"] != "en cours") {
            $this->Ecommerce->id = $ecommerce_id;
            $this->Ecommerce->saveField("statut", "en cours");
            $response["error"] = false;
        }
        else {
            $response["error"] = true;
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    } */
    public function closeSession($salepoint_id)
    {
        $salepoint_d = $this->Salepoint->Salepointdetail->find('first', [
            'conditions' => [
                'salepoint_id' => $salepoint_id,
            ],
        ]);
        if (!empty($salepoint_d['Salepointdetail']['id'])) {
            $this->Session->setFlash('Opération impossible : Il y a un ticket en cours!', 'alert-danger');
        }
        $user_id = $this->Session->read('Auth.User.id');
        $caisse_id = $this->Session->read('caisse_id');
        $session = $this->Sessionuser->find('all', ['conditions' => ['user_id' => $user_id,
        'caisse_id' => $caisse_id, ],
        'order' => ['Sessionuser.id desc'], ]);
        $data = [
            'date_fin' => date('d-m-Y H:i:s'),
            'print' => 1,
        ];
        $this->Sessionuser->id = $session[0]['Sessionuser']['id'];
        $this->Sessionuser->save($data);

        return $this->redirect(['action' => 'index', $salepoint_id]);
    }

    public function closeSessionTicket($session_id, $salepoint_id)
    {
        /* $this->request->data = $this->Salepoint->find('first',['contain'=>['Client'],'conditions' => ['Salepoint.id' => $salepoint_id] ]);
        $details = $this->Salepoint->Salepointdetail->find('all',['contain'=>['Produit'],'conditions' => [ 'Salepointdetail.salepoint_id' => $salepoint_id ] ]);
        $societe = $this->GetSociete();
        $this->set(compact('societe','details')); */
        $options = ['contain' => ['User', 'Site', 'Caisse'], 'conditions' => ['Sessionuser.'.$this->Sessionuser->primaryKey => $session_id]];
        $sessionuser = $this->Sessionuser->find('first', $options);

        $salepoint = $this->Salepoint->query("Select SUM(total_paye+fee) AS chiffre_affaire from salepoints where salepoints.date_u >= '{$sessionuser['Sessionuser']['date_debut']}' AND salepoints.date_u <= '{$sessionuser['Sessionuser']['date_fin']}' and salepoints.user_id = {$sessionuser['Sessionuser']['user_id']} and salepoints.caisse_id = {$sessionuser['Sessionuser']['caisse_id']} and salepoints.etat = 2");
        $chiffre_affaire = $salepoint[0][0]['chiffre_affaire'];

        $salepoint = $this->Salepoint->query("Select SUM(total_apres_reduction+fee) AS chiffre_affaire from salepoints where salepoints.date_u >= '{$sessionuser['Sessionuser']['date_debut']}' AND salepoints.date_u <= '{$sessionuser['Sessionuser']['date_fin']}' and salepoints.ecommerce_id != null and salepoints.user_id = {$sessionuser['Sessionuser']['user_id']} and salepoints.caisse_id = {$sessionuser['Sessionuser']['caisse_id']} and salepoints.etat = 2");
        $chiffre_affaire_ecom = $salepoint[0][0]['chiffre_affaire'];

        $salepoints = $this->Salepoint->find('all', ['conditions' => [
                'Salepoint.date_u >=' => $sessionuser['Sessionuser']['date_debut'],
                'Salepoint.date_u <=' => $sessionuser['Sessionuser']['date_fin'],
                'Salepoint.user_id' => $sessionuser['Sessionuser']['user_id'],
                'Salepoint.caisse_id' => $sessionuser['Sessionuser']['caisse_id'],
                'Salepoint.reference NOT LIKE ' => '%T09%',
                'Salepoint.etat' => 2,
            ]]);

        $total_especes = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_especes += ($avance['Avance']['mode'] == 'espece') ? $avance['Avance']['montant'] : 0;
            }
        }

        $total_cod = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_cod += ($avance['Avance']['mode'] == 'cod') ? $avance['Avance']['montant'] : 0;
            }
        }

        $total_wallet = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_wallet += ($avance['Avance']['mode'] == 'wallet') ? $avance['Avance']['montant'] : 0;
            }
        }
        $total_carte = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_carte += ($avance['Avance']['mode'] == 'Carte') ? $avance['Avance']['montant'] : 0;
            }
        }
        $total_cheque = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_cheque += ($avance['Avance']['mode'] == 'cheque') ? $avance['Avance']['montant'] : 0;
            }
        }
        $total_bon_achat = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_bon_achat += ($avance['Avance']['mode'] == 'bon_achat') ? $avance['Avance']['montant'] : 0;
            }
        }
        $total_cheque_cadeau = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_cheque_cadeau += ($avance['Avance']['mode'] == 'cheque_cadeau') ? $avance['Avance']['montant'] : 0;
            }
        }
        $total_offert = 0;
        foreach ($salepoints as $salepoint) {
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                $total_offert += ($avance['Avance']['mode'] == 'offert') ? $avance['Avance']['montant'] : 0;
            }
        }

        /* query("Select SUM(total_apres_reduction+fee) AS chiffre_affaire from salepoints where salepoints.date_u BETWEEN  '{$sessionuser['Sessionuser']['date_debut']}' AND '{$sessionuser['Sessionuser']['date_fin']}' and salepoints.payment_method LIKE '%cod%' and salepoints.user_id = {$sessionuser['Sessionuser']['user_id']} and salepoints.caisse_id = {$sessionuser['Sessionuser']['caisse_id']} and salepoints.etat = 2"); */

        /* $total_especes = $salepoint[0][0]["chiffre_affaire"];
 */

        $salepoints = $this->Salepoint->find('all', ['conditions' => [
                'Salepoint.date_u >=' => $sessionuser['Sessionuser']['date_debut'],
                'Salepoint.date_u <=' => $sessionuser['Sessionuser']['date_fin'],
                'Salepoint.user_id' => $sessionuser['Sessionuser']['user_id'],
                'Salepoint.caisse_id' => $sessionuser['Sessionuser']['caisse_id'],
                'Salepoint.etat' => 2,
            ]]);

        $payment_methods = [];
        foreach ($salepoints as $salepoint) {
            $cartes = ['Carte', 'cheque'];
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint['Salepoint']['id']]]);
            foreach ($avances as $avance) {
                if (in_array($avance['Avance']['mode'], $cartes)) {
                    if ($avance['Avance']['montant'] != 0) {
                        $payment_methods[][$avance['Avance']['mode']] = $avance['Avance']['montant'];
                    }
                }
            }
        }
        //$salepoint = $this->Salepoint->find('first',['conditions' => ['Salepoint.id' => $salepoint_id] ]);
        //$store_of_salepoint = $salepoint['Salepoint']['store'];
        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $store_data = $this->Store->find('first', ['conditions' => ['id' => $selected_store]]);
        //var_dump($store_data);
        $societe = $this->GetSociete($store_data['Store']['societe_id']);
        $this->set(compact('salepoint_id', 'store_data', 'total_cod', 'total_wallet', 'total_carte', 'total_cheque', 'total_bon_achat', 'total_cheque_cadeau', 'total_offert', 'chiffre_affaire_ecom', 'total_especes', 'societe', 'sessionuser', 'chiffre_affaire', 'payment_methods', 'salepoints'));

        $this->layout = false;
    }

    public function Sessionprinted($session_id, $salepoint_id)
    {
        $response = [];
        $response['error'] = true;
        $this->Sessionuser->id = $session_id;
        if ($this->Sessionuser->saveField('print', 0)) {
            $response['error'] = false;
        }

        // debug($response);
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function traitercommandeglovo($commande_id = null, $salepoint_id = null)
    {
        $commande = $this->Commandeglovo->find('first', ['conditions' => ['Commandeglovo.id' => $commande_id]]);
        $depots = $this->Session->read('depots');
        $depots = $this->Salepoint->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);
        $first_key = key($depots);

        $this->Commandeglovo->id = $commande_id;
        /*  $this->Commandeglovo->saveField('statut', 'en cours');
         $this->Commandeglovo->saveField('depot_id', $first_key);*/
        $client_id = (isset($commande['Commandeglovo']['client_id']) and !empty($commande['Commandeglovo']['client_id'])) ? $commande['Commandeglovo']['client_id'] : null;
        $details = $this->Commandeglovo->Commandeglovodetail->find('all', ['conditions' => ['Commandeglovodetail.commandes_glovo_id' => $commande_id]]);
        $this->Salepoint->Salepointdetail->deleteAll(['Salepointdetail.salepoint_id' => $salepoint_id]);
        /* $fee = $commande['Ecommerce']['fee']; */
        /* $expedition = $commande['Ecommerce']['shipment']; */

        $data['Salepoint']['glovo_id'] = $commande_id;
        $data['Salepoint']['client_id'] = $client_id;
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['type_vente'] = 2;
        /* $data['Salepoint']['fee'] = $fee; */
        /* $data['Salepoint']['expedition'] = $expedition; */

        $data['Salepointdetail'] = [];
        foreach ($details as $k => $v) {
            $data['Salepointdetail'][] = [
                'glovodetail_id' => $v['Commandeglovodetail']['id'],
                'prix_vente' => $v['Commandeglovodetail']['price'],
                'unit_price' => $v['Commandeglovodetail']['price'],
                'produit_id' => $v['Commandeglovodetail']['produit_id'],
                'qte_cmd' => $v['Commandeglovodetail']['quantity'],
                'total' => 0,
                'ttc' => 0,
                'qte' => 0,
            ];
        }

        if ($this->Salepoint->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function traiterecommerce($ecommerce_id = null, $salepoint_id = null)
    {
        $commande = $this->Ecommerce->find('first', ['conditions' => ['Ecommerce.id' => $ecommerce_id]]);
        $depots = $this->Session->read('depots');
        $depots = $this->Salepoint->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);
        $first_key = key($depots);

        $this->Ecommerce->id = $ecommerce_id;
        // $this->Ecommerce->saveField('statut', 'en cours');
        $this->Ecommerce->saveField('depot_id', $first_key);
        $client_id = (isset($commande['Ecommerce']['client_id']) and !empty($commande['Ecommerce']['client_id'])) ? $commande['Ecommerce']['client_id'] : null;
        $details = $this->Ecommerce->Ecommercedetail->find('all', ['conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id]]);
        $this->Salepoint->Salepointdetail->deleteAll(['Salepointdetail.salepoint_id' => $salepoint_id]);
        $fee = $commande['Ecommerce']['fee'];
        $expedition = $commande['Ecommerce']['shipment'];
        $payment_method = $commande['Ecommerce']['payment_method'];
        $ecommerce_id = $ecommerce_id;

        $data['Salepoint']['ecommerce_id'] = $ecommerce_id;
        $data['Salepoint']['client_id'] = $client_id;
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['type_vente'] = 2;
        $data['Salepoint']['fee'] = $fee;
        $data['Salepoint']['expedition'] = $expedition;
        $data['Salepoint']['payment_method'] = $payment_method;  

        $data['Salepointdetail'] = [];
        foreach ($details as $k => $v) {
            $data['Salepointdetail'][] = [
                'ecommercedetail_id' => $v['Ecommercedetail']['id'],
                'prix_vente' => $v['Ecommercedetail']['prix_vente'],
                'unit_price' => $v['Ecommercedetail']['unit_price'],
                'produit_id' => $v['Ecommercedetail']['produit_id'],
                'qte_cmd' => $v['Ecommercedetail']['qte_ordered'],
                'total' => 0,
                'ttc' => 0,
                'qte' => 0,
            ];
        }

        if ($this->Salepoint->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function printed($salepoint_id = null)
    {
        $this->Salepoint->id = $salepoint_id;
        if ($this->Salepoint->saveField('print', 1)) {
            die('La mise à jour été effectué avec succès.');
        } else {
            die('Il y a un problème');
        }
    }
    

    public function ticket($salepoint_id = null)
    {
        $this->request->data = $this->Salepoint->find('first', ['contain' => ['Chequecadeau', 'Client', 'Commandeglovo'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);
        $modes = [];
        foreach ($avances as $avance) {
            if ($avance['Avance']['montant'] != 0) {
                $modes[] = $this->getModePaiment($avance['Avance']['mode']);
            }
        }
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);

        // récupérer les info du store connecté
        $store_of_salepoint = $this->data['Salepoint']['store'];
        $store_data = $this->Store->find('first', ['conditions' => ['id' => $store_of_salepoint]]);

        // récupérer les info du caisse connectée
        $caisse_of_salepoint = $this->data['Salepoint']['caisse_id'];
        $caisse_data = $this->Caisse->find('first', ['conditions' => ['id' => $caisse_of_salepoint]]);

        /* $details_tva = $this->Salepoint->Salepointdetail->find('all',
        ['contain' => ['Produit.tva_vente'], 'fields' => ['Produit.tva_vente', 'SUM(Salepointdetail.total) as mnt_ht', 'Produit.tva_vente', '(SUM(Salepointdetail.total) * (Produit.tva_vente/100)) as taxe',
        'SUM(Salepointdetail.ttc) as mnt_ttc', ],
        'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id],
        'group' => ['Produit.tva_vente'], ]);

        $details_tva = $this->Salepoint->Salepointdetail->find('all',
        ['contain' => ['Produit.tva_vente'], 'fields' => ['Produit.tva_vente', 'SUM(Salepointdetail.total) as mnt_ht', 'Produit.tva_vente', '(SUM(Salepointdetail.ttc)  - SUM(Salepointdetail.total)) as taxe',
        'SUM(Salepointdetail.ttc) as mnt_ttc', ],
        'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id],
        'group' => ['Produit.tva_vente'], ]);

*/

        // calculer la TVA
        $tvaRate = 5; 
        $tvaDecimal = 1 + ($tvaRate / 100);

        $str = "SELECT tva, SUM(mnt_ht) as ht, SUM(taxe) AS tax, SUM(mnt_ttc) As ttc FROM ( 
            SELECT Produits.tva_vente As tva, 
                Salepointdetails.ttc / (1 + (Produits.tva_vente/100)) As mnt_ht, 
                Salepointdetails.ttc * (Produits.tva_vente / 100) / (1 + (Produits.tva_vente/100)) as taxe, 
                Salepointdetails.ttc As mnt_ttc
            FROM Produits, Salepointdetails 
            WHERE Produits.id = Salepointdetails.produit_id 
            AND Salepointdetails.salepoint_id = :salepoint_detail_id
            UNION ALL
            SELECT {$tvaRate}, 
                (Salepoints.fee / {$tvaDecimal}), 
                (Salepoints.fee * " . ($tvaRate / 100) . " / {$tvaDecimal}), 
                Salepoints.fee
            FROM Salepoints
            WHERE Salepoints.id = :salepoint_detail_id AND fee <> 0
        ) AS Tmps
        GROUP BY tva";


        $db = $this->Salepoint->getDataSource();

        $details_tva = $db->fetchAll(
                $str,
            ['salepoint_detail_id' => $salepoint_id]
        );

        /* 		var_dump(number_format($details_tva[0][0]["mnt_ht"], 2, ',', ' '));die();  */

        $societe = $this->GetSociete($store_data['Store']['societe_id']);
        $modes = implode(',', $modes);

        $ref_bon_achat = '';
        if (strpos($modes, "Bon d'achat") !== false) {
            $this->loadModel('Bonachat');
            $bonachats = $this->Bonachat->find('first', [
                'conditions' => ['id' => $this->request->data['Salepoint']['id_bon_achat']], ]
            );

            $ref_bon_achat = $bonachats['Bonachat']['reference'];
        }
        $ref_cheque_cad = '';
        if (strpos($modes, 'Chèque cadeau') !== false and isset($this->request->data['Salepoint']['id_cheque_cad'])) {
            $this->loadModel('Chequecadeau');
            $cheque_cad = $this->Chequecadeau->find('first', [
                'conditions' => ['id' => $this->request->data['Salepoint']['id_cheque_cad']], ]
            );

            $ref_cheque_cad = $cheque_cad['Chequecadeau']['reference'];
        }

        $this->set(compact('details_tva', 'avances', 'ref_cheque_cad', 'ref_bon_achat', 'societe', 'modes', 'details', 'store_data', 'caisse_data'));
        $this->layout = false;
    }

    public function getModePaiment($k = null)
    {
        $Arr = ['espece' => 'Espèces', 'cod' => 'cod',
         'wallet' => 'Wallet', 'bon_achat' => "Bon d'achat", 'cheque_cadeau' => 'Chèque cadeau',
         'Carte' => 'Carte', 'delayed' => 'Delayed', 'tpe' => 'Tpe', 'virement' => 'Virement', 'cmi' => 'CMI', 'offert' => 'Offert', 'cheque' => 'Chèque', ];
        if ($k === null) {
            return $Arr;
        }

        return (isset($Arr[$k])) ? $Arr[$k] : '';
    }

    public function ecommercedetails($ecommerce_id = null)
    {
        $this->request->data = $this->Ecommerce->find('first', ['conditions' => ['Ecommerce.id' => $ecommerce_id]]);
        $details = $this->Ecommerce->Ecommercedetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id]]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function Commandeglovodetails($commandeglovo_id = null)
    {
        $this->request->data = $this->Commandeglovo->find('first', ['conditions' => ['Commandeglovo.id' => $commandeglovo_id]]);
        $details = $this->Commandeglovo->Commandeglovodetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Commandeglovodetail.commandes_glovo_id' => $commandeglovo_id]]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function ecommerces($salepoint_id = null, $barcode = null)
    {
        $this->generatecommandes();
        $conditions['Ecommerce.etat'] = -1;
        $conditions['Ecommerce.store_id'] = $this->Session->read('Auth.User.StoreSession.id');

        if (!empty($barcode)) {
            $conditions['Ecommerce.barcode LIKE '] = '%'.trim($barcode).'%';
        }
        $ecommerces = $this->Ecommerce->find('all', [
            'conditions' => $conditions,
            'contain' => ['Client'],
            'joins' => [
                ['table' => 'ecommercedetails', 'alias' => 'Ecommercedetail', 'type' => 'INNER', 'conditions' => ['Ecommercedetail.ecommerce_id = Ecommerce.id', 'Ecommercedetail.deleted = 0']],
            ],
            'order' => ['Ecommerce.date desc'],
            'group' => ['Ecommerce.id'],
        ]);
        $this->set(compact('salepoint_id', 'ecommerces'));
        $this->layout = false;
    }

    public function traiterRemise($client_id, $id = null)
    {
        $this->Salepoint->id = $id;
        $this->Salepoint->saveField('client_id', $client_id);

        return $this->redirect($this->referer());
    }

    public function AuthOLD($module, $pass)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $response = [];
        $findUser = $this->User->find('first', ['conditions' => ['id' => $user_id,
        'password' => AuthComponent::password($pass), ]]);
        if (!empty($findUser)) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    // CheckPermission
    public function Auth($module, $pass)
    {
        // Mise à jour 4.1 => 
        // Annuler l'USER SESSION ID et rempalcer par : le script doit chercher dans tous les mot de passe dans la BDD
        // $user_id = $this->Session->read('Auth.User.id'); // Retirer cet element
        // $findUser = $this->User->find('first', ['conditions' => ['id' => $user_id, 'password' => AuthComponent::password($pass), ]]);
    
        $this->loadModel('Permissionpos');
        $response = [];
        $findUser = $this->User->find('first', ['conditions' => ['password' => AuthComponent::password($pass), ]]); // Faire la recherche sur le ID USER qui ==mot de passe
    
        $role_id = $findUser['User']['role_id'];
    
        $checkpermpos = $this->Permissionpos->find('all', 
                ['conditions' => [
                    'Permissionpos.role_id' => $role_id,
                    'Permissionpos.nom' => 'Offert',
                    ]]);
    
        $permission_value = $checkpermpos[0]['Permissionpos']['permission'];
    
        if ($permission_value == 0) {
            http_response_code(403);
            die('Vous n\'avez pas le droit d\'effectuer cette action');
        }
    
        if (!empty($findUser)) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }

        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }


    public function remisepopup($id = null)
    {
        //$remises = $this->Remiseclient->find('all', ['contain' => 'Client']);
        $this->loadModel('Client');
        $remises = $this->Client->find('all', ['conditions' => ['Client.id !=' => 1]]);
        $this->set(compact('remises', 'id'));
        $this->layout = false;
    }

    public function generatecommandes()
    {
        if (!function_exists('curl_init')) {
            die('JCOLLAB NEEDS CONFIG - cURL is not enabled. Please enable cURL in your PHP configuration.');
        }

        $ch = curl_init();
        $parametres = $this->GetParametreSte();

        $url = $parametres['Api pending'];


        
        $headers = [
            'Content-Type:application/json',
        ];
        $store_id = $this->Session->read('Auth.User.StoreSession.id');
        $storeSession = $this->Store->find('first', ['conditions' => ['Store.id' => $store_id], 'contain' => ['Societe']]);
        $id_ecommerce = $storeSession['Store']['id_ecommerce'];
        $site = [];
        $site['site'] = intval($id_ecommerce);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($site),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $parametres['User'].':'.$parametres['Password'],
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $return = curl_exec($ch);
        curl_close($ch);
        $return = json_decode($return, true);

        // check the statuts of API
        //$statuts_of_API = $return["data"]["status"];

        if (isset($return['data']['status']) and $return['data']['status'] = '401' and $return['data']['status'] = '500') {
            echo "<br />
					<center>
					<p class='alert-danger'>Module E-Commerce: problème de la connexion avec le serveur / API !</p>

					<br />
					<div class='modal-footer'>
						<button type='button' class='btn default' data-dismiss='modal'>Fermer</button>
					</div>

					<center>
					<br />";
            die();
        }

        if (isset($return) and !empty($return)) {
            $data = [];
            foreach ($return as $v) {
                $data[$v['id']]['Ecommerce'] = [
                    'barcode' => $v['number'],
                    'online_id' => $v['id'],
                    'fee' => $v['fee'],
                    'shipment' => $v['shipment'],
                    'payment_method' => $v['payment_method'],
                    'adresse' => $v['customer']['address'],
                    'date' => date('Y-m-d', strtotime($v['date_created'])),
                    'store_id' => $store_id,
                ];
                $this->created_at = $v['date_created'];

                if (isset($v['customer']['id']) and !empty($v['customer']['id'])) {
                    $client = $this->Ecommerce->Client->find('first', ['fields' => ['id'], 'conditions' => ['Client.id_ecommerce' => $v['customer']['id']]]);

                    if (isset($client['Client']['id']) and !empty($client['Client']['id'])) {
                        $data[$v['id']]['Ecommerce']['client_id'] = $client['Client']['id'];
                    } else {
                        $insert = ['designation' => $v['customer']['name'], 'telmobile' => $v['customer']['phone'], 'email' => $v['customer']['email'], 'id_ecommerce' => $v['customer']['id'], 'organisme' => 1];
                        $this->Ecommerce->Client->create();
                        if ($this->Ecommerce->Client->save($insert)) {
                            $data[$v['id']]['Ecommerce']['client_id'] = $this->Ecommerce->Client->id;
                        }
                    }
                }
                //var_dump($v['line_items']);die();
                if (isset($v['line_items']) and !empty($v['line_items'])) {
                    $data[$v['id']]['Ecommercedetail'] = [];
                    foreach ($v['line_items'] as $key => $value) {
                        $produit = $this->Produit->find('first', ['fields' => ['id'], 'conditions' => ['Produit.code_barre' => $value['product_id']]]);
                        $produit_id = (isset($produit['Produit']['id']) and !empty($produit['Produit']['id'])) ? $produit['Produit']['id'] : null;

                        //$qte_cmd = (isset($value['quantity']) and !empty($value['quantity'])) ? (int) $value['quantity'] : (float) $value['weight_ordered'] ;

                        $qte_cmd = ($value['variation_id'] != 0) ? $value['weight_ordered'] : $value['quantity_ordered'];

                        $qte_ordered = ($value['variation_id'] == 0) ? $value['quantity_ordered'] : $value['weight_ordered'];

                        $data[$v['id']]['Ecommercedetail'][] = [
                            /* 'prix_vente' => ($value['variation_id'] == 0) ? $value['unit_price'] : $value['unit_price']*($value['variation_id']/1000), */
                            'unit_price' => $value['unit_price'],
                            /* 'prix_vente' => ($value['variation_id'] == 0) ? $value['unit_price'] : $value['unit_price']*($value['weight_ordered']/$qte_cmd),
 */                            'prix_vente' => $value['unit_price'],
                            'produit_id' => $produit_id,
                            'online_id' => $value['id'],
                            'qte_cmd' => $qte_cmd,
                            'preparation' => (isset($value['preparation'])) ? $value['preparation'] : null,
                            'qte_ordered' => $qte_ordered,
                            'variation_id' => $value['variation_id'],
                            'total' => 0,
                            'ttc' => 0,
                            'qte' => 0,
                                ];
                    }
                }
            }

            if (isset($data) and empty($data)) {
                $this->Session->setFlash('Opération impossible : la liste des commandes est vide !', 'alert-danger');

                return $this->redirect($this->referer());
            }

            /* 	$commandes = $this->Ecommerce->find("list",['fields'=>['id','id'],'conditions'=>['Ecommerce.etat'=>-1]]);
                $this->Ecommerce->updateAll(['Ecommerce.deleted'=>0],['Ecommerce.id' => $commandes]);

                $details = $this->Ecommerce->Ecommercedetail->find('list',['fields'=>['id','id'],'conditions'=>['Ecommercedetail.ecommerce_id'=>$commandes]]);
                $this->Ecommerce->Ecommercedetail->updateAll(['Ecommercedetail.deleted'=>0],['Ecommercedetail.id'=>$details]);
                 */
            foreach ($data as $commande) {
                $online_id = $commande['Ecommerce']['online_id'];
                $ecommerce = $this->Ecommerce->query("Select * from ecommerces where online_id=$online_id and store_id=$store_id");
                if (!isset($ecommerce[0]['ecommerces']['id'])) {
                    $this->Ecommerce->saveAssociated($commande);
                }
            }

            /* $this->Session->setFlash('La liste des commandes a été mise à jour avec succès.','alert-success');
            return $this->redirect($this->referer());
 */
        } else {
            //  $this->Session->setFlash('Module E-Commerce: problème de la connexion e-commerce !','alert-danger');
               // return $this->redirect($this->referer());
        }
    }

    // fonction de récupération des ventes ecome => pop-up de POS
    public function ecommerce($salepoint_id = null, $barcode = null)
    {
        // $this->generatecommandes();

        // La liste des statuts de commande à afficher dans le POS
        $conditions['Ecommerce.statut'] = ['pending', 'confirmed', 'in_preparation', 'ready_for_delivery','in_progress'];
        $conditions['Ecommerce.etat'] = -1;
        $conditions['Ecommerce.deleted'] = 0;
        $conditions['Ecommerce.store_id'] = $this->Session->read('Auth.User.StoreSession.id');

        $ecommerces = $this->Ecommerce->find('all', [
            'conditions' => $conditions,
            'contain' => ['Client'],
            'joins' => [
                ['table' => 'ecommercedetails', 'alias' => 'Ecommercedetail', 'type' => 'INNER', 'conditions' => ['Ecommercedetail.ecommerce_id = Ecommerce.id', 'Ecommercedetail.deleted = 0']],
            ],
            'order' => ['Ecommerce.date desc'],
            'group' => ['Ecommerce.id'],
        ]);
        $ecommerce = $this->Ecommerce->find('all', [
            'order' => ['Ecommerce.date desc'], ]);
        $created_at = $ecommerce[0]['Ecommerce']['date']; //$this->created_at;
        $this->set(compact('created_at', 'salepoint_id', 'ecommerces'));
        $this->layout = false;       
        
        //$this->SaveOrdersFromApi();  // save orders from API to DB - WEBSITE NEXA
    }

    public function commandesGlovo($salepoint_id = null, $barcode = null)
    {
        $conditions['Commandeglovo.etat'] = -1;
        /* $conditions['Ecommerce.statut !='] = 'en cours';
        */
        $conditions['Commandeglovo.store_id'] = $this->Session->read('Auth.User.StoreSession.id');

        $commandeglovos = $this->Commandeglovo->find('all', [
            'conditions' => $conditions,
            'contain' => ['Client'],
            'joins' => [
                ['table' => 'commande_glovo_details', 'alias' => 'Commandeglovodetail', 'type' => 'INNER', 'conditions' => ['Commandeglovodetail.commandes_glovo_id = Commandeglovo.id', 'Commandeglovodetail.deleted = 0']],
            ],
            'order' => ['Commandeglovo.estimated_pickup_time desc'],
            'group' => ['Commandeglovo.id'],
        ]);

        $this->set(compact('salepoint_id', 'commandeglovos'));
        $this->layout = false;
    }

    public function duplicateEcommerce($salepoint_id = null, $product_id = null)
    {
        $created_at = 1;
        $this->set(compact('created_at'));
        $this->layout = false;
    }

    public function details($salepoint_id = null)
    {
        $details = $this->Salepoint->Salepointdetail->find('all', [
            'conditions' => [
                'Salepointdetail.stat' => -1,
                'Salepointdetail.onhold' => -1,
                'Salepointdetail.salepoint_id' => $salepoint_id,
            ],
            'contain' => ['Produit'],
        ]);

        $this->request->data = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        if ($this->request->data['Salepoint']['total_apres_reduction'] > $this->request->data['Salepoint']['total_cmd']) {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function onhold($salepoint_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $holding = $this->Attente->find('all', ['conditions' => ['Attente.user_id' => $user_id]]);
        // get pos
        $sales = $this->Salepoint->find('first', [
            'conditions' => [
                'Salepoint.id' => $salepoint_id,
            ],
        ]);

        $this->set(compact('holding', 'sales'));
        $this->layout = false;
    }

    public function commandes($salepoint_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $this->loadModel('Depot');
        $depots = $this->Depot->find('list', ['conditions' => ['store_id' => $selected_store],
        'fields' => ['id'], ]);

        $commandes = $this->Commande->find('all', [
            'conditions' => [
                'Commande.etat' => -1,
                'Commande.statut !=' => 'en cours',
                'Commande.depot_id' => $depots,
            ],
            'contain' => ['Client'],
            'joins' => [
                ['table' => 'commandedetails', 'alias' => 'Commandedetail', 'type' => 'INNER', 'conditions' => ['Commandedetail.commande_id = Commande.id', 'Commandedetail.deleted = 0']],
            ],
            'order' => ['Commande.id desc'],
            'group' => ['Commande.id'],
        ]);
        $this->set(compact('commandes'));
        $this->layout = false;
    }

    public function commandedetails($commande_id = null)
    {
        $this->request->data = $this->Commande->find('first', ['conditions' => ['Commande.id' => $commande_id]]);
        $details = $this->Commande->Commandedetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Commandedetail.commande_id' => $commande_id]]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function retours($salepoint_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $retours = $this->Salepoint->find('all', [
            'conditions' => [
                'Salepoint.etat' => 2,
            ],
            'contain' => ['Client'],
            'joins' => [
                ['table' => 'salepointdetails', 'alias' => 'Salepointdetail', 'type' => 'INNER', 'conditions' => ['Salepointdetail.salepoint_id = Salepoint.id', 'Salepointdetail.deleted = 0']],
            ],
            'order' => ['Salepoint.id desc'],
            'group' => ['Salepoint.id'],
        ]);
        $this->set(compact('retours'));
        $this->layout = false;
    }

    public function ticketdetails($salepoint_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function UpdateCommande($salepoint_id = null)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $ecommerce_id = $salepoint['Salepoint']['ecommerce_id'];
        $ecommerce = $this->Ecommerce->find('all', [
            'conditions' => [
                ['Ecommerce.etat' => 2, 'Ecommerce.id' => $ecommerce_id],
            ],
            'joins' => [
                ['table' => 'ecommercedetails', 'alias' => 'Ecommercedetail', 'type' => 'INNER', 'conditions' => ['Ecommercedetail.ecommerce_id = Ecommerce.id', 'Ecommercedetail.deleted = 0']],
                ['table' => 'clients', 'alias' => 'Client', 'type' => 'INNER', 'conditions' => ['Client.id = Ecommerce.client_id', 'Client.deleted = 0']],
                ['table' => 'produits', 'alias' => 'Produit', 'type' => 'INNER', 'conditions' => ['Produit.id = Ecommercedetail.produit_id', 'Produit.deleted = 0']],
            ],
            'order' => ['Ecommerce.date desc'],
            'fields' => ['Ecommerce.online_id', 'Ecommerce.date', 'Ecommerce.payment_method', 'Ecommerce.shipment',
            'Ecommerce.total_a_payer_ttc', 'Ecommerce.fee', 'Client.designation', 'Client.telmobile',
            'Client.email', 'Ecommercedetail.produit_id', 'Ecommercedetail.variation_id', 'Ecommercedetail.online_id', 'Ecommercedetail.qte_cmd', 'Ecommercedetail.qte', 'Ecommercedetail.unit_price', 'Ecommercedetail.qte_ordered', 'Produit.code_barre', ],
        ]);

        $data = [];
        $arr_details = [];
        $tab_details = [];
        $tab_customer = [];
        $j = 0;
        /*modif YK*/
        $store_id = $this->Session->read('Auth.User.StoreSession.id');
        $storeSession = $this->Store->find('first', ['conditions' => ['Store.id' => $store_id], 'contain' => ['Societe']]);
        $id_ecommerce = $storeSession['Store']['id_ecommerce'];

        for ($i = 0; $i < sizeof($ecommerce); ++$i) {
            $data[$j] = [
                        'site' => intval($id_ecommerce),
                        'id' => intval($ecommerce[$j]['Ecommerce']['online_id']),
                        'payment_method' => $ecommerce[$j]['Ecommerce']['payment_method'],
                        'shipment' => $ecommerce[$j]['Ecommerce']['shipment'],
                    ];

            if ($ecommerce[$i]['Ecommercedetail']['variation_id'] != '0') {
                $arr = [
                        'id' => intval($ecommerce[$i]['Ecommercedetail']['online_id']),
                        'product_id' => $ecommerce[$i]['Produit']['code_barre'],
                        'variation_id' => $ecommerce[$i]['Ecommercedetail']['variation_id'],
                        'quantity' => intval($ecommerce[$i]['Ecommercedetail']['qte_cmd']),
                        'unit_price' => floatval($ecommerce[$i]['Ecommercedetail']['unit_price']),
                        'weight_ordered' => floatval($ecommerce[$i]['Ecommercedetail']['qte']),
                    ];
            } else {
                $arr = [
                        'id' => intval($ecommerce[$i]['Ecommercedetail']['online_id']),
                        'product_id' => $ecommerce[$i]['Produit']['code_barre'],
                        'variation_id' => $ecommerce[$i]['Ecommercedetail']['variation_id'],
                        'quantity' => intval($ecommerce[$i]['Ecommercedetail']['qte_cmd']),
                        'unit_price' => floatval($ecommerce[$i]['Ecommercedetail']['unit_price']),
                        'quantity_ordered' => floatval($ecommerce[$i]['Ecommercedetail']['qte']),
                    ];
            }
            /* var_dump(($arr)); */

            array_push($arr_details, $arr);
            $data[$j]['line_items'] = $arr_details;
            /* var_dump($ecommerce[$i]["Ecommerce"]["online_id"]); */
        }

        $parametres = $this->GetParametreSte();
        //header('Content-Type: application/json; charset=UTF-8');
        $data = trim(json_encode(array_values($data)), '[]');
        //die( $data );
        $ch = curl_init();
        $url = $parametres['Api update pos'];
        $headers = [
            'Content-Type:application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $parametres['User'].':'.$parametres['Password'],
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $return = curl_exec($ch);
        curl_close($ch);
        $return = json_decode($return, true);
        if (array_key_exists('status', $return)) {
            return true;
        } else {
            return false;
        }
    }

    public function loadBouchiers($bouchier = null)
    {
        $codes_bouchier = $this->User->find('all', ['conditions' => ['code_bouchier !=' => 0, 'deleted' => 0]]);
        $response = [];
        $response['error'] = true;

        foreach ($codes_bouchier as $code_bouchier) {
            if ($code_bouchier['User']['code_bouchier'] == $bouchier) {
                $response['error'] = false;
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function verifChequecad($salepoint_id = null)
    {
        //cheque_cad
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $this->loadModel('Fidelite');
        $fidelite = $this->Fidelite->find('first');
        $response['active'] = 0;
        if (isset($fidelite['Fidelite']['montant_cheque_cad'])) {
            $montant_cheque = $fidelite['Fidelite']['montant_cheque_cad'];
            if ($salepoint['Salepoint']['check_cad'] == 0 and $salepoint['Salepoint']['points_fidelite'] > $montant_cheque) {
                $this->Session->setFlash('un cheque cadeau a été activé pour le client.', 'alert-success');
                $this->Salepoint->id = $salepoint_id;
                $this->Salepoint->saveField('check_cad', 1);
                $response['active'] = 1;
            }
        }
        die(json_encode($response));
    }

    public function verifpaiement($salepoint_id = null)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);

        $reponse = [];
        $reponse['error'] = false;
        $tot_apres_reduc = $salepoint['Salepoint']['total_apres_reduction'];
        $tottal_cmd = $salepoint['Salepoint']['total_cmd'];
        if (isset($salepoint['Salepoint']['ecommerce_id']) or isset($salepoint['Salepoint']['glovo_id'])) {
            if ($tot_apres_reduc > $tottal_cmd) {
                $reponse['error'] = true;
            }
        }

        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($reponse));
    }

    public function paiement($salepoint_id = null)
    {        
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);

        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);

        $error = false;
        $total = 0;

// $caisse_id = $this->Session->read('caisse_id');
$caisse_id = $this->Session->check('caisse_id') ? $this->Session->read('caisse_id') : 83;

        foreach ($details as $k => $v) {
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            //if ( $v['Salepointdetail']['qte'] < $v['Salepointdetail']['qte_cmd'] ) $error = true;
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : $qte_cmd;
            $total_unitaire = $qte * $v['Salepointdetail']['prix_vente'];
            $total = $total + $total_unitaire;
        }
        $total += $salepoint['Salepoint']['fee'];




        if ($this->request->is(['post', 'put'])) {
            //bon achat
            if ($this->request->data['Salepoint']['payment'] == 'bon_achat' or $this->request->data['Salepoint']['payment2'] == 'bon_achat') {
                if (!isset($salepoint['Salepoint']['mnt_bonachat'])) {
                    //$salepoint['Salepoint']['total_apres_reduction']
                    $this->Session->setFlash('aucun bon d\'achat n\'a été activé', 'alert-danger');

                    return $this->redirect($this->referer());
                } elseif ($this->request->data['Salepoint']['payment'] == 'bon_achat' and $this->request->data['Salepoint']['payment2'] != 'bon_achat') {
                    if (isset($salepoint['Salepoint']['mnt_bonachat']) and $salepoint['Salepoint']['mnt_bonachat'] > $this->request->data['Avance'][0]['montant']) {
                        $this->Session->setFlash('le montant du bon d\'achat est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                } elseif ($this->request->data['Salepoint']['payment'] != 'bon_achat' and $this->request->data['Salepoint']['payment2'] == 'bon_achat') {
                    if (isset($salepoint['Salepoint']['mnt_bonachat']) and $salepoint['Salepoint']['mnt_bonachat'] > $this->request->data['Avance'][1]['montant']) {
                        $this->Session->setFlash('le montant du bon d\'achat est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                } elseif ($this->request->data['Salepoint']['payment'] == 'bon_achat' and $this->request->data['Salepoint']['payment2'] == 'bon_achat') {
                    if (isset($salepoint['Salepoint']['mnt_bonachat']) and $salepoint['Salepoint']['mnt_bonachat'] > ($this->request->data['Avance'][0]['montant'] + $this->request->data['Avance'][1]['montant'])) {
                        $this->Session->setFlash('le montant du bon d\'achat est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                }
            }

            //cheque cadeau
            if ($this->request->data['Salepoint']['payment'] == 'cheque_cadeau' or $this->request->data['Salepoint']['payment2'] == 'cheque_cadeau') {
                if (!isset($salepoint['Salepoint']['mnt_chequecad'])) {
                    //$salepoint['Salepoint']['total_apres_reduction']
                    $this->Session->setFlash('aucun cheque cadeau n\'a été activé', 'alert-danger');

                    return $this->redirect($this->referer());
                } elseif ($this->request->data['Salepoint']['payment'] == 'cheque_cadeau' and $this->request->data['Salepoint']['payment2'] != 'cheque_cadeau') {
                    if (isset($salepoint['Salepoint']['mnt_chequecad']) and $salepoint['Salepoint']['mnt_chequecad'] > $this->request->data['Avance'][0]['montant']) {
                        $this->Session->setFlash('le montant du cheque cadeau est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                } elseif ($this->request->data['Salepoint']['payment'] != 'cheque_cadeau' and $this->request->data['Salepoint']['payment2'] == 'cheque_cadeau') {
                    if (isset($salepoint['Salepoint']['mnt_chequecad']) and $salepoint['Salepoint']['mnt_chequecad'] > $this->request->data['Avance'][1]['montant']) {
                        $this->Session->setFlash('le montant du cheque cadeau est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                } elseif ($this->request->data['Salepoint']['payment'] == 'cheque_cadeau' and $this->request->data['Salepoint']['payment2'] == 'cheque_cadeau') {
                    if (isset($salepoint['Salepoint']['mnt_chequecad']) and $salepoint['Salepoint']['mnt_chequecad'] > ($this->request->data['Avance'][0]['montant'] + $this->request->data['Avance'][1]['montant'])) {
                        $this->Session->setFlash('le montant du cheque cadeau est superieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    }
                }
            }

            //wallet
            if ($this->request->data['Salepoint']['payment'] == 'wallet' or $this->request->data['Salepoint']['payment2'] == 'wallet') {
                if (isset($salepoint['Salepoint']['client_id']) and $salepoint['Salepoint']['client_id'] == 1) {
                    $this->Session->setFlash('aucun client n\'a été choisi', 'alert-danger');

                    return $this->redirect($this->referer());
                }
                $this->loadModel('Wallet');
                $wallet = $this->Wallet->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id']]]);
                if (!isset($wallet['Wallet']['montant'])) {
                    $this->Session->setFlash('aucune wallet n\'existe pour ce client', 'alert-danger');

                    return $this->redirect($this->referer());
                }
                if ($wallet['Wallet']['montant'] == 0) {
                    $this->Session->setFlash('Le solde de la wallet est egale a 0 !', 'alert-danger');

                    return $this->redirect($this->referer());
                }
                $mnt_ticket = 0;
                if ($this->request->data['Salepoint']['payment'] == 'wallet' and $this->request->data['Salepoint']['payment2'] != 'wallet') {
                    $mnt_ticket += $this->request->data['Avance'][0]['montant'];
                } elseif (($this->request->data['Salepoint']['payment'] != 'wallet' and $this->request->data['Salepoint']['payment2'] == 'wallet')) {
                    $mnt_ticket += $this->request->data['Avance'][1]['montant'];
                } elseif (($this->request->data['Salepoint']['payment'] == 'wallet' and $this->request->data['Salepoint']['payment2'] == 'wallet')) {
                    $mnt_ticket += $this->request->data['Avance'][0]['montant'] + $this->request->data['Avance'][1]['montant'];
                }
                //var_dump($net_a_payer);
                $this->loadModel('Wallet');
                $wallet = $this->Wallet->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id']]]);
                if ($wallet['Wallet']['montant'] < $mnt_ticket) {
                    $this->Session->setFlash('votre montant wallet est inferieur au montant du ticket', 'alert-danger');

                    return $this->redirect($this->referer());
                }
            }


            // E-Commerce et change stat de la commande
            if (isset($salepoint['Salepoint']['ecommerce_id']) and !empty($salepoint['Salepoint']['ecommerce_id'])) {
                //debug( $salepoint['Salepoint']['total_cmd'] );
                // debug( $salepoint['Salepoint']['total_apres_reduction'] );

                // $ecommerce_id = $salepoint['Salepoint']['ecommerce_id'];

                // var_dump($ecommerce_id);

                // Change le statut de la commande e-commerce
                //App::import('Controller', 'Ecommerces'); // nom du fichier sans "Controller"
                //$Ecommerces = new EcommercesController(); // nom exact de la classe
                //$Ecommerces->constructClasses(); // charge les modèles
                // $Ecommerces->changeStatus($ecommerce_id, 'ready_for_delivery');


                


                $shipment = $salepoint['Salepoint']['expedition'];
                $ecommerceId = $salepoint['Salepoint']['ecommerce_id'];

                // à la base de ecommerceId recuperer l'orderId = online_id depuis la table ecommerces
                $ecommerce = $this->Ecommerce->find('first', ['conditions' => ['Ecommerce.id' => $ecommerceId]]);
                $orderId = $ecommerce['Ecommerce']['online_id'];

                // 1) Instanciation du contrôleur une seule fois

                if ($shipment == 'delivery') {
                    App::uses('EcommercesController', 'Controller');
                    $Ecommerces = new EcommercesController();
                    $Ecommerces->constructClasses();
                    $Ecommerces->changeStatus($orderId, 'ready_for_delivery');
                    $this->Session->setFlash('La commande a été mise à jour avec succès / ready_for_delivery', 'alert-success');
                    
                } elseif ($shipment == 'self') {
                    App::uses('EcommercesController', 'Controller');
                    $Ecommerces = new EcommercesController();
                    $Ecommerces->constructClasses();
                    $Ecommerces->changeStatus($orderId, 'ready_for_delivery');
                    // $Ecommerces->changeStatus($orderId, 'assigned_to_delivery_person');
                    $this->Session->setFlash('La commande a été mise à jour avec succès / assigned_to_delivery_person', 'alert-success');
                }


            }

            $avances = $this->Salepoint->Avance->find('list', ['fields' => ['id', 'id'], 'conditions' => ['salepoint_id' => $salepoint_id]]);
            if (!empty($avances)) {
                $this->Salepoint->Avance->deleteAll(['Avance.salepoint_id' => $salepoint_id]);
            }
            /* if ( isset( $this->request->data['Avance'] ) AND !empty( $this->request->data['Avance'] ) ) {
                foreach ($this->request->data['Avance'] as $k => $v) {
                    if( isset( $v['montant'] ) AND $v['montant'] == 0 ) unset( $this->request->data['Avance'][$k] );
                }
            } */

            $this->request->data['Avance'][0]['mode'] = $this->request->data['Salepoint']['payment'];
            $this->request->data['Avance'][1]['mode'] = $this->request->data['Salepoint']['payment2'];
            //var_dump($this->request->data["Avance"][0]["mode"]);

            $this->request->data['Salepoint']['etat'] = 2;
            $this->request->data['Salepoint']['paye'] = 2; // la colonne paye = Payé
            if ($this->request->data['Avance'][0]['montant'] == 0 and $this->request->data['Avance'][1]['montant'] != 0) {
                $this->request->data['Salepoint']['payment_method'] = $this->request->data['Salepoint']['payment2'];
            } elseif ($this->request->data['Avance'][1]['montant'] == 0 and $this->request->data['Avance'][0]['montant'] != 0) {
                $this->request->data['Salepoint']['payment_method'] = $this->request->data['Salepoint']['payment'];
            } elseif ($this->request->data['Avance'][0]['montant'] != 0 and $this->request->data['Avance'][1]['montant'] != 0) {
                $this->request->data['Salepoint']['payment_method'] = ($this->request->data['Salepoint']['payment'] != $this->request->data['Salepoint']['payment2']) ? $this->request->data['Salepoint']['payment'].','.$this->request->data['Salepoint']['payment2'] : $this->request->data['Salepoint']['payment'];
            } elseif ($this->request->data['Avance'][0]['montant'] == 0 and $this->request->data['Avance'][1]['montant'] == 0) {
                $this->request->data['Salepoint']['payment_method'] = '';
            }

            //add
            $this->request->data['Salepoint']['store'] = $this->Session->read('Auth.User.StoreSession.id');
            
            ///modif
            //var_dump($this->request->data["Salepoint"]["payment"]);die();
            $this->Session->write('Vente.paiement', 'ok');
            $caisse_id = $this->Session->read('caisse_id');
            $options = ['conditions' => ['id' => $caisse_id]];
            $this->loadModel('Caisse');
            $caisse = $this->Caisse->find('first', $options);
            $montant = $caisse['Caisse']['montant'];
            //var_dump(number_format($salepoint['Salepoint']['total_apres_reduction'] + $salepoint['Salepoint']['fee'], 2, ',', ' '));die();
            //$montant = $montant + number_format($salepoint['Salepoint']['total_apres_reduction'] + $salepoint['Salepoint']['fee'], 2, ',', ' ');
            $pourcentage = $caisse['Caisse']['pourcentage'] / 100;
            $montant = $montant + (($salepoint['Salepoint']['total_apres_reduction'] + $salepoint['Salepoint']['fee']) * $pourcentage);
            $this->Caisse->id = $caisse_id;
            $this->Caisse->saveField('montant', $montant);
            //$ecommerce = $this->Ecommerce->find("first",["conditions" => ["id" => $salepoint['Salepoint']['ecommerce_id']]]);

            // Get boucher info by ID
            $boucher_ID = $this->request->data['Salepoint']['boucher'];
            $get_boucher = $this->User->find('first', ['conditions' => ['code_bouchier' => $boucher_ID, 'code_bouchier !=' => 0, 'deleted' => 0]]);
            $nom = $get_boucher['User']['nom'];
            $prenom = $get_boucher['User']['prenom'];
            $full_name_boucher = $nom.' '.$prenom;

            $this->request->data['Salepoint']['boucher'] = $full_name_boucher;

            $net_a_payer = $salepoint['Salepoint']['total_apres_reduction'] + $salepoint['Salepoint']['fee'];
            if ($this->request->data['Salepoint']['payment'] == 'espece' and $this->request->data['Salepoint']['payment2'] == 'espece' and $net_a_payer < $caisse['Caisse']['montant']) {
                $caisse = $this->Caisse->find('first', $options);
                $montant = $caisse['Caisse']['montant'] - $net_a_payer;
                $this->Caisse->id = $caisse_id;
                $this->Caisse->saveField('montant', $montant);
                //T09
                $this->Session->write('Vente.T09', '1');
            }
            if (empty($salepoint['Salepoint']['ecommerce_id']) and empty($salepoint['Salepoint']['commande_id'])) {
                $client = $this->Ecommerce->Client->find('first', ['conditions' => ['Client.id' => '1']]);
                if (!isset($salepoint['Salepoint']['client_id'])) {
                    $this->request->data['Salepoint']['client_id'] = $client['Client']['id'];
                }
            }

            $this->Session->write('Pos.save', '1');
            $depots = $this->Session->read('depots');
            $depots = $this->Salepoint->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);

            $first_key = key($depots);
            $this->request->data['Salepoint']['depot_id'] = $first_key;
            //var_dump($this->request->data['Salepoint']['total_apres_reduction']);die();

            if ($this->Salepoint->saveAssociated($this->request->data)) {
                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                if (isset($salepoint['Salepoint']['commande_id']) and !empty($salepoint['Salepoint']['commande_id'])) {
                    $commandedetails = [];
                    foreach ($details as $k => $v) {
                        $commandedetails[$k]['Commandedetail']['montant_remise'] = $v['Salepointdetail']['montant_remise'];
                        $commandedetails[$k]['Commandedetail']['id'] = $v['Salepointdetail']['commandedetail_id'];
                        $commandedetails[$k]['Commandedetail']['remise'] = $v['Salepointdetail']['remise'];
                        $commandedetails[$k]['Commandedetail']['qte'] = $v['Salepointdetail']['qte'];
                    }
                    $this->Commande->id = $salepoint['Salepoint']['commande_id'];
                    if ($this->Commande->saveField('etat', 2) and !empty($commandedetails)) {
                        $this->Commande->Commandedetail->saveMany($commandedetails);
                    }
                }
                if (isset($salepoint['Salepoint']['ecommerce_id']) and !empty($salepoint['Salepoint']['ecommerce_id'])) {     
                    $saveEcommerce['Ecommercedetail'] = [];
                    $saveEcommerce['Ecommerce']['etat'] = 2;
                    $saveEcommerce['Ecommerce']['id'] = $salepoint['Salepoint']['ecommerce_id'];
                    $saveEcommerce['Ecommerce']['total_apres_reduction'] = $salepoint['Salepoint']['total_apres_reduction'];
                    $saveEcommerce['Ecommerce']['total_a_payer_ttc'] = $salepoint['Salepoint']['total_a_payer_ttc'];
                    $saveEcommerce['Ecommerce']['total_a_payer_ht'] = $salepoint['Salepoint']['total_a_payer_ht'];
                    $saveEcommerce['Ecommerce']['reste_a_payer'] = $salepoint['Salepoint']['reste_a_payer'];
                    $saveEcommerce['Ecommerce']['total_paye'] = $salepoint['Salepoint']['total_paye'];
                    $saveEcommerce['Ecommerce']['store_id'] = $this->Session->read('Auth.User.StoreSession.id');
                    foreach ($details as $k => $v) {
                        $saveEcommerce['Ecommercedetail'][] = [
                            'id' => $v['Salepointdetail']['ecommercedetail_id'],
                            'prix_vente' => $v['Salepointdetail']['prix_vente'],
                            'total' => $v['Salepointdetail']['total'],
                            'ttc' => $v['Salepointdetail']['ttc'],
                            'qte' => $v['Salepointdetail']['qte'],
                        ];
                    }
                    $this->Ecommerce->saveAssociated($saveEcommerce);
                }

                if (isset($salepoint['Salepoint']['glovo_id']) and !empty($salepoint['Salepoint']['glovo_id'])) {
                    $saveCommandeglovo['Commandeglovodetail'] = [];
                    $saveCommandeglovo['Commandeglovo']['etat'] = 2;
                    $saveCommandeglovo['Commandeglovo']['id'] = $salepoint['Salepoint']['glovo_id'];
                    $saveCommandeglovo['Commandeglovo']['total_apres_reduction'] = $salepoint['Salepoint']['total_apres_reduction'];
                    $saveCommandeglovo['Commandeglovo']['total_a_payer_ttc'] = $salepoint['Salepoint']['total_a_payer_ttc'];
                    $saveCommandeglovo['Commandeglovo']['total_a_payer_ht'] = $salepoint['Salepoint']['total_a_payer_ht'];
                    $saveCommandeglovo['Commandeglovo']['reste_a_payer'] = $salepoint['Salepoint']['reste_a_payer'];
                    $saveCommandeglovo['Commandeglovo']['total_paye'] = $salepoint['Salepoint']['total_paye'];
                    /* $saveCommandeglovo['Commandeglovo']['date'] = $salepoint['Salepoint']['date_u']; */
                    /* $saveCommandeglovo['Commandeglovo']['store_id'] = $this->Session->read('Auth.User.StoreSession.id'); */
                    foreach ($details as $k => $v) {
                        $saveCommandeglovo['Commandeglovodetail'][] = [
                            'id' => $v['Salepointdetail']['glovodetail_id'],
                            'prix_vente' => $v['Salepointdetail']['prix_vente'],
                            'total' => $v['Salepointdetail']['total'],
                            'ttc' => $v['Salepointdetail']['ttc'],
                            'qte' => $v['Salepointdetail']['qte'],
                        ];
                    }
                    $this->Commandeglovo->saveAssociated($saveCommandeglovo);

                    //Api glovo
                  /*   App::import('Controller', 'Commandeglovos');
                    $Commandeglovos = new CommandeglovosController();
                    $Commandeglovos->Apiglovo($salepoint['Salepoint']['glovo_id'], 'ACCEPTED'); */
                }
                $remise_check = 0;
                foreach ($details as $k => $v) {
                    if (isset($v['Salepointdetail']['remise'])) {
                        $remise_check = 1;
                    }
                }
                if ($remise_check == 0) {
                    $this->CheckRemise($salepoint_id);
                }
                $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                $net_a_payer = 0;
                $modes = ['bon_achat', 'cheque_cadeau', 'wallet'];
                if (!in_array($this->request->data['Avance'][0]['mode'], $modes) and !in_array($this->request->data['Avance'][1]['mode'], $modes)) {
                    /* foreach ($details as $detail) {
                        $net_a_payer += $this->RemiseClient($salepoint['Salepoint']['client_id'], $detail['Salepointdetail']['ttc'], $detail['Salepointdetail']['produit_id']);
                    } */
                    foreach ($details as $detail) {
                        $net_a_payer += $detail['Salepointdetail']['ttc'];
                    }
                } else {
                    foreach ($details as $detail) {
                        $net_a_payer += $detail['Salepointdetail']['ttc'];
                    }
                }
                $this->Salepoint->id = $salepoint['Salepoint']['id'];
                $this->Salepoint->saveField('total_paye', $net_a_payer);

                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                $this->Salepoint->id = $salepoint['Salepoint']['id'];

                $this->Session->delete('Vente.T09');
                $this->Session->delete('Vente.paiement');
                if ($this->request->data['Avance'][0]['mode'] == 'bon_achat' or $this->request->data['Avance'][1]['mode'] == 'bon_achat') {
                    $this->loadModel('Bonachat');
                    $bonachats = $this->Bonachat->find('first', [
                        'conditions' => ['id' => $salepoint['Salepoint']['id_bon_achat']], ]
                    );
                    if ($bonachats['Bonachat']['active'] == 0) {
                        $this->Bonachat->id = $salepoint['Salepoint']['id_bon_achat'];
                        $this->Bonachat->saveField('active', 1);
                        $this->Bonachat->id = $salepoint['Salepoint']['id_bon_achat'];
                        $this->Bonachat->saveField('ref_ticket', $salepoint['Salepoint']['reference']);
                    }
                    $this->Bonachat->id = $salepoint['Salepoint']['id_bon_achat'];
                    $this->Bonachat->saveField('date_encaissement', date('Y-m-d H:i:s'));
                    $montant1 = ($this->request->data['Avance'][0]['mode'] == 'bon_achat') ? $this->request->data['Avance'][0]['montant'] : 0;
                    $montant2 = ($this->request->data['Avance'][1]['mode'] == 'bon_achat') ? $this->request->data['Avance'][1]['montant'] : 0;
                    $montant_bon_achat = $montant1 + $montant2;
                    if (isset($salepoint['Salepoint']['mnt_bonachat']) and $salepoint['Salepoint']['mnt_bonachat'] <= $montant_bon_achat) {
                        //$this->Salepoint->id = $salepoint_id;
                        $net_a_payer = $net_a_payer - $montant_bon_achat;
                        //$this->Salepoint->saveField('total_paye', $net_a_payer);
                        $this->Salepoint->id = $salepoint_id;
                        $this->Salepoint->saveField('check_mode', 1);
                        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                    }
                }
                if ($this->request->data['Avance'][0]['mode'] == 'cheque_cadeau' or $this->request->data['Avance'][1]['mode'] == 'cheque_cadeau') {
                    $this->loadModel('Chequecadeau');
                    $chequecadeau = $this->Chequecadeau->find('first', [
                        'conditions' => ['id' => $salepoint['Salepoint']['id_cheque_cad']], ]
                    );
                    if ($chequecadeau['Chequecadeau']['active'] == 0) {
                        $this->Chequecadeau->id = $salepoint['Salepoint']['id_cheque_cad'];
                        $this->Chequecadeau->saveField('active', 1);
                        $this->Chequecadeau->id = $salepoint['Salepoint']['id_cheque_cad'];
                        $this->Chequecadeau->saveField('ref_ticket', $salepoint['Salepoint']['reference']);
                    }
                    $this->Chequecadeau->id = $salepoint['Salepoint']['id_cheque_cad'];
                    $this->Chequecadeau->saveField('date_encaissement', date('Y-m-d H:i:s'));

                    $montant1 = ($this->request->data['Avance'][0]['mode'] == 'cheque_cadeau') ? $this->request->data['Avance'][0]['montant'] : 0;
                    $montant2 = ($this->request->data['Avance'][1]['mode'] == 'cheque_cadeau') ? $this->request->data['Avance'][1]['montant'] : 0;
                    $montant_bon_achat = $montant1 + $montant2;

                    if (isset($salepoint['Salepoint']['mnt_chequecad']) and $salepoint['Salepoint']['mnt_chequecad'] <= $montant_bon_achat) {
                        $this->Salepoint->id = $salepoint_id;
                        $net_a_payer = $net_a_payer - $montant_bon_achat;
                        // $this->Salepoint->saveField('total_paye', $net_a_payer);
                        $this->Salepoint->id = $salepoint_id;
                        $this->Salepoint->saveField('check_mode', 1);
                        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                    }
                }
                if ($this->request->data['Avance'][0]['mode'] == 'wallet' or $this->request->data['Avance'][1]['mode'] == 'wallet') {
                    $mnt_ticket = 0;
                    if ($this->request->data['Avance'][0]['mode'] == 'wallet' and $this->request->data['Avance'][1]['mode'] != 'wallet') {
                        $mnt_ticket += $this->request->data['Avance'][0]['montant'];
                    } elseif (($this->request->data['Avance'][0]['mode'] != 'wallet' and $this->request->data['Avance'][1]['mode'] == 'wallet')) {
                        $mnt_ticket += $this->request->data['Avance'][1]['montant'];
                    } elseif (($this->request->data['Avance'][0]['mode'] == 'wallet' and $this->request->data['Avance'][1]['mode'] == 'wallet')) {
                        $mnt_ticket += $this->request->data['Avance'][0]['montant'] + $this->request->data['Avance'][1]['montant'];
                    }
                    //var_dump($net_a_payer);
                    /* $this->loadModel('Wallet');
                    $wallet = $this->Wallet->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id']]]);
                    if ($wallet['Wallet']['montant'] < $mnt_ticket) {
                        $this->Session->setFlash('votre montant wallet est inferieur au montant du ticket', 'alert-danger');

                        return $this->redirect($this->referer());
                    } */
                    $nv_montant = $wallet['Wallet']['montant'] - $mnt_ticket;
                    $this->Wallet->id = $wallet['Wallet']['id'];
                    $this->Wallet->saveField('montant', $nv_montant);
                    //historique wallet
                    $this->loadModel('Walletdetail');
                    $data = [
                        'wallet_id' => $wallet['Wallet']['id'],
                        'ref_ticket' => $salepoint['Salepoint']['reference'],
                        'montant' => $mnt_ticket,
                    ];
                    $this->Walletdetail->save($data);
                    $net_a_payer = $net_a_payer - $mnt_ticket;
                    //var_dump($net_a_payer);die();
                    /* $this->Salepoint->id = $salepoint_id;
                    $this->Salepoint->saveField('total_paye', $net_a_payer);
                     */$this->Salepoint->id = $salepoint_id;
                    $this->Salepoint->saveField('check_mode', 1);
                }

                //Remise sur montant ticket
                $modes = ['bon_achat', 'cheque_cadeau', 'wallet'];
                if (!in_array($this->request->data['Avance'][0]['mode'], $modes) and !in_array($this->request->data['Avance'][1]['mode'], $modes)) {
                    //Fidelite
                    if (!isset($salepoint['Salepoint']['remise'])) {
                        if (isset($salepoint['Salepoint']['client_id']) and $salepoint['Salepoint']['client_id'] != 1) {
                            $this->loadModel('Client');
                            $client = $this->Client->find('first', ['conditions' => ['id' => $salepoint['Salepoint']['client_id']]]);
                            $total = $client['Client']['points_fidelite'] + $salepoint['Salepoint']['points_fidelite'];
                            $this->loadModel('Fidelite');
                            $fidelite = $this->Fidelite->find('first');
                            if (isset($fidelite['Fidelite']['montant_cheque_cad'])) {
                                $montant_cheque = $fidelite['Fidelite']['montant_cheque_cad'];
                                if ($total > $montant_cheque) {
                                    $montant_cheque_gen = $total / $montant_cheque;
                                    $montant_cheque_gen = floor($montant_cheque_gen) * $montant_cheque;

                                    $reste_fidelite = $total - $montant_cheque_gen;

                                    $this->Client->id = $salepoint['Salepoint']['client_id'];
                                    $this->Client->saveField('points_fidelite', $reste_fidelite);

                                    $this->Salepoint->id = $salepoint_id;
                                    $this->Salepoint->saveField('check_cad', 1);
                                    /* $this->Salepoint->id = $salepoint_id;
                                    $this->Salepoint->saveField('total_paye', 0);
*/
                                    //creer cheque cadeau
                                    $data = [
                                    'date_encaissement' => date('Y-m-d H:i:s'),
                                   /*  'montant' => $net_a_payer, */
                                    'montant' => $montant_cheque_gen,
                                    'active' => 1,
                                    'client_id' => $salepoint['Salepoint']['client_id'],
                                    'ref_ticket' => $salepoint['Salepoint']['reference'],
                                ];
                                    $this->loadModel('Chequecadeau');
                                    $this->Chequecadeau->save($data);
                                    $this->Salepoint->id = $salepoint_id;
                                    $this->Salepoint->saveField('id_chequecadeau', $this->Chequecadeau->id);
                                    $avances = $this->Salepoint->Avance->find('list', ['fields' => ['id', 'id'], 'conditions' => ['salepoint_id' => $salepoint_id]]);

                                    /* $this->Salepoint->Avance->id = reset($avances);
                                    $this->Salepoint->Avance->saveField('mode', 'cheque_cadeau'); */
                                    $salepoint = $this->Salepoint->find('first', ['contain' => ['Chequecadeau'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
                                } else {
                                    $this->Client->id = $salepoint['Salepoint']['client_id'];
                                    $this->Client->saveField('points_fidelite', $total);
                                }
                            }
                        }
                    } else {
                        $remise_globale = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id'], 'type' => 'globale']]);

                        if (isset($remise_globale['Remiseclient']['id'])) {
                            if ($net_a_payer >= $remise_globale['Remiseclient']['montant_ticket']) {
                                $pourcentage = $remise_globale['Remiseclient']['montant'];
                                $pourcentage /= 100;
                                $montantR = $salepoint['Salepoint']['total_a_payer_ttc'] * $pourcentage;
                                $net_a_payer = $salepoint['Salepoint']['total_a_payer_ttc'] - $montantR;
                                $this->Salepoint->id = $salepoint['Salepoint']['id'];
                                $this->Salepoint->saveField('total_paye', $net_a_payer);

                                /* $this->loadModel('Avance');
                                $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);
                                $this->Avance->id = $avances[0]['Avance']['id'];
                                $this->Avance->saveField('montant', $net_a_payer);
                                $this->Avance->id = $avances[1]['Avance']['id'];
                                $this->Avance->save('montant', 0); */
                                //$this->calculeRemiseGlob($salepoint_id, $remise_globale['Remiseclient']['montant'], $montantR, $net_a_payer);
                            }
                        }
                    }
                }

                
                //calcul reste a payer
                $avances = $this->Salepoint->Avance->find('all', [
                    'conditions' => ['Avance.salepoint_id' => $salepoint_id],
                    'order' => ['date ASC'],
                ]);
                $total_paye = 0;
                foreach ($avances as $avance) {
                    $total_paye += $avance['Avance']['montant'];
                }

                /* if (!isset($remise_globale['Remiseclient']['id']) or (isset($remise_globale['Remiseclient']['id']) and $net_a_payer < $remise_globale['Remiseclient']['montant_ticket'])) {
                */     //$reste_a_payer = $salepoint['Salepoint']['total_paye'] + $salepoint['Salepoint']['fee'] - $total_paye;
                $reste_a_payer = $this->data['Salepoint']['total_apres_reduction'] - $this->data['Salepoint']['montant_remise'] - $total_paye;
                $this->Salepoint->id = $salepoint_id;
                $this->Salepoint->saveField('reste_a_payer', $reste_a_payer);
                /*  } */
                //$this->calcule($salepoint['Salepoint']['id']);
                //sortie de la quantité 

// Garder la traçabilité - t_mouvements t_mouvementprincipals t_entrees
                App::import('Controller', 'Bonretourachats');
                $Bonretour = new BonretourachatsController();

                $this->loadModel('Entree');

                foreach ($details as $key => $value) {
                   // $Bonretour->sortie($value['Salepointdetail']['produit_id'], $salepoint['Salepoint']['depot_id'], $value['Salepointdetail']['qte'], $value['Salepointdetail']['qte'], 0);

                    $produit_id = $value['Salepointdetail']['produit_id'];
                    $depot_id = $salepoint['Salepoint']['depot_id'];
                    $paquet_sortie = $value['Salepointdetail']['qte'];

                $Bonretour->sortie($produit_id, $depot_id, $paquet_sortie, $paquet_sortie, 0);

                //    $donnees['Entree'] = [
                //    'quantite' => $paquet_sortie,
                //    'depot_id' => $depot_id,
                //    'produit_id' => $produit_id,
                //    'type' => 'Sortie',
                //    ];
                    // $this->Entree->saveMany($donnees);

                }



                return $this->redirect(['action' => 'index', 0]);
            } else {
                $this->Session->setFlash('Il y a un problème', 'alert-danger');

                return $this->redirect($this->referer());
            }
        } elseif ($this->Salepoint->exists($salepoint_id)) {
            $this->request->data = $this->Salepoint->find('first', ['contain' => ['Ecommerce', 'Commandeglovo'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
            $bon_achat = false;
            $cheque_cad = false;
            if (isset($salepoint['Salepoint']['mnt_bonachat']) and $salepoint['Salepoint']['mnt_bonachat'] <= $salepoint['Salepoint']['total_apres_reduction']) {
                $this->request->data['Avance'][0]['montant'] = $salepoint['Salepoint']['mnt_bonachat'];
                $bon_achat = true;
                $this->request->data['Avance'][1]['montant'] = $salepoint['Salepoint']['total_apres_reduction'] - $salepoint['Salepoint']['mnt_bonachat'];
            } elseif (isset($salepoint['Salepoint']['mnt_chequecad']) and $salepoint['Salepoint']['mnt_chequecad'] <= $salepoint['Salepoint']['total_apres_reduction']) {
                $this->request->data['Avance'][0]['montant'] = $salepoint['Salepoint']['mnt_chequecad'];
                $cheque_cad = true;
                $this->request->data['Avance'][1]['montant'] = $salepoint['Salepoint']['total_apres_reduction'] - $salepoint['Salepoint']['mnt_chequecad'];
            }
        }

        $this->set(compact('total', 'bon_achat', 'cheque_cad', 'error'));
        $this->layout = false;
    }

    public function calculeRemiseGlob($salepoint_id, $remise, $montantR, $net_a_payer)
    {
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $total = 0;

        $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);
        $payement = $avances[0]['Avance']['montant'] + $avances[1]['Avance']['montant'];
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['remise'] = $remise;
        $data['Salepoint']['montant_remise'] = $montantR;
        $data['Salepoint']['reste_a_payer'] = $net_a_payer - $payement;

        if ($this->Salepoint->save($data)) {
            return true;
        }
    }

    public function loadWallet($salepoint_id)
    {
        $salepoint = $this->Salepoint->find('first', ['contain' => ['Ecommerce'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        $wallet_ok = false;
        $this->loadModel('Wallet');
        $wallet = $this->Wallet->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id']]]);
        $mnt1 = 0;
        $mnt2 = 0;
        if (isset($wallet['Wallet']['id'])) {
            $wallet_ok = true;
            if ($wallet['Wallet']['montant'] < $salepoint['Salepoint']['total_apres_reduction']) {
                $mnt1 = $wallet['Wallet']['montant'];
                $mnt2 = $salepoint['Salepoint']['total_apres_reduction'] - $mnt1;
            }
        }

        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode(['wallet' => $wallet_ok, 'mnt1' => $mnt1, 'mnt2' => $mnt2]));
    }

    public function checkQuantity($salepoint_id)
    {
        $details = $this->Salepoint->Salepointdetail->find('all', ['conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $response['error'] = false;
        foreach ($details as $detail) {
            if ($detail['Salepointdetail']['qte'] == 0) {
                $response['error'] = true;
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function updatelineCommande($salepoint_id, $salepointdetail_id, $code_barre)
    {
        $response['error'] = true;
        $response['message'] = '';
        $longeur = strlen($code_barre);
        if ($longeur != 13) {
            $response['message'] = 'Code a barre est incorrect , produit introuvable !';
        } else {
            $params = $this->Parametreste->findList();
            $cb_identifiant = (isset($params['cb_identifiant']) and !empty($params['cb_identifiant'])) ? $params['cb_identifiant'] : '2900';
            $cb_produit_depart = (isset($params['cb_produit_depart']) and !empty($params['cb_produit_depart'])) ? $params['cb_produit_depart'] : 4;
            $cb_produit_longeur = (isset($params['cb_produit_longeur']) and !empty($params['cb_produit_longeur'])) ? $params['cb_produit_longeur'] : 3;
            $cb_quantite_depart = (isset($params['cb_quantite_depart']) and !empty($params['cb_quantite_depart'])) ? $params['cb_quantite_depart'] : 7;
            $cb_quantite_longeur = (isset($params['cb_quantite_longeur']) and !empty($params['cb_quantite_longeur'])) ? $params['cb_quantite_longeur'] : 5;
            $cb_div_kg = (isset($params['cb_div_kg']) and !empty($params['cb_div_kg']) and $params['cb_div_kg'] > 0) ? (int) $params['cb_div_kg'] : 1000;
            $identifiant = substr(trim($code_barre), 0, 4);
            if ($cb_identifiant != $identifiant) {
                $response['message'] = "Identifiant du code à barre est incorrect , veuillez vérifier votre paramétrage d'application !";
            } else {
                $code_article = substr(trim($code_barre), $cb_produit_depart, $cb_produit_longeur);
                $quantite = substr(trim($code_barre), $cb_quantite_depart, $cb_quantite_longeur);

                if (!empty($code_article)) {
                    $produit = $this->Salepoint->Salepointdetail->find('first', [
                        'conditions' => [
                            'Salepointdetail.id' => $salepointdetail_id,
                            'Salepointdetail.salepoint_id' => $salepoint_id,
                        ],
                        'fields' => ['Salepointdetail.*', 'Salepointdetail.id', 'Salepointdetail.qte', 'Salepoint.glovo_id', 'Salepointdetail.qte_cmd', 'Produit.id', 'Produit.code_barre', 'Produit.prix_vente', 'Produit.unite_id', 'Produit.tva_vente', 'Produit.pese'],
                        'contain' => ['Produit', 'Salepoint'],
                    ]);

                    if (isset($produit['Salepointdetail']['id']) and !empty($produit['Salepointdetail']['id'])) {
                        /*     if ( isset( $produit['Produit']['pese'] ) AND $produit['Produit']['unite_id'] == 4 )  $qte = (int) $quantite;  */// piéce
                        if (isset($produit['Produit']['pese']) and $produit['Produit']['pese'] == '1') {
                            $qte = $quantite / $cb_div_kg; // autre
                        } else {
                            $qte = (int) $quantite;
                            $pese = 0;
                        }

                        /* if(isset($pese)) {
                            if(strlen((string) $qte) == 2) {
                                $qte = $qte/10;
                            }
                            else if(strlen((string) $qte) == 3) {
                                $qte = $qte/((int) 100);
                            }
                            else if(strlen((string) $qte) == 4) {
                                $qte = $qte/1000;
                            }

                        } */
                        $qte_old = (!empty($produit['Salepointdetail']['qte'])) ? $produit['Salepointdetail']['qte'] : 0;
                        /* $qte = (int) $quantite;
                        $qte = $qte/1000; */

                        $qte = $qte_old + $qte;

                        if ($qte <= 0) {
                            $response['message'] = 'Opération impossible la quantité doit étre supérieur a zéro !';
                        } else {
                            $qte_cmd = (isset($produit['Salepointdetail']['qte_cmd'])) ? $produit['Salepointdetail']['qte_cmd'] : 0;
                            if ($qte > $qte_cmd) {
                                $response['message'] = 'la quantité livrée doit étre inferieure ou égale a la quantité commandée !';
                            } else {
                                $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                                $division_tva = (1 + $tva / 100);

                                //modif
                                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                                $ecommerce_id = $salepoint['Salepoint']['ecommerce_id'];

                                $prix_vente_ht = round($produit['Produit']['prix_vente'] / $division_tva, 2);
                                $prix_vente_ttc = $produit['Produit']['prix_vente'];

                                $total_ht = round($prix_vente_ht * $qte, 2);
                                $total_ttc = round($prix_vente_ttc * $qte, 2);

                                if (isset($produit['Salepoint']['glovo_id'])) {
                                    $total_ht = round($produit['Salepointdetail']['prix_vente'] * $qte / $division_tva, 2);
                                    $total_ttc = round($produit['Salepointdetail']['prix_vente'] * $qte, 2);
                                }
                                /* $prix_vente_ht = round( $ecommerce_d["Ecommercedetail"]["prix_vente"]/$division_tva,2 );
                                $prix_vente_ttc = $ecommerce_d["Ecommercedetail"]["prix_vente"];
 */
                                $data['Salepointdetail']['id'] = $produit['Salepointdetail']['id'];
                                $data['Salepointdetail']['total'] = $total_ht;
                                $data['Salepointdetail']['ttc'] = $total_ttc;
                                $data['Salepointdetail']['qte'] = $qte;

                                if ($this->Salepoint->Salepointdetail->save($data)) {
                                    $this->calcule($salepoint_id);
                                    $response['message'] = "L'enregistrement a été fait avec succès. !";
                                    $response['error'] = false;
                                } else {
                                    $response['message'] = 'Erreur de sauvgarde des donnée !';
                                }
                            }
                        }
                    } else {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                    }
                } else {
                    $response['message'] = 'Code a barre incorrect ou vide !';
                }
            }
        }
        if ($this->notice == 1) {
            $response['notice'] = true;
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    // Scan ECOM
    public function updatelineDE($salepoint_id, $salepointdetail_id, $code_barre)
    {
        $response['error'] = true;
        $response['message'] = '';
        $longeur = strlen($code_barre);
        if ($longeur != 13) {
            $response['message'] = 'Code a barre est incorrect , produit introuvable !';
        } else {

            $params = $this->Parametreste->findList();
            $cb_div_kg = (isset($params['cb_div_kg']) and !empty($params['cb_div_kg']) and $params['cb_div_kg'] > 0) ? (int) $params['cb_div_kg'] : 1000;

            // Go to EAN13 Table
            $produit = $this->Produit->find('first', [
                'fields' => [
                    'Produit.id',
                    'Ean13Code.variante',
                    'Ean13Code.code_barre',
                    'Ean13Code.nom_produit' // ✅ Nom du produit depuis ean13_codes
                ],
                'conditions' => [
                    'Produit.type' => 2,
                    'Ean13Code.code_ean13' => $code_barre,
                ],
                'joins' => [
                    [
                        'table' => 'ean13_codes',
                        'alias' => 'Ean13Code',
                        'type' => 'INNER',
                        'conditions' => ['Ean13Code.produit_id = Produit.id']
                    ]
                ]
            ]);
        
            $code_article = $produit['Ean13Code']['code_barre'];
            $nom_produit_ean13  = $produit['Ean13Code']['nom_produit']; // ✅ Utilisation
            $quantite     = 1;

            // If code_article innexistant il faut afficher message d'erreur
            if (empty($code_article)) {
                $response['message'] = 'Code a barre innexistant !';
            }
          
                if (!empty($code_article)) {
                    $produit = $this->Salepoint->Salepointdetail->find('first', [
                        'conditions' => [
                            'Salepointdetail.id' => $salepointdetail_id,
                            'Salepointdetail.salepoint_id' => $salepoint_id,
                        ],
                        'fields' => ['Salepointdetail.id', 'Salepointdetail.qte', 'Salepointdetail.qte_cmd', 'Produit.id', 'Produit.code_barre', 'Produit.prix_vente', 'Produit.unite_id', 'Produit.tva_vente', 'Produit.pese'],
                        'contain' => ['Produit'],
                    ]);

                    if (isset($produit['Salepointdetail']['id']) and !empty($produit['Salepointdetail']['id'])) {
                        /*     if ( isset( $produit['Produit']['pese'] ) AND $produit['Produit']['unite_id'] == 4 )  $qte = (int) $quantite;  */// piéce
                        if (isset($produit['Produit']['pese']) and $produit['Produit']['pese'] == '1') {
                            $qte = $quantite / $cb_div_kg; // autre
                        } else {
                            $qte = (int) $quantite;
                            $pese = 0;
                        }

                        /* if(isset($pese)) {
                            if(strlen((string) $qte) == 2) {
                                $qte = $qte/10;
                            }
                            else if(strlen((string) $qte) == 3) {
                                $qte = $qte/((int) 100);
                            }
                            else if(strlen((string) $qte) == 4) {
                                $qte = $qte/1000;
                            }

                        } */
                        $qte_old = (!empty($produit['Salepointdetail']['qte'])) ? $produit['Salepointdetail']['qte'] : 0;
                        /* $qte = (int) $quantite;
                        $qte = $qte/1000; */

                        $qte = $qte_old + $qte;

                        if ($qte <= 0) {
                            $response['message'] = 'Opération impossible la quantité doit étre supérieur a zéro !';
                        } else {
                            $qte_cmd = (isset($produit['Salepointdetail']['qte_cmd'])) ? $produit['Salepointdetail']['qte_cmd'] : 0;
                            if ($qte > $qte_cmd) {
                                $response['message'] = 'la quantité livrée doit étre inferieure ou égale a la quantité commandée !';
                            } else {
                                $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                                $division_tva = (1 + $tva / 100);

                                //modif
                                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                                $ecommerce_id = $salepoint['Salepoint']['ecommerce_id'];

                                $ecommerce_d = $this->Ecommerce->Ecommercedetail->find('all', [
                                    'conditions' => [
                                        'Ecommercedetail.produit_id' => $produit['Produit']['id'],
                                        'Ecommercedetail.ecommerce_id' => $ecommerce_id,
                                    ],
                                    'fields' => ['Ecommercedetail.id', 'Ecommercedetail.prix_vente'],
                                ]);

                                /* $prix_vente_ht = round( $ecommerce_d["Ecommercedetail"]["prix_vente"]/$division_tva,2 );
                                $prix_vente_ttc = $ecommerce_d["Ecommercedetail"]["prix_vente"];
 */
                                $total_ht = round($ecommerce_d[0]['Ecommercedetail']['prix_vente'] * $qte / $division_tva, 2);
                                $total_ttc = round($ecommerce_d[0]['Ecommercedetail']['prix_vente'] * $qte, 2);


$prix_vente_ttc = $ecommerce_d[0]['Ecommercedetail']['prix_vente'];
$ttc_calculated = round($prix_vente_ttc * $qte, 2);
$ht_calculated  = round($prix_vente_ttc * $qte / $division_tva, 2);

$data['Salepointdetail']['id'] = $produit['Salepointdetail']['id'];
$data['Salepointdetail']['qte'] = $qte;
$data['Salepointdetail']['total'] = $ht_calculated;
$data['Salepointdetail']['ttc'] = $ttc_calculated;
$data['Salepointdetail']['nom_produit_ean13'] = $nom_produit_ean13; // le nom produit de la table EAN13 vers salespointdetail

                                //$data['Salepointdetail']['id'] = $produit['Salepointdetail']['id'];
                                //$data['Salepointdetail']['total'] = $total_ht;
                                //$data['Salepointdetail']['ttc'] = $total_ttc;
                                //$data['Salepointdetail']['qte'] = $qte;

                                // var_dump($data);die();

                                if ($this->Salepoint->Salepointdetail->save($data)) {
                                    $this->calcule($salepoint_id);
                                    $response['message'] = "L'enregistrement a été fait avec succès. !";
                                    $response['error'] = false;
                                } else {
                                    $response['message'] = 'Erreur de sauvgarde des donnée !';
                                }
                            }
                        }
                    } else {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                    }
                } else {
                    $response['message'] = 'Code a barre incorrect ou vide !';
                }
        }
        if ($this->notice == 1) {
            $response['notice'] = true;
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function updateline($code_barre = null, $salepoint_id = null)
    {

        $response['error'] = true;
        $response['message'] = '';
        $longeur = strlen($code_barre);
        if ($longeur != 13) {
            $response['message'] = 'Code a barre est incorrect , produit introuvable !';
        } else {

            $params = $this->Parametreste->findList();
            $cb_div_kg = (isset($params['cb_div_kg']) and !empty($params['cb_div_kg']) and $params['cb_div_kg'] > 0) ? (int) $params['cb_div_kg'] : 1000;

            // Go to EAN13 Table
            $produit = $this->Produit->find('first', [
                'fields' => [
                    'Produit.id',
                    'Ean13Code.variante',
                    'Ean13Code.code_barre',
                    'Ean13Code.nom_produit' // ✅ Nom du produit depuis ean13_codes
                ],
                'conditions' => [
                    'Produit.type' => 2,
                    'Ean13Code.code_ean13' => $code_barre,
                ],
                'joins' => [
                    [
                        'table' => 'ean13_codes',
                        'alias' => 'Ean13Code',
                        'type' => 'INNER',
                        'conditions' => ['Ean13Code.produit_id = Produit.id']
                    ]
                ]
            ]);
        
            $code_article = $produit['Ean13Code']['code_barre'];
            $nom_produit_ean13  = $produit['Ean13Code']['nom_produit']; // ✅ Utilisation
            $quantite     = 1;

            // If code_article innexistant il faut afficher message d'erreur
            if (    empty($code_article)) {
                $response['message'] = 'Code a barre innexistant !';
            }

                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                if (isset($salepoint['Salepoint']['ecommerce_id']) or isset($salepoint['Salepoint']['glovo_id'])) {
                    $produit = $this->Produit->find('first', ['fields' => ['id', 'prix_vente', 'pese', 'tva_vente'], 'conditions' => ['Produit.type' => 2, 'Produit.code_barre' => $code_article]]);

                    $salepoint_detail = $this->Salepoint->Salepointdetail->find('all', ['conditions' => ['Salepointdetail.produit_id' => $produit['Produit']['id'], 'Salepointdetail.salepoint_id' => $salepoint_id]]);
                    if (empty($salepoint_detail)) {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                        header('Content-Type: application/json; charset=UTF-8');
                        die(json_encode($response));
                    }
                }
                if (!empty($code_article)) {
                    $commandedetail = true;
                    $produit = $this->Salepoint->Salepointdetail->find('first', [
                        'conditions' => [
                            'Produit.code_barre' => $code_article,
                            'Salepointdetail.salepoint_id' => $salepoint_id,
                        ],
                        'fields' => ['Salepointdetail.*', 'Salepointdetail.id', 'Salepointdetail.qte', 'Salepoint.glovo_id', 'Salepointdetail.ecommercedetail_id', 'Salepointdetail.qte_cmd', 'Produit.id', 'Produit.code_barre', 'Produit.prix_vente', 'Produit.unite_id', 'Produit.tva_vente', 'Produit.pese'],
                        'contain' => ['Produit', 'Salepoint'],
                    ]);

                    if (empty($produit['Salepointdetail']['commandedetail_id']) and empty($produit['Salepointdetail']['ecommercedetail_id'])) {
                        $commandedetail = true;
                    }
                    if (isset($produit['Salepointdetail']['ecommercedetail_id']) and !empty($produit['Salepointdetail']['ecommercedetail_id'])) {
                        $ecommerced = true;
                    }
                    if (empty($produit)) {
                        $produit = $this->Salepoint->Salepointdetail->find('first', [
                            'conditions' => [
                                'Salepointdetail.salepoint_id' => $salepoint_id,
                            ],
                            'contain' => ['Salepoint'],
                            'fields' => ['Salepointdetail.*', 'Salepointdetail.id', 'Salepoint.glovo_id', 'Salepointdetail.qte', 'Salepointdetail.qte_cmd', 'Salepointdetail.ecommercedetail_id', 'Salepointdetail.commandedetail_id'],
                        ]);

                        if (isset($produit['Salepointdetail']['commandedetail_id']) and !empty($produit['Salepointdetail']['commandedetail_id'])) {
                            $produit = $this->Produit->find('first', ['fields' => ['id', 'prix_vente', 'pese', 'tva_vente'], 'conditions' => ['Produit.type' => 2, 'Produit.code_barre' => $code_article]]);
                            if (isset($produit['Produit']['id']) and !empty($produit['Produit']['id'])) {
                                $commandedetail = true;
                            } else {
                                $response['message'] = 'Code a barre incorrect produit introuvable !';
                            }
                        } elseif (isset($produit['Salepointdetail']['ecommercedetail_id']) and !empty($produit['Salepointdetail']['ecommercedetail_id'])) {
                            $commandedetail = false;
                        }
                    }

                    /* else {
                        $produit = $this->Salepoint->Salepointdetail->find('first',[
                            'conditions' => [
                                'Produit.code_barre' => $code_article ,
                                'Salepointdetail.salepoint_id' => $salepoint_id ,
                            ],
                            'fields'=>['Salepointdetail.id','Salepointdetail.qte','Salepointdetail.qte_cmd','Produit.id','Produit.code_barre','Produit.prix_vente','Produit.unite_id','Produit.tva_vente','Produit.pese'],
                            'contain'=>['Produit'],
                        ]);
                    } */
                    /* var_dump($commandedetail);
                    die();  */
                    /* var_dump(isset($produit["Salepointdetail"]["ecommercedetail_id"]));
                    die();  */

                    if ((isset($commandedetail) and ($commandedetail == true)) || (isset($produit['Salepointdetail']['id']) and !empty($produit['Salepointdetail']['id']) and ($commandedetail == true))) {
                        /*     if ( isset( $produit['Produit']['pese'] ) AND $produit['Produit']['unite_id'] == 4 )  $qte = (int) $quantite;  */// piéce
                        if (isset($produit['Produit']['pese']) and $produit['Produit']['pese'] == '1') {
                            $qte = $quantite / $cb_div_kg; // autre
                        } else {
                            $qte = (int) $quantite;
                            $pese = 0;
                        }

                        /* if(isset($pese)) {
                            if(strlen((string) $qte) == 2) {
                                $qte = $qte/10;
                            }
                            else if(strlen((string) $qte) == 3) {
                                $qte = $qte/((int) 100);
                            }
                            else if(strlen((string) $qte) == 4) {
                                $qte = $qte/1000;
                            }

                        } */
                        $qte_old = (!empty($produit['Salepointdetail']['qte'])) ? $produit['Salepointdetail']['qte'] : 0;
                        /* $qte = (int) $quantite;
                        $qte = $qte/1000; */

                        $qte = $qte_old + $qte;
                        $details_d = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id,
                                'Salepointdetail.produit_id' => $produit['Produit']['id'], ]]);
                        if (count($details_d) > 1) {
                            $pass = 1;
                        }

                        if ($qte <= 0) {
                            $response['message'] = 'Opération impossible la quantité doit étre supérieur a zéro !';
                        } else {
                            $qte_cmd = (isset($produit['Salepointdetail']['qte_cmd'])) ? $produit['Salepointdetail']['qte_cmd'] : 0;
                            if (($qte > $qte_cmd and (isset($commandedetail) == false)) || (isset($produit['Salepoint']['glovo_id']) and (!isset($pass)) and ($qte > $qte_cmd * 1.02)) || (isset($ecommerced) and (!isset($pass)) and ($qte > $qte_cmd))) {
                                $response['message'] = 'la quantité livrée doit étre inferieure ou égale a la quantité commandée !';
                            } else {
                                $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                                $division_tva = (1 + $tva / 100);

                                //modif
                                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                                $ecommerce_id = $salepoint['Salepoint']['ecommerce_id'];

                                if (isset($ecommerced) and ($ecommerced == true)) {
                                    $ecommerce_d = $this->Ecommerce->Ecommercedetail->find('all', [
                                        'conditions' => [
                                            'Ecommercedetail.produit_id' => $produit['Produit']['id'],
                                            'Ecommercedetail.ecommerce_id' => $ecommerce_id,
                                        ],
                                        'fields' => ['Ecommercedetail.id', 'Ecommercedetail.prix_vente'],
                                    ]);
                                    $total_ht = round($ecommerce_d[0]['Ecommercedetail']['prix_vente'] * $qte / $division_tva, 2);
                                    $total_ttc = round($ecommerce_d[0]['Ecommercedetail']['prix_vente'] * $qte, 2);
                                }
                                ///add recent
                                if (isset($produit['Salepoint']['glovo_id'])) {
                                    $total_ht = round($produit['Salepointdetail']['prix_vente'] * $qte / $division_tva, 2);
                                    $total_ttc = round($produit['Salepointdetail']['prix_vente'] * $qte, 2);
                                }
                                ///////////
                                // if not isset $produit['Salepoint']['glovo_id'] and is noet isset $ecommerced 
                                if (!isset($produit['Salepoint']['glovo_id']) and !isset($ecommerced)) {
                                    $prix_vente_ht = round($produit['Produit']['prix_vente'] / $division_tva, 2);
                                    $prix_vente_ttc = $produit['Produit']['prix_vente'];

                                    $total_ht = round($prix_vente_ht * $qte, 2);
                                    $total_ttc = round($prix_vente_ttc * $qte, 2);
                                }

                                /* $prix_vente_ht = round( $ecommerce_d["Ecommercedetail"]["prix_vente"]/$division_tva,2 );
                                $prix_vente_ttc = $ecommerce_d["Ecommercedetail"]["prix_vente"];

                                /////////old
                                $prix_vente_ht = round( $produit['Produit']['prix_vente']/$division_tva,2 );
                                $prix_vente_ttc = $produit['Produit']['prix_vente'];

                                $total_ht = round($prix_vente_ht*$qte,2);
                                $total_ttc = round($prix_vente_ttc*$qte,2);
                                */

                                if (isset($produit['Salepointdetail']['id'])) {
                                    $data['Salepointdetail']['id'] = $produit['Salepointdetail']['id'];
                                }
                                $data['Salepointdetail']['total'] = $total_ht;
                                $data['Salepointdetail']['ttc'] = $total_ttc;
                                $data['Salepointdetail']['qte'] = $qte;
                                if (!isset($ecommerce_d)) {
                                    $data['Salepointdetail']['prix_vente'] = $produit['Produit']['prix_vente'];
                                    $data['Salepointdetail']['produit_id'] = $produit['Produit']['id'];
                                    $data['Salepointdetail']['salepoint_id'] = $salepoint_id;
                                    $data['Salepointdetail']['montant_remise'] = 0;
                                    $data['Salepointdetail']['remise'] = 0;
                                    $data['Salepointdetail']['onhold'] = -1;
                                    $data['Salepointdetail']['stat'] = -1;
                                } else {
                                    $data['Salepointdetail']['prix_vente'] = $ecommerce_d[0]['Ecommercedetail']['prix_vente'];
                                    $data['Salepointdetail']['produit_id'] = $produit['Produit']['id'];
                                    $data['Salepointdetail']['salepoint_id'] = $salepoint_id;
                                    $data['Salepointdetail']['montant_remise'] = 0;
                                    $data['Salepointdetail']['remise'] = 0;
                                    $data['Salepointdetail']['onhold'] = -1;
                                    $data['Salepointdetail']['stat'] = -1;
                                    $data['Salepointdetail']['nom_produit_ean13'] = $nom_produit_ean13; // le nom produit de la table EAN13 vers salespointdetail
                                }
                                if (isset($produit['Salepoint']['glovo_id'])) {
                                    $data['Salepointdetail']['prix_vente'] = $produit['Salepointdetail']['prix_vente'];
                                    $data['Salepointdetail']['produit_id'] = $produit['Produit']['id'];
                                    $data['Salepointdetail']['salepoint_id'] = $salepoint_id;
                                    $data['Salepointdetail']['montant_remise'] = 0;
                                    $data['Salepointdetail']['remise'] = 0;
                                    $data['Salepointdetail']['onhold'] = -1;
                                    $data['Salepointdetail']['stat'] = -1;
                                }
                                $total_ht_actuel = $total_ttc;
                                $salepoint1 = $this->Salepoint->find('first', [
                                    'conditions' => [
                                        'Salepoint.id' => $salepoint_id,
                                    ], ]);

                                $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
                                /* $remise = ( isset( $salepoint1['Salepoint']['remise'] ) AND !empty( $salepoint1['Salepoint']['remise'] ) ) ? (float) $salepoint1['Salepoint']['remise'] : 0 ;
                                $total_cmd = 0;
                                $total_a_payer_ht = 0;
                                $total_a_payer_ttc = 0;
                                $remise_articles = 0;
                                $montant_remise_articles = 0;
                                $nombre_total = count( $details );
                                foreach ($details as $k => $v) {
                                    // TVA
                                    $tva = ( isset( $v['Produit']['tva_vente'] ) AND !empty( $v['Produit']['tva_vente'] ) ) ? (int) $v['Produit']['tva_vente'] : 0 ;
                                    $division_tva = (1+$tva/100);
                                    // Quantité & Prix de vente
                                    $qte_cmd = $v['Salepointdetail']['qte_cmd'];
                                    $qte = ( isset( $v['Salepointdetail']['qte'] ) AND $v['Salepointdetail']['qte'] > 0 ) ? $v['Salepointdetail']['qte'] : 0;

                                    $prix_vente_ht = round( $v['Salepointdetail']['prix_vente']/$division_tva,2 );
                                    $prix_vente_ttc = $v['Salepointdetail']['prix_vente'];
                                    // Calcule total
                                    $total_ht = round($prix_vente_ht*$qte,2);
                                    $total_ttc = round($prix_vente_ttc*$qte,2);

                                    $total_a_payer_ht = $total_a_payer_ht + $total_ht;
                                    $total_a_payer_ttc = $total_a_payer_ttc + $total_ttc;
                                    $total_cmd = $total_cmd + round($prix_vente_ttc*$qte_cmd,2);
                                }

                                $montant_remise = ( $total_a_payer_ttc >= 0 ) ? (float) ($total_a_payer_ttc*$remise)/100 : 0 ;

                                $montant_tva = $total_a_payer_ttc-$total_a_payer_ht;
                                $total_apres_reduction = round( $total_a_payer_ttc - $montant_remise ,2);
                                $total_ac = $total_apres_reduction + $total_ht_actuel;
                             */    //$count_values = array();
                                $details_d = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id,
                                'Salepointdetail.produit_id' => $produit['Produit']['id'], ]]);
                                if (count($details_d) > 1) {
                                    $response['duplicate'] = true;
                                    $response['produit_id'] = $produit['Produit']['id'];
                                    $response['error'] = false;
                                    $response['code_barre'] = $code_barre;
                                    $options = $this->Optionproduit->find('all', ['conditions' => ['Optionproduit.id_produit' => $produit['Produit']['id']],
                                        'contain' => ['Options'],
                                        'fields' => ['Options.libelle'], ]);
                                    $optionlibelle = [];
                                    foreach ($options as $option) {
                                        $optionlibelle[] = $option['Options']['libelle'];
                                    }
                                    $optionslib = implode(',', $optionlibelle);
                                    $conditionnements = $this->Typeconditionnementtproduit->find('all', ['conditions' => ['Typeconditionnementtproduit.id_produit' => $produit['Produit']['id']],
                                        'contain' => ['Typeconditionnement'],
                                        'fields' => ['Typeconditionnement.libelle'], ]);
                                    $conditionnementlibelle = [];
                                    if (!isset($ecommerced)) {
                                        foreach ($conditionnements as $conditionnement) {
                                            $conditionnementlibelle[] = $conditionnement['Typeconditionnement']['libelle'];
                                        }
                                        $conditionnementlib = implode(',', $conditionnementlibelle);
                                    } else {
                                        $details = $this->Ecommerce->Ecommercedetail->find('all', ['conditions' => ['Ecommercedetail.ecommerce_id' => $salepoint['Salepoint']['ecommerce_id'],
                                        'Ecommercedetail.produit_id' => $produit['Produit']['id'], ]]);

                                        $optionlibelle = [];
                                        foreach ($details as $detail) {
                                            $optionlibelle[] = $detail['Ecommercedetail']['preparation'];
                                        }

                                        $conditionnementlibelle = [];
                                        foreach ($details as $detail) {
                                            $conditionnementlibelle[] = $detail['Ecommercedetail']['variation_id'];
                                        }
                                    }
                                    $i = 0;
                                    if (!isset($ecommerced)) {
                                        foreach ($details_d as $d) {
                                            $produit = $this->Produit->find('first', ['conditions' => ['Produit.id' => $d['Salepointdetail']['produit_id']]]);
                                            $response['details'][$i] = [$d['Salepointdetail']['id'], $produit['Produit']['libelle'],
                                        $d['Salepointdetail']['qte'], $d['Salepointdetail']['qte_cmd'],
                                        $d['Salepointdetail']['unit_price'], $conditionnementlib, $optionslib, $d['Salepointdetail']['ttc'], ];
                                            ++$i;
                                        }
                                    } else {
                                        foreach ($details_d as $d) {
                                            $produit = $this->Produit->find('first', ['conditions' => ['Produit.id' => $d['Salepointdetail']['produit_id']]]);
                                            $response['details'][$i] = [$d['Salepointdetail']['id'], $produit['Produit']['libelle'],
                                            $d['Salepointdetail']['qte'], $d['Salepointdetail']['qte_cmd'],
                                            $d['Salepointdetail']['unit_price'], $conditionnementlibelle[$i], $optionlibelle[$i], $d['Salepointdetail']['ttc'], ];
                                            ++$i;
                                        }
                                    }

                                    //return $this->redirect( ['action' => 'duplicateEcommerce',$salepoint_id,$produit['Produit']['id']] );
                                }
                                /* else if($total_ac > $salepoint1["Salepoint"]["total_cmd"]) {
                                    $response['message'] = "Le Net a payer dépasse le total commandé";

                                } */
                                else {
                                    if ($this->Salepoint->Salepointdetail->save($data)) {
                                        $this->calcule($salepoint_id);
                                        $response['message'] = "L'enregistrement a été fait avec succès. !";
                                        $response['error'] = false;
                                    } else {
                                        $response['message'] = 'Erreur de sauvgarde des donnée !';
                                    }
                                }
                            }
                        }
                    } else {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                    }
                } else {
                    $response['message'] = 'Code a barre incorrect ou vide !';
                }
        }
        if ($this->notice == 1) {
            $response['notice'] = true;
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function CheckRemiseGlobale($client_id)
    {
        $remise_globale = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $client_id, 'type' => 'globale']]);

        if (isset($remise_globale['Remiseclient']['id'])) {
            return true;
        }

        return false;
    }

    public function RemiseGlobale($salepoint_id, $client_id, $montant)
    {
        $remise_globale = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $client_id, 'type' => 'globale']]);

        if ($montant >= $remise_globale['Remiseclient']['montant_ticket']) {
            $pourcentage = $remise_globale['Remiseclient']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            /* $net_a_payer = $salepoint['Salepoint']['total_a_payer_ttc'] - $montantR;
            $this->Salepoint->id = $salepoint['Salepoint']['id'];
            $this->Salepoint->saveField('total_paye', $net_a_payer); */

            /*  $this->loadModel('Avance');
             $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);
             $this->Avance->id = $avances[0]['Avance']['id'];
             $this->Avance->saveField('montant', $net_a_payer);
             $this->Avance->id = $avances[1]['Avance']['id'];
             $this->Avance->save('montant', 0);
             $this->calculeRemiseGlob($salepoint_id, $remise_globale['Remiseclient']['montant'], $montantR, $net_a_payer);  */
            $data['Salepoint']['id'] = $salepoint_id;
            $data['Salepoint']['remise'] = $remise_globale['Remiseclient']['montant'];
            $data['Salepoint']['montant_remise'] = $montantR;
            $data['Salepoint']['total_apres_reduction'] = $montant - $montantR;
            $this->Salepoint->save($data);

            return true;
        }
    }

    public function RemiseClient($client_id, $montant, $produit_id, $qte)
    {
        $produit = $this->Produit->find('first', ['conditions' => ['id' => $produit_id]]);

        $remisearticle = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $client_id, 'type' => 'article', 'produit_id' => $produit_id]]);
        /*   $remisecategories = $this->Remiseclient->find('all', ['conditions' => ['client_id' => $client_id, 'type' => 'categorie']]);
          $remisegloable = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $client_id, 'type' => 'globale']]);
 */
        $this->loadModel('Promotion');
        $promotion_article_client = $this->Promotion->find('first', ['conditions' => ['client_id' => $client_id, 'categorie_id' => null, 'produit_id' => $produit_id]]);
        $promotion_categorie_client = $this->Promotion->find('first', ['conditions' => ['client_id' => $client_id, 'categorie_id' => $produit['Produit']['categorieproduit_id']]]);
        $promotion_article = $this->Promotion->find('first', ['conditions' => ['client_id' => null, 'categorie_id' => null, 'produit_id' => $produit_id]]);
        $promotion_categorie = $this->Promotion->find('first', ['conditions' => ['client_id' => null, 'categorie_id' => $produit['Produit']['categorieproduit_id'], 'produit_id' => $produit_id]]);
        /* if (isset($remisegloable['Remiseclient']['id'])) {
            $pourcentage = $remisegloable['Remiseclient']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        } */
        if (isset($remisearticle['Remiseclient']['id']) and $qte >= $remisearticle['Remiseclient']['nb_kilos']) {
            $pourcentage = $remisearticle['Remiseclient']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        } elseif (isset($promotion_article_client['Promotion']['id'])) {
            $pourcentage = $promotion_article_client['Promotion']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        } elseif (isset($promotion_categorie_client['Promotion']['id'])) {
            $pourcentage = $promotion_categorie_client['Promotion']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        } elseif (isset($promotion_article['Promotion']['id'])) {
            $pourcentage = $promotion_article['Promotion']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        } elseif (isset($promotion_categorie['Promotion']['id'])) {
            $pourcentage = $promotion_categorie['Promotion']['montant'];
            $pourcentage /= 100;
            $montantR = $montant * $pourcentage;
            $net_a_payer = $montant - $montantR;

            return $net_a_payer;
        }

        /* if (isset($remisecategories[0]['Remiseclient']['id'])) {
            $produit = $this->Produit->find('first', ['conditions' => ['id' => $produit_id]]);

            foreach ($remisecategories as $remisecategorie) {
                if ($produit['Produit']['categorieproduit_id'] == $remisecategorie['Remiseclient']['categorie_id']) {
                    $pourcentage = $remisecategorie['Remiseclient']['montant'];
                    $pourcentage /= 100;
                    $montantR = $montant * $pourcentage;
                    $net_a_payer = $montant - $montantR;

                    return $net_a_payer;
                }
            }
        } */

        return $montant;
    }

    public function scanGlovo($code_barre = null, $salepoint_id = null)
    {
        $response['error'] = true;
        $response['message'] = '';
        $longeur = strlen($code_barre);
        if ($longeur != 13) {
            $response['message'] = 'Code a barre est incorrect , produit introuvable !';
        } else {
            $params = $this->Parametreste->findList();
            $cb_identifiant = (isset($params['cb_identifiant']) and !empty($params['cb_identifiant'])) ? $params['cb_identifiant'] : '2900';
            $cb_produit_depart = (isset($params['cb_produit_depart']) and !empty($params['cb_produit_depart'])) ? $params['cb_produit_depart'] : 4;
            $cb_produit_longeur = (isset($params['cb_produit_longeur']) and !empty($params['cb_produit_longeur'])) ? $params['cb_produit_longeur'] : 3;
            $cb_quantite_depart = (isset($params['cb_quantite_depart']) and !empty($params['cb_quantite_depart'])) ? $params['cb_quantite_depart'] : 7;
            $cb_quantite_longeur = (isset($params['cb_quantite_longeur']) and !empty($params['cb_quantite_longeur'])) ? $params['cb_quantite_longeur'] : 5;
            $cb_div_kg = (isset($params['cb_div_kg']) and !empty($params['cb_div_kg']) and $params['cb_div_kg'] > 0) ? (int) $params['cb_div_kg'] : 1000;

            $identifiant = substr(trim($code_barre), 0, 4);
            if ($cb_identifiant != $identifiant) {
                $response['message'] = "Identifiant du code à barre est incorrect , veuillez vérifier votre paramétrage d'application !";
            } else {
                $code_article = substr(trim($code_barre), $cb_produit_depart, $cb_produit_longeur);
                $quantite = substr(trim($code_barre), $cb_quantite_depart, $cb_quantite_longeur);

                if (!empty($code_article)) {
                    $produit = $this->Produit->find('first', ['fields' => ['id', 'pese', 'prix_vente', 'unite_id', 'tva_vente'], 'conditions' => ['Produit.type' => 2, 'Produit.code_barre' => $code_article]]);

                    if (isset($produit['Produit']['id']) and !empty($produit['Produit']['id'])) {
                        if (isset($produit['Produit']['pese']) and $produit['Produit']['pese'] == '1') {
                            $qte = $quantite / $cb_div_kg;
                        } else {
                            $qte = (int) $quantite;
                        } // piéce

                        if ($qte <= 0) {
                            $response['message'] = 'Opération impossible la quantité doit étre supérieur a zéro !';
                        } else {
                            $produit_old = $this->Salepoint->Salepointdetail->find('first', [
                                'conditions' => [
                                    'Produit.code_barre' => $code_article,
                                    'Salepointdetail.salepoint_id' => $salepoint_id,
                                ],
                                'fields' => ['Salepointdetail.id', 'Salepointdetail.qte', 'Salepointdetail.prix_vente', 'Salepointdetail.ecommercedetail_id', 'Salepointdetail.qte_cmd', 'Produit.id', 'Produit.code_barre', 'Produit.prix_vente', 'Produit.unite_id', 'Produit.tva_vente', 'Produit.pese'],
                                'contain' => ['Produit'],
                            ]);

                            $qte_old = (!empty($produit_old['Salepointdetail']['qte'])) ? $produit_old['Salepointdetail']['qte'] : 0;

                            $qte = $qte_old + $qte;
                            $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                            $division_tva = (1 + $tva / 100);

                            $prix_vente_ht = round($produit_old['Salepointdetail']['prix_vente'] / $division_tva, 2);
                            $prix_vente_ttc = $produit_old['Salepointdetail']['prix_vente'];

                            $total_ht = round($prix_vente_ht * $qte, 2);
                            $total_ttc = round($prix_vente_ttc * $qte, 2);

                            if (isset($produit_old['Salepointdetail']['id'])) {
                                $data['Salepointdetail']['id'] = $produit_old['Salepointdetail']['id'];
                            }

                            $data['Salepointdetail']['salepoint_id'] = $salepoint_id;
                            $data['Salepointdetail']['total'] = $total_ht;

                            $data['Salepointdetail']['ttc'] = $total_ttc;
                            $data['Salepointdetail']['qte'] = $qte;

                            $data['Salepointdetail']['montant_remise'] = 0;
                            $data['Salepointdetail']['remise'] = 0;
                            $data['Salepointdetail']['onhold'] = -1;
                            $data['Salepointdetail']['stat'] = -1;

                            if ($this->Salepoint->Salepointdetail->save($data)) {
                                $this->calcule($salepoint_id);

                                $response['message'] = "L'enregistrement a été fait avec succès. !";
                                $response['error'] = false;
                            } else {
                                $response['message'] = 'Erreur de sauvgarde des donnée !';
                            }
                        }
                    } else {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                    }
                } else {
                    $response['message'] = 'Code a barre incorrect ou vide !';
                }
            }
        }

        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function getProduitParEAN13($code_ean13) {
        $this->autoRender = false;
    
        $produit = $this->Produit->find('first', [
            'fields' => ['Produit.prix_vente'], // <-- ici on récupère juste prix_vente
            'conditions' => [
                'Produit.type' => 2,
                'Ean13Code.code_ean13' => $code_ean13
            ],
            'joins' => [
                [
                    'table' => 'ean13_codes',
                    'alias' => 'Ean13Code',
                    'type' => 'INNER',
                    'conditions' => ['Ean13Code.produit_id = Produit.id']
                ]
            ]
        ]);
    
        if (!empty($produit)) {
            return $produit;
        } else {
            return ['error' => 'Produit non trouvé'];
        }
    }


    public function getEan13Details($code_ean13, $client_id = null) {
        $this->autoRender = false;
        // il faut ajouter le store ID ici
        $store_id = $this->Session->read('Auth.User.StoreSession.id');
    
        $this->loadModel('Ean13Code');

        // if $client_id is null
        if ($client_id != null) {
            // if $client_id is not null
            $ean = $this->Ean13Code->find('first', [
                'conditions' => [
                    'Ean13Code.code_ean13' => $code_ean13,
                    'Ean13Code.store_id' => $store_id,
                    'Ean13Code.client_id' => $client_id // <-- ici on filtre par client_id
                ]
            ]);
            // if $ean is empty
            if (empty($ean)) {
                // on cherche sans client_id
                $ean = $this->Ean13Code->find('first', [
                    'conditions' => [
                        'Ean13Code.code_ean13' => $code_ean13,
                        'Ean13Code.store_id' => $store_id,
                        'Ean13Code.client_id' => null // <-- ici on filtre par client_id
                    ]
                ]);
            }
        } else {
            // if $client_id is null
            $ean = $this->Ean13Code->find('first', [
                'conditions' => [
                    'Ean13Code.code_ean13' => $code_ean13,
                    'Ean13Code.store_id' => $store_id,
                    'Ean13Code.client_id' => null
                ]
            ]);
        }
            
        if (!empty($ean)) {
            return $ean['Ean13Code'];
        }
    
        return null;
    }
    
    
    public function scan($code_barre = null, $salepoint_id = null)
    {

        // Depuis la table salespoint selectionner le client_id à la base de $salepoint_id
        $client_id = $this->Salepoint->find('first', [
            'conditions' => ['Salepoint.id' => $salepoint_id],
            'fields' => ['Salepoint.client_id']
        ]);
        // tester si le client_id existe
        if (isset($client_id['Salepoint']['client_id']) and !empty($client_id['Salepoint']['client_id'])) {
            $client_id = $client_id['Salepoint']['client_id'];
        } else {
            $client_id = null;
        }


        $response['error'] = true;
        $response['message'] = '';
        $longeur = strlen($code_barre);

        if ($longeur != 13) {
            $response['message'] = 'Code a barre est incorrect , produit introuvable !';
        } else {

            $params = $this->Parametreste->findList();
            $cb_div_kg = (isset($params['cb_div_kg']) and !empty($params['cb_div_kg']) and $params['cb_div_kg'] > 0) ? (int) $params['cb_div_kg'] : 1000;

                $code_ean13 = $code_barre;
                $produit = $this->getEan13Details($code_ean13, $client_id);

                if (!empty($produit)) {
                $code_article = $produit['code_barre'];
                } else {
                echo 'Produit non trouvé';
                }
                $quantite = 1;


                if (!empty($code_article)) {

                    // Old script 
                    // $produit = $this->Produit->find('first', ['fields' => ['id', 'pese', 'prix_vente', 'unite_id', 'tva_vente'], 'conditions' => ['Produit.type' => 2, 'Produit.code_barre' => $code_article]]);

                    // Étape 1 : requête sans prix_vente
                    $produit = $this->Produit->find('first', [
                        'fields' => ['id', 'pese', 'unite_id', 'tva_vente'],
                        'conditions' => [
                            'Produit.type' => 2,
                            'Produit.code_barre' => $code_article
                        ]
                    ]);

                    // Étape 2 : récupérer prix_vente depuis EAN13

                    $code_ean13 = $code_barre;

                    // if $client_id is null
                    if ($client_id != null) {
                        $details = $this->getEan13Details($code_ean13, $client_id);
                    } else {
                        // if $client_id is null
                        $details = $this->getEan13Details($code_ean13, null);
                    }

                    // Vérification de l'existence de nom_produit
                    if (!empty($details['nom_produit'])) {
                        $produit_nom_ean13 = $details['nom_produit'];
                    }

                    // Étape 3 : injecter prix_vente dans le même tableau
                    if (!empty($details['prix_vente'])) {
                        $produit['Produit']['prix_vente'] = $details['prix_vente'];
                    }

                    // Vérification de l'existence de prix_vente
                    if (empty($details['prix_vente'])) {
                        $this->Session->setFlash('Le prix de vente est introuvable pour ce produit.', 'alert-danger');
                        return $this->redirect($this->referer());
                    }

                    if (isset($produit['Produit']['id']) and !empty($produit['Produit']['id'])) {
                        if (isset($produit['Produit']['pese']) and $produit['Produit']['pese'] == '1') {
                            $qte = $quantite / $cb_div_kg;
                        } else {
                            $qte = (int) $quantite;
                        } // piéce

                        if ($qte <= 0) {
                            $response['message'] = 'Opération impossible la quantité doit étre supérieur a zéro !';
                        } else {

                            
                        // Si le produit existe déjà dans le POS, la quantité ne doit pas être cumulée => ajouter une nouvelle ligne

                            /***
                             * 
                             * 
                            $produit_old = $this->Salepoint->Salepointdetail->find('first', [
                                'conditions' => [
                                    'Produit.code_barre' => $code_article,
                                    'Salepointdetail.salepoint_id' => $salepoint_id,
                                ],
                                'fields' => ['Salepointdetail.id', 'Salepointdetail.qte', 'Salepointdetail.ecommercedetail_id', 'Salepointdetail.qte_cmd', 'Produit.id', 'Produit.code_barre', 'Produit.prix_vente', 'Produit.unite_id', 'Produit.tva_vente', 'Produit.pese'],
                                'contain' => ['Produit'],
                            ]);
                            $qte_old = (!empty($produit_old['Salepointdetail']['qte'])) ? $produit_old['Salepointdetail']['qte'] : 0;
                            $qte = $qte_old + $qte;
                            */



                            $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                            $division_tva = (1 + $tva / 100);

                            $prix_vente_ht = round($produit['Produit']['prix_vente'] / $division_tva, 2);
                            $prix_vente_ttc = $produit['Produit']['prix_vente'];

                            $total_ht = round($prix_vente_ht * $qte, 2);
                            $total_ttc = round($prix_vente_ttc * $qte, 2);

                            if (isset($produit_old['Salepointdetail']['id'])) {
                                $data['Salepointdetail']['id'] = $produit_old['Salepointdetail']['id'];
                            }
                            $data['Salepointdetail']['prix_vente'] = $produit['Produit']['prix_vente'];
                            $data['Salepointdetail']['produit_id'] = $produit['Produit']['id'];
                            $data['Salepointdetail']['salepoint_id'] = $salepoint_id;
                            $data['Salepointdetail']['total'] = $total_ht;

                            $data['Salepointdetail']['ttc'] = $total_ttc;
                            $data['Salepointdetail']['qte'] = $qte;

                            $data['Salepointdetail']['montant_remise'] = 0;
                            $data['Salepointdetail']['remise'] = 0;
                            $data['Salepointdetail']['onhold'] = -1;
                            $data['Salepointdetail']['stat'] = -1;

                            if (!empty($details['nom_produit'])) {
                                 $data['Salepointdetail']['nom_produit_ean13'] = $produit_nom_ean13;
                            }

                            //add remise
                            $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                            //check remise globale
                            $remise_glob = $this->CheckRemiseGlobale($salepoint['Salepoint']['client_id']);

                            //check remise article
                            if (!$remise_glob) {
                                $total_ttc_nv = $this->RemiseClient($salepoint['Salepoint']['client_id'], $total_ttc, $data['Salepointdetail']['produit_id'], $qte);
                                if ($total_ttc_nv < $total_ttc) {
                                    $data['Salepointdetail']['ttc'] = $total_ttc_nv;

                                    $diff_remise = $total_ttc - $total_ttc_nv;
                                    $data['Salepointdetail']['montant_remise'] = $diff_remise;
                                    $data['Salepointdetail']['total'] = $total_ttc_nv / $division_tva;
                                    $data['Salepointdetail']['remise'] = ($diff_remise * 100) / $total_ttc;
                                }
                            }
                            if ($this->Salepoint->Salepointdetail->save($data)) {
                                $this->calculeRemise($salepoint_id);
                                if ($remise_glob) {
                                    $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
                                    $this->RemiseGlobale($salepoint['Salepoint']['id'], $salepoint['Salepoint']['client_id'], $salepoint['Salepoint']['total_apres_reduction']);
                                } else {
                                    if ($total_ttc_nv < $total_ttc) {
                                        $this->editlineremise(0, $salepoint_id);
                                    }
                                }
                                $this->saveFidelite($salepoint_id);
                                //$this->checkFidelite($salepoint_id);
                                $response['message'] = "L'enregistrement a été fait avec succès. !";
                                $response['error'] = false;
                            } else {
                                $response['message'] = 'Erreur de sauvgarde des donnée !';
                            }
                        }
                    } else {
                        $response['message'] = 'Code a barre incorrect produit introuvable !';
                    }
                } else {
                    $response['message'] = 'Code a barre incorrect ou vide !';
                }
            //}
        }

        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($response));
    }

    public function calculeRemise($salepoint_id = null, $cancel = false)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $remise = (isset($salepoint['Salepoint']['remise']) and !empty($salepoint['Salepoint']['remise'])) ? (float) $salepoint['Salepoint']['remise'] : 0;
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);

        $total_cmd = 0;
        $total_a_payer_ht = 0;
        $total_a_payer_ttc = 0;
        $remise_articles = 0;
        $montant_remise_articles = 0;
        $nombre_total = count($details);
        $remise_details = 0;
        foreach ($details as $k => $v) {
            // TVA
            $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 0;
            $division_tva = (1 + $tva / 100);
            // Quantité & Prix de vente
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : 0;

            $prix_vente_ht = round($v['Salepointdetail']['prix_vente'] / $division_tva, 2);
            $prix_vente_ttc = $v['Salepointdetail']['prix_vente'];
            // Calcule total
            $total_ht = round($prix_vente_ht * $qte, 2);
            //$total_ttc = round($prix_vente_ttc * $qte, 2);

            $total_a_payer_ht = $total_a_payer_ht + $total_ht;
            /*  if($cancel == true) $total_a_payer_ttc += $v['Salepointdetail']['ttc'];
             else */
            $total_a_payer_ttc += $v['Salepointdetail']['ttc'] + $v['Salepointdetail']['montant_remise'];
            $total_cmd = $total_cmd + round($prix_vente_ttc * $qte_cmd, 2);
            $remise_details += $v['Salepointdetail']['montant_remise'];
        }

        $montant_remise = (isset($salepoint['Salepoint']['montant_remise']) and !empty($salepoint['Salepoint']['montant_remise'])) ? (float) $salepoint['Salepoint']['montant_remise'] : 0;

        $montant_tva = $total_a_payer_ttc - $total_a_payer_ht;
        if ($cancel == true) {
            $total_apres_reduction = $total_a_payer_ttc - $remise_details;
        } else {
            $total_apres_reduction = $total_a_payer_ttc - $montant_remise;
        }

        $total_paye = 0;
        foreach ($avances as $k => $v) {
            $total_paye = $total_paye + $v['Avance']['montant'];
        }

        $reste_a_payer = $total_apres_reduction - $total_paye;
        $reste_a_payer = ($reste_a_payer <= 0) ? 0 : $reste_a_payer;

        ///add
        $total_paye -= $salepoint['Salepoint']['fee'];

        if (bccomp($total_apres_reduction, $total_paye, 3) == 0) {
            $paye = 2;
        } elseif ($total_paye == 0) {
            $paye = -1;
        } else {
            $paye = 1;
        }

        $salepointData['Salepoint']['paye'] = $paye;
        $salepointData['Salepoint']['remise'] = $remise;
        $salepointData['Salepoint']['id'] = $salepoint_id;
        $salepointData['Salepoint']['total_cmd'] = $total_cmd;
        $salepointData['Salepoint']['total_paye'] = $total_paye;
        $salepointData['Salepoint']['montant_tva'] = $montant_tva;
        $salepointData['Salepoint']['reste_a_payer'] = $reste_a_payer;
        if ($cancel == true and empty($details)) {
            $salepointData['Salepoint']['montant_remise'] = 0;
        } elseif ($cancel == true and !empty($details)) {
            $salepointData['Salepoint']['montant_remise'] = $remise_details;
        }
        $salepointData['Salepoint']['total_a_payer_ht'] = $total_a_payer_ht;
        $salepointData['Salepoint']['total_a_payer_ttc'] = $total_a_payer_ttc;
        $salepointData['Salepoint']['total_apres_reduction'] = $total_apres_reduction;

        if ($total_apres_reduction > $total_cmd) {
            $this->notice = 1;
        }

        if ($this->Salepoint->save($salepointData)) {
            return true;
        }
    }

    public function calcule($salepoint_id = null)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $remise = (isset($salepoint['Salepoint']['remise']) and !empty($salepoint['Salepoint']['remise'])) ? (float) $salepoint['Salepoint']['remise'] : 0;
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoint_id]]);

        $total_cmd = 0;
        $total_a_payer_ht = 0;
        $total_a_payer_ttc = 0;
        $remise_articles = 0;
        $montant_remise_articles = 0;
        $nombre_total = count($details);
        foreach ($details as $k => $v) {
            // TVA
            $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 0;
            $division_tva = (1 + $tva / 100);
            // Quantité & Prix de vente
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : 0;

            $prix_vente_ht = round($v['Salepointdetail']['prix_vente'] / $division_tva, 2);
            $prix_vente_ttc = $v['Salepointdetail']['prix_vente'];
            // Calcule total
            $total_ht = round($prix_vente_ht * $qte, 2);
            $total_ttc = round($prix_vente_ttc * $qte, 2);

            $total_a_payer_ht = $total_a_payer_ht + $total_ht;
            $total_a_payer_ttc = $total_a_payer_ttc + $total_ttc;
            $total_cmd = $total_cmd + round($prix_vente_ttc * $qte_cmd, 2);
        }

        $montant_remise = ($total_a_payer_ttc >= 0) ? (float) ($total_a_payer_ttc * $remise) / 100 : 0;
        $montant_remise = (isset($salepoint['Salepoint']['montant_remise']) and !empty($salepoint['Salepoint']['montant_remise'])) ? (float) $salepoint['Salepoint']['montant_remise'] : $montant_remise;
        $montant_tva = $total_a_payer_ttc - $total_a_payer_ht;
        $total_apres_reduction = $total_a_payer_ttc - $montant_remise;

        $total_paye = 0;
        foreach ($avances as $k => $v) {
            $total_paye = $total_paye + $v['Avance']['montant'];
        }

        $reste_a_payer = $total_apres_reduction - $total_paye;
        $reste_a_payer = ($reste_a_payer <= 0) ? 0 : $reste_a_payer;

        ///add
        // $total_paye -= $salepoint['Salepoint']['fee'];

        if (bccomp($total_apres_reduction, $total_paye, 3) == 0) {
            $paye = 2;
        } elseif ($total_paye == 0) {
            $paye = -1;
        } else {
            $paye = 1;
        }


        $salepointData['Salepoint']['paye'] = $paye;
        $salepointData['Salepoint']['remise'] = $remise;
        $salepointData['Salepoint']['id'] = $salepoint_id;
        $salepointData['Salepoint']['total_cmd'] = $total_cmd;
        $salepointData['Salepoint']['total_paye'] = $total_paye;
        $salepointData['Salepoint']['montant_tva'] = $montant_tva;
        $salepointData['Salepoint']['reste_a_payer'] = $reste_a_payer;
        $salepointData['Salepoint']['montant_remise'] = $montant_remise;
        $salepointData['Salepoint']['total_a_payer_ht'] = $total_a_payer_ht;
        $salepointData['Salepoint']['total_a_payer_ttc'] = $total_a_payer_ttc;
        $salepointData['Salepoint']['total_apres_reduction'] = $total_apres_reduction;

        if ($total_apres_reduction > $total_cmd) {
            $this->notice = 1;
        }

        return $this->Salepoint->save($salepointData);
    }

    public function editlineremise($id = null, $salepoint_id = null)
    {
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $total = 0;
        $remise = 0;
        $montant_remise = 0;
        foreach ($details as $k => $v) {
            $total = $total + $v['Salepointdetail']['ttc'];
            $remise = $remise + $v['Salepointdetail']['remise'];
            $montant_remise = $montant_remise + $v['Salepointdetail']['montant_remise'];
        }
        //dans le cas de gloa
        //$remise = $remise / count($details);
        $data['Salepoint']['remise'] = 0;
        $data['Salepoint']['montant_remise'] = $montant_remise;
        //$data['Salepoint']['total_apres_reduction'] = $total;

        if ($this->Salepoint->save($data)) {
            $this->calcule($salepoint_id);

            return true;
        }
    }

    public function checkFidelite($salepoint_id)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $this->loadModel('Fidelite');
        $fidelite = $this->Fidelite->find('first');
        if (isset($fidelite['Fidelite']['montant_cheque_cad'])) {
            $montant_cheque = $fidelite['Fidelite']['montant_cheque_cad'];
            if ($salepoint['Salepoint']['points_fidelite'] > $montant_cheque) {
                //activer cheque cad
                $this->loadModel('Chequecadeau');
                $cheque_cad = $this->Chequecadeau->find('first');
                $this->Chequecadeau->id = $cheque_cad['Chequecadeau']['id'];
                $this->Chequecadeau->saveField('active', 1);
            }
        }
    }

    public function saveFidelite($salepoint_id)
    {
        //points fidelite client
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        if ($salepoint['Salepoint']['client_id'] != 1) {
            $this->loadModel('Fidelite');
            $fidelite = $this->Fidelite->find('first');
            if (isset($fidelite['Fidelite']['montant'])) {
                $montant = $fidelite['Fidelite']['montant'];
                $total_points = 0;
                foreach ($details as $key => $value) {
                    if ($value['Salepointdetail']['remise'] == 0 and $value['Salepointdetail']['ttc'] >= $montant) {
                        $points = intval($value['Salepointdetail']['ttc'] / $montant);
                        $total_points += ($points * $fidelite['Fidelite']['points']);
                    }
                }
                $this->Salepoint->id = $salepoint_id;
                $this->Salepoint->saveField('points_fidelite', $total_points);
                /* $this->loadModel("Client");
                $client = $this->Client->find("first", ["conditions" => ["id" => $salepoint["Salepoint"]["client_id"]]]);
                $total = $client["Client"]["points_fidelite"] + $total_points;
                $this->Client->id = $salepoint["Salepoint"]["client_id"];
                $this->Client->saveField("points_fidelite", $total); */

            //check fidelite
            }
        }
    }

    public function holdingdetail($attente_id = null)
    {
        $details = $this->Attente->Attentedetail->find('all', ['conditions' => ['Attentedetail.attente_id' => $attente_id], 'contain' => ['Produit']]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function editline($id = null, $salepoint_id = null)
    {
        if ($this->request->is(['post', 'put'])) {
            if ($this->Salepoint->Salepointdetail->save($this->request->data)) {
                $this->calcule($salepoint_id);

                $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
                $total = 0;
                $remise = 0;
                $montant_remise = 0;
                foreach ($details as $k => $v) {
                    $total = $total + $v['Salepointdetail']['ttc'];
                    $remise = $remise + $v['Salepointdetail']['remise'];
                    $montant_remise = $montant_remise + $v['Salepointdetail']['montant_remise'];

                    $produit = $this->Produit->find('first', ['fields' => ['id', 'pese', 'prix_vente', 'unite_id', 'tva_vente'], 'conditions' => ['Produit.type' => 2, 'Produit.id' => $v['Salepointdetail']['produit_id']]]);

                    $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? (int) $produit['Produit']['tva_vente'] : 0;
                    $division_tva = (1 + $tva / 100);
                    $detail_ht = $v['Salepointdetail']['ttc'] / $division_tva;
                    $this->Salepoint->Salepointdetail->id = $v['Salepointdetail']['id'];
                    $this->Salepoint->Salepointdetail->saveField('total', $detail_ht);
                }
                $remise = $remise / count($details);
                $this->request->data['Salepoint']['remise'] = $remise;
                $this->request->data['Salepoint']['montant_remise'] = $montant_remise;
                $this->request->data['Salepoint']['total_apres_reduction'] = $total;

                if ($this->Salepoint->save($this->request->data)) {
                    //$this->CheckRemise($salepoint_id);
                }

                die('L\'enregistrement a été effectué avec succès.');
            } else {
                die('Il y a un problème');
            }
        } elseif ($this->Salepoint->Salepointdetail->exists($id)) {
            $this->request->data = $this->Salepoint->Salepointdetail->find('first', ['conditions' => ['Salepointdetail.id' => $id]]);
        }
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function holdoff($attente_id = null, $salepoint_id = null)
    {
        $attente = $this->Attente->find('first', ['conditions' => ['Attente.id' => $attente_id]]);
        $details = $this->Attente->Attentedetail->find('all', ['conditions' => ['Attentedetail.attente_id' => $attente_id]]);
        $this->Salepoint->Salepointdetail->deleteAll(['Salepointdetail.salepoint_id' => $salepoint_id]);

        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepointdetail'] = [];
        foreach ($details as $k => $v) {
            $data['Salepointdetail'][] = [
                'montant_remise' => $v['Attentedetail']['montant_remise'],
                'prix_vente' => $v['Attentedetail']['prix_vente'],
                'produit_id' => $v['Attentedetail']['produit_id'],
                'qte_cmd' => $v['Attentedetail']['qte_cmd'],
                'remise' => $v['Attentedetail']['remise'],
                'total' => $v['Attentedetail']['total'],
                'ttc' => $v['Attentedetail']['ttc'],
                'qte' => $v['Attentedetail']['qte'],
            ];
        }

        if ($this->Salepoint->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            if ($this->Attente->delete($attente_id)) {
                $this->Attente->Attentedetail->deleteAll(['Attentedetail.attente_id' => $attente_id]);
            }
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function traitercommande($commande_id = null, $salepoint_id = null)
    {
        $commande = $this->Commande->find('first', ['conditions' => ['Commande.id' => $commande_id]]);
        $this->Commande->id = $commande_id;
        $this->Commande->saveField('statut', 'en cours');
        $client_id = (isset($commande['Commande']['client_id']) and !empty($commande['Commande']['client_id'])) ? $commande['Commande']['client_id'] : null;
        $details = $this->Commande->Commandedetail->find('all', ['conditions' => ['Commandedetail.commande_id' => $commande_id]]);
        $this->Salepoint->Salepointdetail->deleteAll(['Salepointdetail.salepoint_id' => $salepoint_id]);
        $remise = (isset($commande['Commande']['remise']) and !empty($commande['Commande']['remise'])) ? (float) $commande['Commande']['remise'] : 0;

        $data['Salepoint']['remise'] = $remise;
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['commande_id'] = $commande_id;
        $data['Salepoint']['client_id'] = $client_id;
        $data['Salepoint']['type_vente'] = 1;

        $data['Salepointdetail'] = [];
        foreach ($details as $k => $v) {
            $data['Salepointdetail'][] = [
                'montant_remise' => $v['Commandedetail']['montant_remise'],
                'prix_vente' => $v['Commandedetail']['prix_vente'],
                'produit_id' => $v['Commandedetail']['produit_id'],
                'commandedetail_id' => $v['Commandedetail']['id'],
                'qte_cmd' => $v['Commandedetail']['qte_cmd'],
                'remise' => $v['Commandedetail']['remise'],
                'total' => 0,
                'ttc' => 0,
                'qte' => 0,
            ];
        }

        if ($this->Salepoint->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            $this->Session->setFlash('L\'action a été effectué avec succès. 1', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function modifierticket($salepoint_id = null)
    {
        $retour = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $details = $this->Salepoint->Salepointdetail->find('all', ['conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $remise = (isset($retour['Salepoint']['remise']) and !empty($retour['Salepoint']['remise'])) ? (float) $retour['Salepoint']['remise'] : 0;

        $data['Salepoint']['remise'] = $remise;
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['etat'] = -1;

        $data['Salepointdetail'] = [];
        foreach ($details as $k => $v) {
            $data['Salepointdetail'][] = [
                'montant_remise' => $v['Salepointdetail']['montant_remise'],
                'prix_vente' => $v['Salepointdetail']['prix_vente'],
                'produit_id' => $v['Salepointdetail']['produit_id'],
                'qte_cmd' => $v['Salepointdetail']['qte_cmd'],
                'remise' => $v['Salepointdetail']['remise'],
                'total' => $v['Salepointdetail']['total'],
                'qte' => $v['Salepointdetail']['qte'],
                'ttc' => $v['Salepointdetail']['ttc'],
                'id' => $v['Salepointdetail']['id'],
            ];
        }

        if ($this->Salepoint->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect(['action' => 'index', $salepoint_id]);
    }

    public function holdon($salepoint_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $details = $this->Salepoint->Salepointdetail->find('all', [
            'conditions' => [
                'Salepointdetail.stat' => -1,
                'Salepointdetail.onhold' => -1,
                'Salepointdetail.salepoint_id' => $salepoint_id,
            ],
        ]);

        if (empty($details)) {
            $this->Session->setFlash('Liste des article est vide !', 'alert-danger');

            return $this->redirect($this->referer());
        }

        $data['Attente']['user_id'] = $user_id;
        $data['Attente']['date'] = date('d-m-Y');
        $data['Attentedetail'] = [];

        $ids = [];
        $remise = 0;
        $nombre_total = count($details);
        foreach ($details as $k => $v) {
            $remise = $remise + $v['Salepointdetail']['remise'];
            $ids[$v['Salepointdetail']['id']] = $v['Salepointdetail']['id'];
            $data['Attentedetail'][] = [
                'montant_remise' => $v['Salepointdetail']['montant_remise'],
                'prix_vente' => $v['Salepointdetail']['prix_vente'],
                'produit_id' => $v['Salepointdetail']['produit_id'],
                'qte_cmd' => $v['Salepointdetail']['qte_cmd'],
                'remise' => $v['Salepointdetail']['remise'],
                'total' => $v['Salepointdetail']['total'],
                'ttc' => $v['Salepointdetail']['ttc'],
                'qte' => $v['Salepointdetail']['qte'],
            ];
        }
        $remise = $remise / $nombre_total;
        $data['Attente']['remise'] = $remise;

        $this->Salepoint->Salepointdetail->deleteAll(['Salepointdetail.id' => $ids]);
        if ($this->Attente->saveAssociated($data)) {
            $this->calcule($salepoint_id);
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function cancel($salepoint_id = null)
    {
        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['commande_id'] = null;
        $data['Salepoint']['etat'] = 3;
        $data['Salepoint']['paye'] = 3;
        $data['Salepoint']['store'] = $this->Session->read('Auth.User.StoreSession.id');

        $this->Salepoint->id = $salepoint_id;

        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        if (isset($salepoint['Salepoint']['commande_id']) and !empty($salepoint['Salepoint']['commande_id'])) {
            $this->Commande->id = $salepoint['Salepoint']['commande_id'];
            $this->Commande->saveField('statut', '');
        } elseif (isset($salepoint['Salepoint']['ecommerce_id']) and !empty($salepoint['Salepoint']['ecommerce_id'])) {
            $this->Ecommerce->id = $salepoint['Salepoint']['ecommerce_id'];
            $this->Ecommerce->saveField('statut', 'confirmed');
        }

        if ($this->Salepoint->save($data)) {
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect(['action' => 'index', 0]);
    }

    public function gift($salepoint_id = null)
    {
        // $user = $this->Session->read('Auth.User');
        // $role_id = $user['role_id'];
        // $role = $this->Role->find('first', ['conditions' => ['Role.id' => $role_id]]);
        /* if ($role['Role']['libelle'] != 'manager') {
            http_response_code(403);
            die('Que les manager peuvent faire cette action.');
        } */
        $this->Salepoint->id = $salepoint_id;
        /***
         * OLD CODE
         * if ($this->Salepoint->saveField('remise', 100)) {
            $this->CheckRemise($salepoint_id);
            die('L\'action a été effectué avec succès!');
        } else {
            die('Vous n\'avez pas le droit d\'effectuer cette action!');
        }  
        
        ***/

        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);


        $remise = 100; // Offert = 100%

        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);

        $SalepointdetailData = [];
        $total_montant_remise  = 0;
        
        foreach ($details as $k => $v) {
            // TVA
            $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 0;
            $division_tva = (1 + $tva / 100);

            // Quantité & Prix de vente
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : $qte_cmd;
            $prix_vente_ht = round($v['Salepointdetail']['prix_vente'] / $division_tva, 2);
            $prix_vente_ttc = $v['Salepointdetail']['prix_vente'];

            // Calcule total
            $total_ht = round($prix_vente_ht * $qte, 2);
            $total_ttc = round($prix_vente_ttc * $qte, 2);


            $montant_remise = ($total_ttc >= 0) ? (float) ($total_ttc * $remise) / 100 : 0;
            $total_montant_remise += $montant_remise;
            
            $total_ttc = $total_ttc - $montant_remise;

            // Data structure
            $SalepointdetailData[] = [
                'id' => $v['Salepointdetail']['id'],
                'montant_remise' => $montant_remise,
                'total' => $total_ht,
                'ttc' => $total_ttc,
                'remise' => $remise,
            ];
        }

        $data['Salepoint']['id'] = $salepoint_id;
        $data['Salepoint']['remise'] = $remise;
        $data['Salepoint']['montant_remise'] = $total_montant_remise;
        $data['Salepoint']['reste_a_payer'] = 0;

        if ($this->Salepoint->Salepointdetail->saveMany($SalepointdetailData) &&
            $this->Salepoint->save($data)
            ) {
            $this->calcule($salepoint_id);
            die('L\'action a été effectué avec succès!');
        }

        return true;

    } // END : gift

    public function close($salepoint_id = null)
    {
        return $this->redirect(['controller' => 'salepoints', 'action' => 'index']);
    }

    public function cancelline($id = null, $salepoint_id = null)
    {
        $this->Salepoint->Salepointdetail->id = $id;
        if ($this->Salepoint->Salepointdetail->saveField('qte', 0)) {
            $this->calcule($salepoint_id, true);
            die('La mise à jour été effectué avec succès.');
        } else {
            die('Il y a un problème');
        }
    }

    public function deleteline($id = null, $salepoint_id = null)
    {
        if (isset($this->globalPermission['Permission']['s']) and $this->globalPermission['Permission']['s'] == 0) {
            die('Vous n\'avez pas la permission de supprimer !');
        }
        $this->Salepoint->Salepointdetail->id = $id;
        if (!$this->Salepoint->Salepointdetail->exists()) {
            throw new NotFoundException(__('Invalide line'));
        }
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);

        if (isset($salepoint['Salepoint']['glovo_id']) or isset($salepoint['Salepoint']['ecommerce_id'])) {
            $this->Salepoint->Salepointdetail->saveField('qte', 0);
            $this->Salepoint->Salepointdetail->saveField('ttc', 0);
            $this->Salepoint->Salepointdetail->saveField('total', 0);
            $this->calculeRemise($salepoint_id, true);
            die('La suppression a été effectué avec succès.');
        } else {
            if ($this->Salepoint->Salepointdetail->delete()) {
                $this->calculeRemise($salepoint_id, true);
                $this->saveFidelite($salepoint_id);
                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);

                if (!isset($salepoint['Salepoint']['ecommerce_id']) and $this->CheckRemiseGlobale($salepoint['Salepoint']['client_id'])) {
                    $remise_globale = $this->Remiseclient->find('first', ['conditions' => ['client_id' => $salepoint['Salepoint']['client_id'], 'type' => 'globale']]);

                    if ($salepoint['Salepoint']['total_apres_reduction'] < $remise_globale['Remiseclient']['montant_ticket']) {
                        $this->Salepoint->id = $salepoint['Salepoint']['id'];
                        $this->Salepoint->saveField('remise', 0);
                    } else {
                        $this->RemiseGlobale($salepoint['Salepoint']['id'], $salepoint['Salepoint']['client_id'], $salepoint['Salepoint']['total_apres_reduction']);
                    }
                }
                die('La suppression a été effectué avec succès.');
            } else {
                die('Il y a un problème');
            }
        }
    }

    public function remise($salepoint_id = null)
    {
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $total = 0;
        foreach ($details as $k => $v) {
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : $qte_cmd;
            $total_unitaire = $qte * $v['Salepointdetail']['prix_vente'];
            $total = $total + $total_unitaire;
        }
        if ($this->request->is(['post', 'put'])) {
            if ($this->Salepoint->save($this->request->data)) {
                $this->CheckRemise($salepoint_id);
                $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
            } else {
                $this->Session->setFlash('Il y a un problème', 'alert-danger');
            }

            return $this->redirect($this->referer());
        } elseif ($this->Salepoint->exists($salepoint_id)) {
            $this->request->data = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        }

        $this->set(compact('total'));
        $this->layout = false;
    }

    public function CheckRemise($salepoint_id = null)
    {
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        
        $remise = (isset($salepoint['Salepoint']['remise']) and !empty($salepoint['Salepoint']['remise'])) ? (float) $salepoint['Salepoint']['remise'] : 0;
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);

        $SalepointdetailData = [];
        foreach ($details as $k => $v) {
            // TVA
            $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 0;
            $division_tva = (1 + $tva / 100);

            // Quantité & Prix de vente
            $qte_cmd = $v['Salepointdetail']['qte_cmd'];
            $qte = (isset($v['Salepointdetail']['qte']) and $v['Salepointdetail']['qte'] > 0) ? $v['Salepointdetail']['qte'] : $qte_cmd;
            $prix_vente_ht = round($v['Salepointdetail']['prix_vente'] / $division_tva, 2);
            $prix_vente_ttc = $v['Salepointdetail']['prix_vente'];

            // Calcule total
            $total_ht = round($prix_vente_ht * $qte, 2);
            $total_ttc = round($prix_vente_ttc * $qte, 2);

            $montant_remise = ($total_ttc >= 0) ? (float) ($total_ttc * $remise) / 100 : 0;

            $total_ttc = $total_ttc - $montant_remise;

            // Data structure
            $SalepointdetailData[] = [
                'id' => $v['Salepointdetail']['id'],
                'montant_remise' => $montant_remise,
                'total' => $total_ht,
                'ttc' => $total_ttc,
                'remise' => $remise,
            ];
        }

        if ($this->Salepoint->Salepointdetail->saveMany($SalepointdetailData)) {
            $this->calcule($salepoint_id);
        }

        return true;
    }

    public function changeState($salepoint_id = null)
    {
        $this->Salepoint->id = $salepoint_id;
        $this->Salepoint->saveField('etat', 3);
        $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $caisse_id = $this->Session->read('caisse_id');
        $options = ['conditions' => ['id' => $caisse_id]];
        $caisse = $this->Caisse->find('first', $options);

        $pourcentage = $caisse['Caisse']['pourcentage'] / 100;
        $montantt09 = $salepoint['Salepoint']['total_paye'] * $pourcentage;
        $montant = $caisse['Caisse']['montant'] - $montantt09;

        if (strpos($salepoint['Salepoint']['reference'], 'T09') !== false) {
            $montant += $salepoint['Salepoint']['total_paye'];
        }
        $this->Caisse->id = $caisse_id;
        $this->Caisse->saveField('montant', $montant);
        $this->Session->setFlash('L\'aexction a été effectué avec succès.', 'alert-success');

        $this->Salepoint->id = $salepoint_id;
        $this->Salepoint->saveField('print', -1);
        $this->redirect(['action' => 'index', 0]);

        return $this->redirect($this->referer());
    }

    public function annulertickets($salepoint_id = null)
    {
        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $today = date('Y-m-d');
        $salepoints = $this->Salepoint->find('all', [
            'conditions' => [
                'Salepoint.etat' => 2,
                'Salepoint.store' => $selected_store,
                'Salepoint.ecommerce_id' => null,
                'Salepoint.commande_id' => null,
                'DATE_FORMAT(Salepoint.date_u, "%Y-%m-%d")' => $today,
            ],
            'contain' => ['Salepointdetail', 'Client'],
            'order' => ['Salepoint.date desc'],
        ]);
        $this->set(compact('salepoints'));
        $this->layout = false;
    }

    public function impression($salepoint_id = null)
    {
        $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');

        $this->Salepoint->id = $salepoint_id;
        $this->Salepoint->saveField('print', -1);
        $this->redirect(['action' => 'index', 0]);
    }

    public function Reimpression()
    {
        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $caisse_id = $this->Session->read('caisse_id');
        $today = date('Y-m-d');
        $salepoints = $this->Salepoint->find('all', [
            'conditions' => [
                'Salepoint.etat' => 2,
                'Salepoint.store' => $selected_store,
                'Salepoint.caisse_id' => $caisse_id,
                'Salepoint.ecommerce_id' => null,
                'Salepoint.commande_id' => null,
                'DATE_FORMAT(Salepoint.date_u, "%Y-%m-%d")' => $today,
            ],
            'contain' => ['Salepointdetail', 'Client'],
            'order' => ['Salepoint.date desc'],
        ]);
        $this->set(compact('salepoints'));
        $this->layout = false;
    }

    public function activerBonachat($montant, $salepoint_id, $bonachat_id)
    {
        $salepoint = $this->Salepoint->find('first', ['contain' => ['Avance'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        $this->loadModel('Bonachat');
        $bonachat = $this->Bonachat->find('first', [
        'conditions' => ['id' => $bonachat_id], ]);

        if ($bonachat['Bonachat']['montant'] > $salepoint['Salepoint']['total_apres_reduction']) {
            $this->Session->setFlash('le montant du bon d\'achat est superieur au montant du ticket', 'alert-danger');
        } else {
            $this->Salepoint->id = $salepoint_id;
            $this->Salepoint->saveField('mnt_bonachat', $montant);

            $this->Salepoint->id = $salepoint_id;
            $this->Salepoint->saveField('id_bon_achat', $bonachat_id);
            $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');
        }

        return $this->redirect($this->referer());
    }

    public function activerChequecadeau($montant, $salepoint_id, $chequecad_id)
    {
        $salepoint = $this->Salepoint->find('first', ['contain' => ['Avance'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        
        // Dans les étapes de synchronisation des cheques cadeaux
        // - 1 | Collecter les données des chèques-cadeaux.
        // - 2 | Enregistrez-les dans la table locale.
        // - 3 | Récupérer  les données de cette table en fonction de la condition liée à l'identifiant du chèque-cadeau.

            // Récupérer les informations de l'application 
            $result = $this->requestAction(array('controller' => 'Configs', 'action' => 'Getinfo'));
            $server_link = $result['server_link'];
            $caisse_id      = $result['caisse_id'];
            $store_id       = $result['store_id'];

            $link_api = $server_link.'/Pos/apiGetCHEQUESync/';  // DEPUIS LE SERVEUR PROD 
        //    $link_api = 'http://localhost/JCOLLAB/JCOLLAB4X/Pos/apiGetCHEQUESync'; 
            $json_data = file_get_contents($link_api);

            if ($json_data === false) {
                // Le fichier n'a pas été trouvé, définir un message d'erreur personnalisé
                $this->Session->setFlash("Impossible de récupérer les données du fichier", 'alert-danger');
            } else {
                $chequecadeaus = json_decode($json_data, true);
                $this->set('chequecadeaus', $chequecadeaus);

                $this->loadModel('Chequecadeau');
                foreach ($chequecadeaus as $chequecadeauData) {
                    try {
                        // Créez un nouvel enregistrement du modèle Chequecadeau
                        $newChequecadeau = $this->Chequecadeau->create();
                
                        // Assurez-vous que les données sont définies dans le tableau $chequecadeauData
                        if (!empty($chequecadeauData)) {
                            // Forcez la colonne 'id' à prendre la valeur de l'array
                            $newChequecadeau['Chequecadeau']['id'] = $chequecadeauData['Chequecadeau']['id'];
                
                            // Forcez la colonne 'reference' à prendre la valeur de l'array
                            $newChequecadeau['Chequecadeau']['reference'] = $chequecadeauData['Chequecadeau']['reference'];
                
                            // Les autres données du tableau $chequecadeauData dans le modèle
                            $newChequecadeau['Chequecadeau']['date_debut'] = $chequecadeauData['Chequecadeau']['date_debut'];
                            $newChequecadeau['Chequecadeau']['date_fin'] = $chequecadeauData['Chequecadeau']['date_fin'];
                            $newChequecadeau['Chequecadeau']['validite'] = $chequecadeauData['Chequecadeau']['validite'];
                            $newChequecadeau['Chequecadeau']['montant'] = $chequecadeauData['Chequecadeau']['montant'];
                            $newChequecadeau['Chequecadeau']['active'] = $chequecadeauData['Chequecadeau']['active'];                    
               
                            $newChequecadeau['Chequecadeau']['numero'] = $chequecadeauData['Chequecadeau']['numero'];   
                            $newChequecadeau['Chequecadeau']['user_c'] = $chequecadeauData['Chequecadeau']['user_c'];   
                            $newChequecadeau['Chequecadeau']['date_c'] = $chequecadeauData['Chequecadeau']['date_c'];   
                            $newChequecadeau['Chequecadeau']['deleted'] = $chequecadeauData['Chequecadeau']['deleted'];   
                            $newChequecadeau['Chequecadeau']['client_id'] = $chequecadeauData['Chequecadeau']['client_id'];   
                            $newChequecadeau['Chequecadeau']['ref_ticket'] = $chequecadeauData['Chequecadeau']['ref_ticket'];   

                            if ($this->Chequecadeau->save($newChequecadeau)) {
                                echo "Données insérées avec succès.";

                                // Affectation des variables 
                                $chequecadeauData_REF = $chequecadeauData['Chequecadeau']['reference'];
                                $chequecadeauData_MNT = $chequecadeauData['Chequecadeau']['montant'];

                                // Les valeurs dans les sessions
                                $this->Session->write('chequecadeauData_REF', $chequecadeauData_REF);
                                $this->Session->write('chequecadeauData_MNT', $chequecadeauData_MNT);



                            } else {
                                echo "Erreur lors de l'insertion des données.";
                            }
                        }
                    } catch (Exception $e) {
                        echo "Erreur lors de l'insertion des données : " . $e->getMessage();
                    }
                }
                
                
                // récupérer à nouveau les données du chèque en question (refrence)
                $chequecadeau = $this->Chequecadeau->find('first', [
                    'conditions' => ['id' => $chequecad_id], ]
                );
            }
      

        // if ($chequecadeau['Chequecadeau']['montant'] > $salepoint['Salepoint']['total_apres_reduction']) {
        //     $this->Session->setFlash('le montant du cheque cadeau est superieur au montant du ticket', 'alert-danger');
        // } else {
            $this->Salepoint->id = $salepoint_id;
            $this->Salepoint->saveField('mnt_chequecad', $montant);
            $this->Salepoint->id = $salepoint_id;
            $this->Salepoint->saveField('id_cheque_cad', $chequecad_id);
            $this->Session->setFlash('Votre chèque-cadeau a été chargé avec succès.. [MS1-MD130]', 'alert-success');
        //}

        return $this->redirect($this->referer());
    }


        // Recupérer les cheques depuis le serveur 
	public function apiGetCHEQUESync($salepoint_id = null)
	{
        $today = date('Y-m-d');
        $this->loadModel('Chequecadeau');
		// Définir le type de réponse
		$this->response->type('json');

        // Récupérer les données de la table "chequecadeaus"
        $chequecadeaus = $this->Chequecadeau->find('all', [
            'conditions' => [
                'Chequecadeau.active' => 1,
                'DATE_FORMAT(Chequecadeau.date_fin, "%Y-%m-%d") >=' => $today,
                'Chequecadeau.date_encaissement' => null
            ],
            'order' => ['Chequecadeau.date_fin desc'],
        ]);

		// Afficher les données en format JSON
		echo json_encode($chequecadeaus);
        
		// Arrêter le rendu de la vue
		return $this->response;
	}

    public function chequecadeaus($salepoint_id = null)
    {
        $today = date('Y-m-d');
        $this->loadModel('Chequecadeau');
        $chequecadeaus = $this->Chequecadeau->find('all', [
            'conditions' => [
                'OR' => [
                'Chequecadeau.active' => 0,
                'Chequecadeau.date_encaissement' => null,
                [
                'Chequecadeau.active' => 1,
                'DATE_FORMAT(Chequecadeau.date_fin, "%Y-%m-%d") >=' => $today, ], ],
                'Chequecadeau.date_encaissement' => null,
            ],
            'order' => ['Chequecadeau.date_fin desc'],
        ]);

        $this->set(compact('chequecadeaus', 'salepoint_id'));
        $this->layout = false;
    }

    public function bonachats($salepoint_id = null)
    {
        $today = date('Y-m-d');
        $this->loadModel('Bonachat');
        $bonachats = $this->Bonachat->find('all', [
            'conditions' => [
                'OR' => [
                'Bonachat.active' => 0,
                'Bonachat.date_encaissement' => null,
                [
                'Bonachat.active' => 1,
                'DATE_FORMAT(Bonachat.date_fin, "%Y-%m-%d") >=' => $today, ], ],
                'Bonachat.date_encaissement' => null,
            ],
            'order' => ['Bonachat.date_fin desc'],
        ]);

        $this->set(compact('bonachats', 'salepoint_id'));
        $this->layout = false;
    }

    public function changePaymentMode()
    {
        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $today = date('Y-m-d');

        $salepoints = $this->Salepoint->find('all', [
            'conditions' => [
                'Salepoint.etat' => 2,
                'Salepoint.store' => $selected_store,
                'Salepoint.ecommerce_id' => null,
                'Salepoint.commande_id' => null,
                'DATE_FORMAT(Salepoint.date_u, "%Y-%m-%d")' => $today,
            ],
            'contain' => ['Salepointdetail', 'Client', 'Avance'],
            'order' => ['Salepoint.date desc'],
        ]);
        $this->set(compact('salepoints'));
        $this->layout = false;
    }

    public function changeModes($salepoint_id = null)
    {
        $this->request->data = $this->Salepoint->find('first', ['contain' => ['Avance'], 'conditions' => ['Salepoint.id' => $salepoint_id]]);
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $modes = ['espece' => 'Espèces', 'cod' => 'cod',
         'wallet' => 'Wallet', 'bon_achat' => "Bon d'achat", 'cheque_cadeau' => 'Chèque cadeau',
         'Carte' => 'Carte', 'tpe' => 'Tpe', 'virement' => 'Virement', 'cmi' => 'CMI', 'offert' => 'Offert', 'cheque' => 'Chèque', ];
        $modes2 = ['espece' => 'Espèces', 'cod' => 'cod',
         'wallet' => 'Wallet', 'bon_achat' => "Bon d'achat", 'cheque_cadeau' => 'Chèque cadeau',
         'Carte' => 'Carte', 'tpe' => 'Tpe', 'virement' => 'Virement', 'cmi' => 'CMI', 'offert' => 'Offert', 'cheque' => 'Chèque', ];
        $this->set(compact('details', 'modes'));
        $this->layout = false;
    }

    public function updateAvance($avance_id = null)
    {
        $salepoint = $this->Salepoint->find('first', ['contain' => ['Avance'], 'conditions' => ['Salepoint.id' => $this->request->data['Salepoint']['id']]]);

        $cmp = [];
        $cmp['Avance'][0]['id'] = $salepoint['Avance'][0]['id'];
        $cmp['Avance'][0]['montant'] = $salepoint['Avance'][0]['montant'];
        $cmp['Avance'][0]['mode'] = $salepoint['Avance'][0]['mode'];
        $cmp['Avance'][1]['id'] = $salepoint['Avance'][1]['id'];
        $cmp['Avance'][1]['montant'] = $salepoint['Avance'][1]['montant'];
        $cmp['Avance'][1]['mode'] = $salepoint['Avance'][1]['mode'];
        //var_dump($this->request->data["Avance"],$cmp["Avance"]);die();
        if ($this->request->data['Avance'] != $cmp['Avance']) {
            if (strpos($salepoint['Salepoint']['reference'], 'T09') !== false) {
                $old_ref = explode('-', $salepoint['Salepoint']['reference']);
                $this->loadModel('Caisse');
                $caisse = $this->Caisse->find('first', ['conditions' => ['prefix' => $old_ref[0]]]);
                $num_compteur = $caisse['Caisse']['numero'];
                $num_compteur = (int) $num_compteur;
                $new_ref = $old_ref[0].'-'.str_pad($num_compteur + 1, 6, '0', STR_PAD_LEFT);
                $this->Salepoint->updateAll(['Salepoint.reference' => '"'.$new_ref.'"'], ['Salepoint.id' => $this->request->data['Salepoint']['id']]);
                $this->Caisse->updateAll(['Caisse.numero' => str_pad($num_compteur + 1, 6, '0', STR_PAD_LEFT)], ['Caisse.id' => $caisse['Caisse']['id']]);
            }
            $this->loadModel('Avance');
            $this->Avance->id = $this->request->data['Avance'][0]['id'];
            $this->Avance->save($this->request->data['Avance'][0]);
            $this->Avance->id = $this->request->data['Avance'][1]['id'];
            $this->Avance->save($this->request->data['Avance'][1]);
        }
        $this->Session->setFlash('L\'action a été effectué avec succès.', 'alert-success');

        return $this->redirect($this->referer());
    }

    public function salepointdetails($salepoint_id = null)
    {
        $this->request->data = $this->Salepoint->find('first', ['conditions' => ['Salepoint.id' => $salepoint_id]]);
        $details = $this->Salepoint->Salepointdetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Salepointdetail.salepoint_id' => $salepoint_id]]);
        $this->set(compact('details'));
        $this->layout = false;
    }

    public function double_screen($salepoint_id = null)
    {
        $this->layout = 'pos';
    }    
 
    // synchronisation Vente POS => serveur
    function syncmanuel($store_id = null)
    {
        // Récupérer les informations de l'application 
        $result         = $this->requestAction(array('controller' => 'Configs', 'action' => 'Getinfo'));
        $server_link    = $result['server_link'];
        $caisse_id      = $result['caisse_id'];
        $store_id       = $result['store_id'];

        // Donnée : T_SALEPOINT et T_SALEPOINTDETAIL ---------------------------------

        // Récupérer les données de la table "Salepoint"
        $salepoints_ready_togo = $this->Salepoint->find('all',[
            'contain' => [
                'Salepointdetail'=>[
                    //'fields' => array_diff(array_keys($this->Salepointdetail->getColumnTypes()), ['id']),
                    'conditions' => [
                        'Salepointdetail.deleted'=>0,
                        ],
                    ],
                'Avance'=>[
                    'conditions' => [
                        'Avance.deleted'=>0,
                    ],
                ]
            ],
            'conditions' => [
                'Salepoint.sync' => 0, // 0 = les elements non sync
                'Salepoint.store' => $store_id,
                'Salepoint.caisse_id' => $caisse_id,
                'NOT' => ['Salepoint.reference LIKE' => '%Prov%'],
            ]
        ]);


        // Donnée : T_ENTREE  ---------------------------------
        // Récupérer tous les dépôts du magasin
        $store = $this->Store->find('first', array(
            'conditions' => array('Store.id' => $store_id),
            'contain' => array(
                'Depot' => array(
                    'conditions' => array('Depot.vente' => 1)
                )
                )
        ));
        $depot_id = $store['Depot'][0]['id'];

        // Récupérer toutes les entrées liées aux dépôts du magasin
        $entrees = $this->Entree->find('all', array(
            'conditions' => array(
                                'Entree.depot_id' =>$depot_id, 
                                'sync'=> 0,
                                'type'=> 'Sortie'
                                ),
        ));

       // Connexion à la deuxième base de données  *******
       $this->Salepoint->setDataSource('sync_server');
       $this->Salepointdetail->setDataSource('sync_server');
       $this->Avance->setDataSource('sync_server');
       $this->Entree->setDataSource('sync_server');

        // Enregistrer les informations T_SALEPOINT et T_SALEPOINTDETAIL
        $salepoint_ids = [];
        foreach ($salepoints_ready_togo as $k => $data) {
            // Récupération des identifiants des ventes
            $salepoint_ids[ $data['Salepoint']['id'] ] = $data['Salepoint']['id']; 
            // Sauvegarde des données dans la deuxiéme base *******
            $this->Salepoint->saveAssociated($data);
        }

        // Enregistrer les informations T_ENTREE
        foreach ($entrees as $entree) {
            $entree['Entree']['sync'] = 1;
            $this->Entree->save($entree);
        }
        
        // Retour à la première base de données
        $this->Salepoint->setDataSource('default');
        $this->Entree->setDataSource('default');

        if (!empty($salepoint_ids) ) {
           // Mettre à jour l'etat de sync = 1
            if ( $this->Salepoint->updateAll(['Salepoint.sync' => 1], ['Salepoint.id' => $salepoint_ids]) ) {
                
                // Si les ventes sonts Synchronisé : 

                    //0 - Mettre à jour la colonne Entree_sync = 1
                    $this->Entree->updateAll(['Entree.sync' => 1]);
                
                    //1 - Insérer la trace dans la table Synchronisations
                    $this->loadModel('Synchronisation');
                    $this->Synchronisation->save(array(
                        'source' => $server_link,
                        'module' => "Vente POS",
                        'user_created' => 0,
                        'destination' => 'Store='.$store_id.' Caisse='.$caisse_id,
                    ));

                    //2 - Afficher un message d'information
                    $this->Session->setFlash('Les données de vente sont correctement synchronisées.', 'alert-success');
            }
        }

        return $this->redirect($this->referer());
       // $this->layout = false;
    }

    public function getsalestosync($store_id = null, $caisse_id = null, $salepoint_id = null)
    {
        $store_id = $this->Session->read('Auth.User.StoreSession.id');
        $caisse_id = $this->Session->read('caisse_id');

        // Récupérer les données de la table "Salepoint"
        $salepoints = $this->Salepoint->find('all',[
            'conditions' => [
                'Salepoint.sync' => 0, // 0 = les elements non sync
                'Salepoint.store' => $store_id,
                'Salepoint.caisse_id' => $caisse_id,
                'NOT' => ['Salepoint.reference LIKE' => '%Prov%'],
                'Salepoint.deleted' => 0,
            ]
        ]);

        $this->set('salepoints', $salepoints);
        $this->layout = false;
    }


        // synchronisation Ventes ECOM => Serveur
        public function syncmanuelecom($store_id = null)
        {
            // Récupérer les informations de l'application 
            $result = $this->requestAction(array('controller' => 'Configs', 'action' => 'Getinfo'));
            $server_link    = $result['server_link'];
            $caisse_id      = $result['caisse_id'];
            $store_id       = $result['store_id'];

            // Récupérer les données de la table "Salepoint"
            $salepoints_ready_togo = $this->Ecommerce->find('all',[
                'fields' => array_diff(array_keys($this->Ecommerce->getColumnTypes()), ['id']),
                'contain' => [
                    'Ecommercedetail'=>[
                        'fields' => array_diff(array_keys($this->Ecommercedetail->getColumnTypes()), ['id']),
                        'conditions' => ['Ecommercedetail.deleted'=>0],
                    ],
                    'Client'=>[
                        'fields' => 'Client.*',
                        'conditions' => ['Client.deleted'=>0],
                    ]
                ],
                'conditions' => [
                    'Ecommerce.sync' => 0, // 0 = les elements non sync
                    'Ecommerce.store_id' => $store_id,
                    'Ecommerce.etat' => 2, // que les etats traités
                ]
            ]);

            // Connexion à la deuxième base de données  *******
            $this->Ecommerce->setDataSource('sync_server');
            $this->Ecommercedetail->setDataSource('sync_server');
            $this->Client->setDataSource('sync_server');

            $salepoint_ids = [];
            foreach ($salepoints_ready_togo as $k => $data) {
                // Récupération des identifiants des ventes
                $salepoint_ids[ $data['Ecommerce']['id'] ] = $data['Ecommerce']['id']; 

                    // Vérifier si le client existe déjà dans la base de données
                    $client = $this->Client->find('first', [
                        'conditions' => [
                            'Client.email' => $data['Client']['email'],
                            'Client.deleted' => 0
                        ]
                    ]);

                    if (!$client) {
                        // Si le client n'existe pas, l'enregistrer dans la base de données
                        unset($data['Ecommerce']['id']);
                        unset($data['Client']['id']);
                        $data['Client']['deleted'] == 0; //  cannot be null
                        $this->Ecommerce->saveAssociated($data);
                    } else {
                        // Si le client existe déjà, mettre à jour l'ID du client dans les données de vente
                        $data['Ecommerce']['client_id'] = $client['Client']['id'];
                        unset($data['Ecommerce']['id']);
                        $this->Ecommerce->saveAssociated($data);
                    }
            }

            // Retour à la première base de données
            $this->Ecommerce->setDataSource('default');
    
            if (!empty($salepoint_ids)) {
               // tableau des champs à mettre à jour : 1  Synchronisé 
                if ( $this->Ecommerce->updateAll(['Ecommerce.sync' => 1], ['Ecommerce.id' => $salepoint_ids]) ) { 

                // Si les ventes sonts Synchronisé
                    //1 - Insérer la trace dans la table Synchronisations
                    $this->loadModel('Synchronisation');
                    $this->Synchronisation->save(array(
                    'source' => $server_link,
                    'module' => "Vente POS",
                    'user_created' => 0,
                    'destination' => 'Store='.$store_id.' Caisse='.$caisse_id,
                    ));
                    // 2 - Afficher un message d'information
                    $this->Session->setFlash('Les données de vente ECOM sont correctement synchronisées.', 'alert-success');
                }
            }
    
           return $this->redirect($this->referer());
           $this->layout = false;
        }





        public function wallet($salepoint_id = null)
    {
        // Récupérer les informations de l'application 
        $result = $this->requestAction(array('controller' => 'Configs', 'action' => 'Getinfo'));
        $server_link = $result['server_link'];
        $caisse_id      = $result['caisse_id'];
        $store_id       = $result['store_id'];

        $link_api = $server_link.'/Pos/apiGetWALLET/';  // DEPUIS LE SERVEUR PROD 
        // $link_api = 'http://localhost/JCOLLAB/JCOLLAB4X/Pos/apiGetWALLET'; 
        $json_data = file_get_contents($link_api);

        if ($json_data === false) {
            // Le fichier n'a pas été trouvé, définir un message d'erreur personnalisé
            $this->Session->setFlash("Impossible de récupérer les données du fichier WALLET", 'alert-danger');
        } else {
            $walletsData = json_decode($json_data, true);
            $this->set('walletsData', $walletsData);
        }

        $this->set(compact('walletsData', 'salepoint_id', 'designation'));
        $this->layout = false;
    }

    // Recupérer la liste des WALLET cheques depuis le serveur 
    public function apiGetWALLET($salepoint_id = null)
    {
        $today = date('Y-m-d');
        $this->loadModel('Wallet');
        $this->loadModel('Client');
        // Définir le type de réponse
        $this->response->type('json');

        $walletsData = $this->Wallet->find('all', [
            'conditions' => ['Wallet.deleted' => 0],
            'contain' => [
                'Client' => [
                    'fields' => ['designation']
                ]
            ]
        ]);
        
        $this->set('walletsData', $walletsData);

        // Afficher les données en format JSON
        echo json_encode($walletsData);
        
        // Arrêter le rendu de la vue
        return $this->response;
    }


        public function loadWalletDirect($id, $client_id, $montant, $wallet_id, $reference )
    {
        // 1- Affectationde payment_method & client_id
        $this->Salepoint->id = $id;
        $this->Salepoint->saveField('client_id', $client_id);
        $this->Salepoint->saveField('payment_method', "wallet");
        
        // 2- Création de Wallet
        $this->loadModel('Wallet');

        // Vérifiez si un enregistrement avec le même wallet_id existe
        $existingWallet = $this->Wallet->find('first', array(
            'conditions' => array('reference' => $reference)
        ));

        if ($existingWallet) {
            // Mise à jour du montant si l'enregistrement existe déjà
            $existingWallet['Wallet']['montant'] = $montant;
            if ($this->Wallet->save($existingWallet)) {
                // Mise à jour réussie
                $this->Session->setFlash('Le montant du wallet client a été mis à jour avec succès.. [MS2-MD130-loadWalletDirect]', 'alert-success');

                // Affectation des varibles sessions
                $client_id = $existingWallet['Wallet']['client_id'];
                $montant = $existingWallet['Wallet']['montant'];
                $wallet_id = $existingWallet['Wallet']['id'];
                $reference = $existingWallet['Wallet']['reference'];

                $this->Session->write('client_id', $client_id);
                $this->Session->write('montant', $montant);
                $this->Session->write('wallet_id', $wallet_id);
                $this->Session->write('reference', $reference);

            } else {
                // Erreur lors de la mise à jour
                $this->Session->setFlash('Il y a eu une erreur lors de la mise à jour du montant [MS3-MD130-loadWalletDirect]', 'alert-danger');
            }

        } else {
            // Créez un nouvel enregistrement si le wallet_id n'existe pas
            $data = array(
                'client_id' => $client_id,
                'montant' => $montant,
                'wallet_id' => $wallet_id
            );

            if (!empty($client_id) && !empty($montant) && !empty($wallet_id)) {
                $this->Session->write('client_id', $client_id);
                $this->Session->write('montant', $montant);
                $this->Session->write('wallet_id', $wallet_id);
                $this->Session->write('reference', $reference);
            }


            if ($this->Wallet->save($data)) {
                // Enregistrement réussi
                $this->Session->setFlash('Le wallet client a été créé avec succès.. [MS4-MD130-loadWalletDirect]', 'alert-success');   
            } else {
                // Erreur lors de la création
                $this->Session->setFlash('Il y a eu une erreur lors de la création du wallet client [MS4-MD130-loadWalletDirect]', 'alert-danger');
            }
        }

        return $this->redirect($this->referer());
    }






    // FOR TESTE : API link
    public function API_GET_DATA_NON_SYNC_FROM_SALE()
    {
        $link_api = Router::url(array(
            'controller' => 'Apisync',
            'action' => 'apiGetSalesToSync',
        ), true);

        $json_data = file_get_contents($link_api);
        $data = json_decode($json_data, true);
        $this->set('data', $data);

        $this->layout = false;
    }



    // Website: Récupérer les commandes en attente
    public function SaveOrdersFromApi() {
        $this->loadModel('Ecommerce'); 
        $this->loadModel('Ecommercedetail'); 
        $this->loadModel('Client');
        $this->loadModel('Produit');
    
        $this->autoRender = false;
    
        echo "Début du script<br>";
    
        // Effectuer la requête vers l'API pour récupérer les commandes
        $response = $this->callApi();
    
        echo "Vérification de la réponse API<br>";
        
        if (!isset($response['data'][0])) { 
            die("Erreur: Données API introuvables");
        }
    
        echo "Données API trouvées, traitement en cours...<br>";
    
        $orderIds = [];
        
        foreach ($response['data'][0] as $order) {
            if (!isset($order['id'], $order['date_created'], $order['payment_method'], $order['shipment'], $order['customer'], $order['line_items'])) {
                die("Erreur: Données de commande incomplètes");
            }
    
            echo "Traitement de la commande ID: " . $order['id'] . "<br>";
    
            // Gestion du client
            $customerData = [
                'id_ecommerce' => $order['customer']['id'],
                'designation' => $order['customer']['name'],
                'telmobile' => $order['customer']['phone'],
                'email' => $order['customer']['email'],
                'adresse' => $order['customer']['address'],
            ];
    
            $existingCustomer = $this->Client->find('first', [
                'conditions' => ['Client.id_ecommerce' => $customerData['id_ecommerce']]
            ]);
    
            if ($existingCustomer) {
                $this->Client->id = $existingCustomer['Client']['id'];
                // Mise à jour du total_vente
                $totalVente = $existingCustomer['Client']['total_vente'] + $order['total'];
                $customerData['total_vente'] = $totalVente;
            } else {
                $this->Client->create();
                $customerData['total_vente'] = $order['total'];
            }
            
            if ($this->Client->save($customerData)) {
                $customerId = $this->Client->id;
                echo "Client enregistré avec ID: " . $customerId . "<br>";
            } else {
                echo "Échec d'enregistrement du client: ";
                debug($this->Client->validationErrors);
                die();
            }
    
            // Enregistrement de la commande
            $this->Ecommerce->create();
            $ecommerceData = [
                'barcode' => $order['id'],
                'online_id' => $order['id'],
                'fee' => isset($order['fee']) ? $order['fee'] : "0.00",
                'shipment' => $order['shipment'],

                // si $order['payment_method'] = Stripe => 'Carte' sinon donne moi la valeur de $order['payment_method']
                'payment_method' => ($order['payment_method'] == 'stripe') ? 'cmi' : $order['payment_method'],
                
                'adresse' => $order['customer']['address'],
                'date' => date('Y-m-d', strtotime($order['date_created'])),
                'store_id' => 1,
                'statut' => 'confirmed',
                'client_id' => $customerId,
                'total_qte' => array_sum(array_column($order['line_items'], 'quantity')),
                'total_a_payer_ttc' => array_sum(array_map(function($item) {
                    return isset($item['unit_price']) ? $item['unit_price'] * $item['quantity'] : 0;
                }, $order['line_items'])),
            ];
    
            if ($this->Ecommerce->save($ecommerceData)) {
                $ecommerceId = $this->Ecommerce->id; 
                echo "Commande enregistrée avec ID: " . $ecommerceId . "<br>";
                $orderIds[] = $order['id'];
    
                foreach ($order['line_items'] as $lineItem) {
                    $this->Ecommercedetail->create();
                    
                $productCodeBarreAPI =  $lineItem['product_id'];         
                $productId = $this->Produit->field('id', ['code_barre' => $productCodeBarreAPI]);

                    $ecommerceDetailData = [
                        'unit_price' => $lineItem['unit_price'],
                        'prix_vente' => $lineItem['unit_price'],
                        'produit_id' =>  $productId,
                        'online_id' => $lineItem['id'],
                        'qte_cmd' => $lineItem['quantity'],
                        'qte_ordered' => $lineItem['quantity'],
                        'variation_id' => $lineItem['weight_ordered'],
                        'ecommerce_id' => $ecommerceId,   
                    ];

                    if ($this->Ecommercedetail->save($ecommerceDetailData)) {
                        echo "Détail enregistré pour Code barre : " . $lineItem['product_id'] . " produit ID: " . $productId . "<br>";
                    } else {
                        echo "Échec d'enregistrement du détail produit: ";
                        debug($this->Ecommercedetail->validationErrors);
                        die();
                    }
                }
            } else {
                echo "Échec d'enregistrement de la commande: ";
                debug($this->Ecommerce->validationErrors);
                die();
            }
        }
    
        if (!empty($orderIds)) {
            $this->confirmOrders($orderIds); // in progress 
        }    
        
        if (!empty($orderIds) && is_array($orderIds)) {
            // 1) Instanciation du contrôleur une seule fois
            App::uses('EcommercesController', 'Controller');
            $Ecommerces = new EcommercesController();
            $Ecommerces->constructClasses();
        
            // 2) Boucle directement sur ton tableau d’IDs
            foreach ($orderIds as $orderId) {
                $Ecommerces->changeStatus($orderId, 'confirmed');
            }
        }

        $this->layout = false;
    }
    
    // Fonction pour confirmer les commandes
    public function confirmOrders($orderIds) {
        $this->autoRender = false;
    
        $parametres = $this->GetParametreSte();
        $url = "https://lafonda.ae/rest/api/orders/confirm-orders";
        $user = $parametres['User'];
        $password = $parametres['Password'];
    
        $data = json_encode(['order_ids' => $orderIds]);    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($user . ':' . $password)
        ]);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            echo "Erreur lors de la confirmation des commandes: " . curl_error($ch);
        } else {
            echo "Commandes confirmées: " . $response;
        }
    
        curl_close($ch);
    }

        // Fonction pour appeler l'API en GET avec authentification
    public function callApi() {
        $this->autoRender = false;

        $url = "https://lafonda.ae/rest/api/orders/pending?site=1"; // Ajout du paramètre dans l'URL

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // Méthode GET
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('restapi:DSDS@$%^&@#')
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['success' => false, 'error' => curl_error($ch)];
        }

        curl_close($ch);

        return json_decode($response, true);
    }




    public function GetClient($client_id = 2) {
        $this->loadModel('Client');

        return $this->Client->find('first', [
            'conditions' => ['Client.id' => $client_id]
        ]);
    }
    
    
    

    
}
