<?php

App::import('Vendor', 'dompdf', ['file' => 'dompdf'.DS.'dompdf_config.inc.php']);
class EcommercesController extends AppController
{
    public $idModule = 132;

    public function index()
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role_id = $this->Session->read('Auth.User.role_id');
        $depots = $this->Session->read('depots');
        $clients = $this->Ecommerce->Client->findList();
        $users = $this->Ecommerce->User->findList();
        $depots = $this->Ecommerce->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);
        $this->set(compact('users', 'clients', 'depots', 'user_id', 'role_id'));
        $this->getPath($this->idModule);
    }



    public function syncwebsite() {
        $apiUrl = 'https://lafonda-uat.o2usd.net/rest/api/orders/pending';
        $username = 'restapi';
        $password = 'DSDS@$%^&@#';

        // Préparer les données de la requête
        $postData = [
            'site' => 1 // Spécifier l'ID du site
        ];

        // Préparer la requête cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POST, true); // Méthode POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData)); // Ajouter les données JSON

        // Ajouter les en-têtes requis
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Exécuter la requête cURL
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 && $response) {
            $orders = json_decode($response, true); // Convertir JSON en tableau PHP

            // Vérifier si les données sont valides
            if (!empty($orders['data']) && $orders['success'] === true) {
                $this->set('orders', $orders['data']);
            } else {
                $this->Session->setFlash(__('No orders found or invalid response from the API.'));
            }
        } else {
            $this->Session->setFlash(__('Unable to fetch data from the API. HTTP Code: ' . $httpCode));
        }

die(json_encode($httpCode));

        // Fermer la session cURL
        curl_close($ch);
    }





    public function getclient($client_id = null)
    {
        $req = $this->Ecommerce->Client->find('first', ['conditions' => ['id' => $client_id]]);
        $remise = (isset($req['Client']['id']) and !empty($req['Client']['remise'])) ? (float) $req['Client']['remise'] : 0;
        die(json_encode($remise));
    }

    public function loadquatite($depot_id = null, $produit_id = null)
    {
        $req = $this->Ecommerce->Ecommercedetail->Produit->Depotproduit->find('first', ['conditions' => ['depot_id' => $depot_id, 'produit_id' => $produit_id]]);
        $quantite = (isset($req['Depotproduit']['id']) and !empty($req['Depotproduit']['quantite'])) ? (float) $req['Depotproduit']['quantite'] : 0;
        die(json_encode($quantite));
    }

    public function indexAjax()
    {
        $admins = $this->Session->read('admins');
        $user_id = $this->Session->read('Auth.User.id');
        $role_id = $this->Session->read('Auth.User.role_id');
        $conditions = [];
        //if ( !in_array($role_id, $admins) ) $conditions['Ecommerce.user_c'] = $user_id;
        foreach ($this->params['named'] as $param_name => $value) {
            if (!in_array($param_name, ['page', 'sort', 'direction', 'limit'])) {
                if ($param_name == 'Ecommerce.barcode') {
                    $conditions['Ecommerce.barcode LIKE '] = "%$value%";
                } elseif ($param_name == 'Ecommerce.date1') {
                    $conditions['Ecommerce.date >='] = date('Y-m-d', strtotime($value));
                } elseif ($param_name == 'Ecommerce.date2') {
                    $conditions['Ecommerce.date <='] = date('Y-m-d H:i:s', strtotime($value.' 23:59:59'));
                } else {
                    $conditions[$param_name] = $value;
                    $data['Filter'][$param_name] = $value;
                }
            }
        }

        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $conditions['Ecommerce.store_id'] = $selected_store;

        $this->Ecommerce->recursive = -1;
        $this->Paginator->settings = [
            'contain' => ['Creator', 'Depot', 'User', 'Client'],
            'order' => ['Ecommerce.etat' => 'asc', 'Ecommerce.date_u' => 'desc'],
            'conditions' => $conditions,
        ];
        $taches = $this->Paginator->paginate();
        $ventes = $this->Ecommerce->find('all', ['contain' => ['Depot', 'User', 'Client'], 'order' => ['CAST(Ecommerce.barcode as unsigned) desc'], 'conditions' => $conditions]);
        $this->set(compact('taches', 'ventes', 'user_id'));
        $this->layout = false;
    }

    // API GET ECOM : Synconisation des ECOM vers la caisse demanderesse
    public function apiEcomSync()
    {
        // Définir le type de réponse
		$this->response->type('json');
        $conditions = [];
        //if ( !in_array($role_id, $admins) ) $conditions['Ecommerce.user_c'] = $user_id;
        foreach ($this->params['named'] as $param_name => $value) {
            if (!in_array($param_name, ['page', 'sort', 'direction', 'limit'])) {
                if ($param_name == 'Ecommerce.barcode') {
                    $conditions['Ecommerce.barcode LIKE '] = "%$value%";
                } elseif ($param_name == 'Ecommerce.date1') {
                    $conditions['Ecommerce.date >='] = date('Y-m-d', strtotime($value));
                } elseif ($param_name == 'Ecommerce.date2') {
                    $conditions['Ecommerce.date <='] = date('Y-m-d H:i:s', strtotime($value.' 23:59:59'));
                } else {
                    $conditions[$param_name] = $value;
                    $data['Filter'][$param_name] = $value;
                }
            }
        }

        $selected_store = $this->Session->read('Auth.User.StoreSession.id');
        $conditions['Ecommerce.store_id'] = $selected_store;

        $this->Ecommerce->recursive = -1;
        $ventes = $this->Ecommerce->find('all', ['contain' => ['Depot', 'User', 'Client'], 'order' => ['CAST(Ecommerce.barcode as unsigned) desc'], 'conditions' => $conditions]);
        $this->set(compact('taches', 'ventes', 'user_id'));



        // Afficher les données en format JSON
		echo json_encode($ventes);
        
		// Arrêter le rendu de la vue
		return $this->response;
    }

    public function edit($id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role_id = $this->Session->read('Auth.User.role_id');
        $depots = $this->Session->read('depots');
        $this->set(compact('users', 'clients', 'user_id', 'role_id'));
        if ($this->request->is(['post', 'put'])) {
            $this->request->data['Ecommerce']['active_remise'] = (isset($this->request->data['Ecommerce']['active_remise']) and !empty($this->request->data['Ecommerce']['active_remise'])) ? 1 : -1;
            if ($this->request->data['Ecommerce']['active_remise'] == -1) {
                $this->request->data['Ecommerce']['remise'] = 0;
            }
            if ($this->Ecommerce->save($this->request->data)) {
                if (isset($this->request->data['Ecommerce']['id']) and !empty($this->request->data['Ecommerce']['id'])) {
                    $this->CheckRemise($this->request->data['Ecommerce']['id']);
                }
                $this->Session->setFlash('L\'enregistrement a été effectué avec succès.', 'alert-success');

                return $this->redirect(['action' => 'view', $this->Ecommerce->id]);
            } else {
                $this->Session->setFlash('Il y a un problème', 'alert-danger');

                return $this->redirect(['action' => 'index']);
            }
        } elseif ($this->Ecommerce->exists($id)) {
            $options = ['conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $id]];
            $this->request->data = $this->Ecommerce->find('first', $options);
        }

        $clients = $this->Ecommerce->Client->findList();
        $users = $this->Ecommerce->User->findList();
        $depots = $this->Ecommerce->Depot->findList(['Depot.vente' => 1, 'Depot.id' => $depots]);
        $this->set(compact('users', 'clients', 'depots', 'user_id', 'role_id'));
        $this->layout = false;
    }

    public function MotifsAbandon($barcode, $id)
    {
        if ($this->request->is(['post', 'put'])) {
            $this->loadModel('Motif');
            if ($this->Motif->save($this->request->data)) {
                $ecommerce = $this->Ecommerce->find('first', ['conditions' => ['id' => $id]]);
                $online_id = $ecommerce['Ecommerce']['online_id'];
                $ch = curl_init();

                $headers = [
                    'Content-Type:application/json',
                ];
                $store_id = $this->Session->read('Auth.User.StoreSession.id');
                $this->loadModel('Store');
                $storeSession = $this->Store->find('first', ['conditions' => ['Store.id' => $store_id], 'contain' => ['Societe']]);
                $id_ecommerce = $storeSession['Store']['id_ecommerce'];
                $data = [];
                $data['site'] = intval($id_ecommerce);
                $data['id'] = $online_id;
                $parametres = $this->GetParametreSte();
                $url = $parametres['Api Abondan total'];

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_USERPWD => $parametres['User'].':'.$parametres['Password'],
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $return = curl_exec($ch);
                curl_close($ch);
                $return = json_decode($return, true);

                if ($return['message'] != 'Order returned.') {
                    $this->Session->setFlash('problème Api', 'alert-danger');

                    return $this->redirect(['action' => 'index']);
                }
                ///////////////////

                $this->Ecommerce->updateAll(['Ecommerce.total_apres_reduction' => 0,
            'Ecommerce.total_a_payer_ttc' => 0,
            'Ecommerce.total_a_payer_ht' => 0, ], ['Ecommerce.id' => $id]);

                $this->loadModel('Salepoint');
                $this->Salepoint->updateAll(['Salepoint.total_apres_reduction' => 0,
            'Salepoint.total_a_payer_ttc' => 0, 'Salepoint.total_paye' => 0,
            'Salepoint.montant_tva' => 0, 'Salepoint.total_a_payer_ht' => 0, ], ['Salepoint.ecommerce_id' => $id]);

                $this->loadModel('Salepoint');
                $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.ecommerce_id' => $id]]);

                $this->loadModel('Salepoint');
                $this->Salepoint->Avance->updateAll(['Avance.montant' => 0], ['Avance.salepoint_id' => $salepoint['Salepoint']['id'], 'Avance.montant !=' => 0]);

                $details = $this->Ecommerce->Ecommercedetail->find('all', [
                    'conditions' => ['Ecommercedetail.ecommerce_id' => $id],
                    'contain' => ['Produit'],
                ]);

                for ($i = 0; $i < sizeof($details); ++$i) {
                    $this->Ecommerce->create();
                    $this->Ecommerce->Ecommercedetail->updateAll(
                        ['Ecommercedetail.ttc' => 0,
                    'Ecommercedetail.qte' => 0, ],
                        ['Ecommercedetail.id' => $details[$i]['Ecommercedetail']['id']]
                    );

                    $this->loadModel('Salepointdetail');
                    $this->Salepointdetail->create();
                    $this->Salepointdetail->updateAll(
                        ['Salepointdetail.ttc' => 0,
                    'Salepointdetail.qte' => 0, ],
                        ['Salepointdetail.ecommercedetail_id' => $details[$i]['Ecommercedetail']['id']]
                    );
                }
                ////////////////
                $this->Ecommerce->id = $id;
                if ($this->Ecommerce->saveField('etat', 4)) {
                    $this->Session->setFlash('L\'enregistrement a été effectué avec succès.', 'alert-success');

                    return $this->redirect(['action' => 'index']);
                }
                $this->Session->setFlash('Il y a un problème', 'alert-danger');

                return $this->redirect(['action' => 'index']);
            }
        }
        $this->loadModel('Motifsabandon');
        $motifsabandons = $this->Motifsabandon->find('list', ['conditions' => ['deleted' => 0],
        'fields' => ['libelle'], ]);
        $this->set(compact('barcode', 'motifsabandons'));
        $this->layout = false;
    }

    public function delete($id = null)
    {
        if (isset($this->globalPermission['Permission']['s']) and $this->globalPermission['Permission']['s'] == 0) {
            $this->Session->setFlash('Vous n\'avez pas la permission de supprimer !', 'alert-danger');

            return $this->redirect($this->referer());
        }
        $this->Ecommerce->id = $id;
        if (!$this->Ecommerce->exists()) {
            throw new NotFoundException(__('Invalide vente'));
        }

        if ($this->Ecommerce->delete()/* flagDelete() */) {
            $this->Ecommerce->Ecommercedetail->deleteAll(['Ecommercedetail.ecommerce_id' => $id]);
            $this->Session->setFlash('La suppression a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function deletedetail($id = null, $ecommerce_id = null)
    {
        $this->Ecommerce->Ecommercedetail->id = $id;
        if (!$this->Ecommerce->Ecommercedetail->exists()) {
            throw new NotFoundException(__('Invalide article'));
        }

        if ($this->Ecommerce->Ecommercedetail->flagDelete()) {
            $this->calculatrice($ecommerce_id);
            $this->Session->setFlash('La suppression a été effectué avec succès.', 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function AnnulerProduits($ecommerce_id, $produits, $qte)
    {
        $options = ['conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $ecommerce_id]];
        $ecommerce = $this->Ecommerce->find('first', $options);

        $online_id = $ecommerce['Ecommerce']['online_id'];

        $produit2 = $produits;
        $produits = explode(',', $produits);
        $qte = explode(',', $qte);
        $qteD = [];

        for ($i = 0; $i < count($produits); ++$i) {
            $qteD[$produits[$i]] = $qte[$i];
        }
        $details = $this->Ecommerce->Ecommercedetail->find('all', [
            'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id,
            'Ecommercedetail.id' => $produits, ],
            'contain' => ['Produit'],
        ]);

        //$product_barres = [];
        $quantities = [];
        $weight_ordered = [];
        $qteF = [];
        foreach ($details as $detail) {
            $product_barres[] = $detail['Produit']['code_barre'];
            $quantities[] = $detail['Ecommercedetail']['qte_cmd'];
            $weight_ordered[] = $detail['Ecommercedetail']['qte_ordered'];
        }
        $data = [];
        $arr_details = [];

        $this->loadModel('Store');
        $store_id = $this->Session->read('Auth.User.StoreSession.id');
        $storeSession = $this->Store->find('first', ['conditions' => ['Store.id' => $store_id], 'contain' => ['Societe']]);
        $id_ecommerce = $storeSession['Store']['id_ecommerce'];

        $data[0] = [
            'site' => intval($id_ecommerce),
            'id' => intval($online_id),
        ];

        for ($i = 0; $i < sizeof($details); ++$i) {
            $qte2 = $qteD[$details[$i]['Ecommercedetail']['id']];
            if ($details[$i]['Ecommercedetail']['variation_id'] != '0') {
                // Calcul avec KG
                $arr = [
                'id' => $details[$i]['Ecommercedetail']['online_id'],
                'variation_id' => $details[$i]['Ecommercedetail']['variation_id'],
               // "quantity" =>  intval($quantities[$i]),
                'quantity' => $qte2, //intval($quantities[$i]),
                'weight_ordered' => $qte2, //floatval($weight_ordered[$i])
            ];
            } else {
                // Calcul avec pièce
                $arr = [
                'id' => $details[$i]['Ecommercedetail']['online_id'],
                'variation_id' => $details[$i]['Ecommercedetail']['variation_id'],
                // "quantity" =>  intval($quantities[$i]),
                'quantity' => $qte2, //intval($quantities[$i]),
                'quantity_ordered' => $qte2, //intval($quantity_ordered[$i])
            ];
            }

            array_push($arr_details, $arr);
            $data[0]['line_items'] = $arr_details;
        }

        $data = trim(json_encode(array_values($data)), '[]');
        header('Content-Type: application/json; charset=UTF-8');

        //var_dump($data);
        //die();

        $ch = curl_init();

        $parametres = $this->GetParametreSte();
        $url = $parametres['Api annulation partielle'];

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

        /* if($return["message"] == "The order is updated successfully") { */

        // $return["status"] = "success";

        // if($return["message"] == "Order returned.") { // version HAMZA

        if ($return['status'] == 'success') {
            /* $this->Ecommerce->Ecommercedetail->updateAll(['Ecommercedetail.qte'=> 0,'Ecommercedetail.ttc'=> 0],['Ecommercedetail.ecommerce_id'=> $ecommerce_id,
                'Ecommercedetail.id'=> $produits]); */

            $somme = 0;
            $total = 0;
            $diff = 0;
            //ecomdetail --> qte
            $this->loadModel('Salepoint');
            $salepoint = $this->Salepoint->find('first', ['conditions' => ['Salepoint.ecommerce_id' => $ecommerce_id]]);

            for ($i = 0; $i < sizeof($details); ++$i) {
                $prix = $details[$i]['Ecommercedetail']['unit_price'];
                $montant = $prix * $qteD[$details[$i]['Ecommercedetail']['id']];
                $somme += $montant;
                $oldqte = $details[$i]['Ecommercedetail']['qte'] - $qteD[$details[$i]['Ecommercedetail']['id']];
                $oldmontant = $prix * $oldqte;
                $diff += $oldmontant;

                $this->Ecommerce->create();
                $this->Ecommerce->Ecommercedetail->updateAll(['Ecommercedetail.ttc' => $montant,
                'Ecommercedetail.qte' => $qteD[$details[$i]['Ecommercedetail']['id']], ],
                ['Ecommercedetail.id' => $details[$i]['Ecommercedetail']['id']]);

                $this->loadModel('Salepointdetail');
                $this->Salepointdetail->create();
                $this->Salepointdetail->updateAll(['Salepointdetail.ttc' => $montant,
                'Salepointdetail.qte' => $qteD[$details[$i]['Ecommercedetail']['id']], ],
                ['Salepointdetail.ecommercedetail_id' => $details[$i]['Ecommercedetail']['id']]);
            }

            $details2 = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id,
                ['NOT' => ['Ecommercedetail.id' => $produits]], ],
                'contain' => ['Produit'],
            ]);
            $sommeR = 0;

            for ($i = 0; $i < sizeof($details2); ++$i) {
                $prix = $details2[$i]['Ecommercedetail']['unit_price'];
                $montant = $prix * $details2[$i]['Ecommercedetail']['qte'];
                $sommeR += $montant;
            }

            $ecommerce = $this->Ecommerce->find('first', ['conditions' => ['Ecommerce.id' => $details[0]['Ecommercedetail']['ecommerce_id']]]);
            //$net_a_payer = $ecommerce['Ecommerce']['total_apres_reduction'];

            $net_a_payer = $somme + $sommeR;

            //////////////
            $details3 = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id],
                'contain' => ['Produit'],
            ]);
            $total_a_payer_ht = 0;
            foreach ($details3 as $k => $v) {
                $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 20;
                $division_tva = (1 + $tva / 100);
                // Quantité & Prix de vente

                $qte = $v['Ecommercedetail']['qte'];

                $prix_vente_ht = round($v['Ecommercedetail']['unit_price'] / $division_tva, 2);
                // Calcule total
                $total_ht = round($prix_vente_ht * $qte, 2);
                $total_a_payer_ht += $total_ht;
            }

            ///////////////////////
            //$total_ht = $ecommerce['Ecommerce']['total_a_payer_ht'] - $diff;
            $this->Ecommerce->updateAll(['Ecommerce.total_apres_reduction' => $net_a_payer,
            'Ecommerce.total_a_payer_ttc' => $net_a_payer,
            'Ecommerce.total_a_payer_ht' => $total_a_payer_ht, ], ['Ecommerce.id' => $details[0]['Ecommercedetail']['ecommerce_id']]);

            $montant_tva = $net_a_payer - $total_a_payer_ht;
            $this->loadModel('Salepoint');
            $this->Salepoint->updateAll(['Salepoint.total_apres_reduction' => $net_a_payer + $salepoint['Salepoint']['fee'],
            'Salepoint.total_a_payer_ttc' => $net_a_payer, 'Salepoint.total_paye' => $net_a_payer + $salepoint['Salepoint']['fee'],
            'Salepoint.montant_tva' => $montant_tva, 'Salepoint.total_a_payer_ht' => $total_a_payer_ht, ], ['Salepoint.ecommerce_id' => $details[0]['Ecommercedetail']['ecommerce_id']]);
            $this->loadModel('Salepoint');

            $this->loadModel('Salepoint');
            $this->Salepoint->Avance->updateAll(['Avance.montant' => $net_a_payer + $salepoint['Salepoint']['fee']], ['Avance.salepoint_id' => $salepoint['Salepoint']['id'], 'Avance.montant !=' => 0]);

            return $this->redirect(['action' => 'view', $ecommerce_id, 4, $return['message']]);
        } else {
            $error = 3;

            return $this->redirect(['action' => 'view', $ecommerce_id, $error, $return['message']]);
        }
        /* $this->render('view');
$this->set('id', 9);
 */
    }

    public function view($id = null, $produits = null, $message = null, $flag = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role_id = $this->Session->read('Auth.User.role_id');

        $details = [];

        if ($this->Ecommerce->exists($id)) {
            $options = ['contain' => ['Client', 'Depot'], 'conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $id]];
            $this->request->data = $this->Ecommerce->find('first', $options);

            $options = ['contain' => ['Depot', 'Client', 'User'], 'conditions' => ['Salepoint.ecommerce_id' => $id]];

            $details = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $id],
                'contain' => ['Produit'],
            ]);
        } else {
            $this->Session->setFlash("Ce document n'existe pas !", 'alert-danger');

            return $this->redirect(['action' => 'index']);
        }

        if ($produits == '4') {
            $error = false;
            $this->set(compact('details', 'error', 'message', 'role_id', 'user_id', 'flag'));
        } elseif ($produits == '3') {
            $error = true;
            $this->set(compact('details', 'error', 'message', 'role_id', 'user_id', 'flag'));
        } else {
            $this->set(compact('details', 'role_id', 'user_id', 'flag'));
        }
        $this->getPath($this->idModule);
    }

    public function getProduit($produit_id = null, $depot_id = null)
    {
        $produit = $this->Ecommerce->Ecommercedetail->Produit->find('first', ['conditions' => ['Produit.id' => $produit_id]]);
        $tva = (isset($produit['Produit']['tva_vente']) and !empty($produit['Produit']['tva_vente'])) ? $produit['Produit']['tva_vente'] : 20;
        $prix_vente = (isset($produit['Produit']['prix_vente']) and !empty($produit['Produit']['prix_vente'])) ? $produit['Produit']['prix_vente'] : 20;
        die(json_encode(['tva' => $tva, 'prix_vente' => $prix_vente]));
    }

    public function pdf($id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role_id');
        $details = [];
        if ($this->Ecommerce->exists($id)) {
            $options = ['contain' => ['Depot', 'User', 'Client' => ['Ville']], 'conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $id]];
            $this->request->data = $this->Ecommerce->find('first', $options);

            $details = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $id],
                'contain' => ['Produit'],
            ]);
        } else {
            $this->Session->setFlash("Ce document n'existe pas !", 'alert-danger');

            return $this->redirect(['action' => 'index']);
        }

        if (empty($details)) {
            $this->Session->setFlash('Aucun produit saisie ! ', 'alert-danger');

            return $this->redirect($this->referer());
        }

        $societe = $this->GetSociete();
        $this->set(compact('details', 'role', 'user_id', 'societe'));
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


    public function ticket($id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role_id');
        $this->loadModel('Store');
        $this->loadModel('Salepoint');
        $this->loadModel('Caisse');
        $this->loadModel('User');
        $this->loadModel('Avance');
        $this->loadModel('Produit');
        $this->loadModel('Bonachat');
   
        
        $details = [];
        if ($this->Ecommerce->exists($id)) {
            $options = ['contain' => ['Depot', 'User', 'Client' => ['Ville']], 'conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $id]];
            $ecom_data_db = $this->request->data = $this->Ecommerce->find('first', $options);

            $details = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $id],
                'contain' => ['Produit'],
            ]);

            // récupérer les info du store de la vente
            $store_of_salepoint = $ecom_data_db['Ecommerce']['store_id'];
            $store_data = $this->Store->find('first', ['conditions' => ['id' => $store_of_salepoint]]);

            // récupérer les info de la caisse de cette vente
             $conditions = array(
                'Salepoint.store' => $store_of_salepoint,
                'Salepoint.ecommerce_id' => $id
            );
            $salepoints_data = $this->Salepoint->find('first', array(
                'conditions' => $conditions
            ));

            if (!empty($salepoints_data)) {
                $caisse_of_salepoint = $salepoints_data['Salepoint']['caisse_id'];
                $caisse_data = $this->Caisse->find('first', ['conditions' => ['id' => $caisse_of_salepoint]]);
            }

            // récupérer les info de caisser
            $user_c_ecom = $ecom_data_db['Ecommerce']['user_c'];
            $conditions = array(
                'User.id' => $user_c_ecom,
            );
    
            // Effectuer la recherche avec les conditions
            $users_data = $this->User->find('first', array(
                'conditions' => $conditions
            ));

            // VENTILATION TVA
            $this->loadModel('Salepointdetail');
            
            $str = 'SELECT tva, SUM(mnt_ht) as ht, SUM(taxe) AS tax, SUM(mnt_ttc) As ttc FROM ( 
                SELECT Produits.tva_vente As tva, Salepointdetails.ttc / (1 + (Produits.tva_vente/100)) As mnt_ht, Salepointdetails.ttc * (Produits.tva_vente / 100) / (1 + (Produits.tva_vente/100)) as taxe, Salepointdetails.ttc As mnt_ttc
                from Produits, Salepointdetails where Produits.id = Salepointdetails.produit_id and Salepointdetails.salepoint_id = :salepoint_detail_id
                UNION ALL
                SELECT 20, (Salepoints.fee / 1.2), (Salepoints.fee * 0.2 / 1.2), Salepoints.fee
                from Salepoints
                where Salepoints.id = :salepoint_detail_id and fee <> 0) AS Tmps
                group by tva';
    
            $salepoints_data_id = $salepoints_data['Salepoint']['id'];
            $db = $this->Salepoint->getDataSource();

            $details_tva = $db->fetchAll(
                    $str,
                ['salepoint_detail_id' => $salepoints_data_id]
            );

            // MODES DE PAIEMENT
            $avances = $this->Salepoint->Avance->find('all', ['conditions' => ['salepoint_id' => $salepoints_data_id]]);
            $modes = [];
            foreach ($avances as $avance) {
                if ($avance['Avance']['montant'] != 0) {
                    $modes[] = $this->getModePaiment($avance['Avance']['mode']);
                }
            }

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




        } else {
            $this->Session->setFlash("Ce document n'existe pas !", 'alert-danger');

            return $this->redirect(['action' => 'index']);
        }

        if (empty($details)) {
            $this->Session->setFlash('Aucun produit saisie ! ', 'alert-danger');

            return $this->redirect($this->referer());
        }

        $societe = $this->GetSociete();
        $this->set(compact('details_tva','details', 'role', 'user_id', 'societe', 'store_data', 'caisse_data','users_data','salepoints_data','avances','ref_cheque_cad','ref_bon_achat','modes'));


        $this->layout = false;
    }

    public function editdetail($id = null, $ecommerce_id = null)
    {
        $ecommerce = $this->Ecommerce->find('first', ['contain' => ['User'], 'conditions' => ['Ecommerce.id' => $ecommerce_id]]);
        $role_id = $this->Session->read('Auth.User.role_id');
        $admins = $this->Session->read('admins');
        $depot_id = 1;

        $produits_exists = $this->Ecommerce->Ecommercedetail->Produit->find('list', [
            'fields' => ['Produit.id', 'Produit.id'],
            'joins' => [
                ['table' => 'ecommercedetails', 'alias' => 'Ecommercedetail', 'type' => 'INNER', 'conditions' => ['Ecommercedetail.produit_id = Produit.id', 'Ecommercedetail.deleted = 0']],
            ],
            'conditions' => [
                'Ecommercedetail.ecommerce_id' => $ecommerce_id,
            ],
        ]);

        $produits = $this->Ecommerce->Ecommercedetail->Produit->findList([
            'Produit.id !=' => $produits_exists,
        ]);

        if ($this->request->is(['post', 'put'])) {
            $this->request->data['Ecommercedetail']['ecommerce_id'] = $ecommerce_id;
            if ($this->Ecommerce->Ecommercedetail->save($this->request->data)) {
                $this->calculatrice($ecommerce_id);
                $this->Session->setFlash('L\'enregistrement a été effectué avec succès.', 'alert-success');
            } else {
                $this->Session->setFlash('Il y a un problème', 'alert-danger');
            }

            return $this->redirect($this->referer());
        } elseif ($this->Ecommerce->Ecommercedetail->exists($id)) {
            $options = ['conditions' => ['Ecommercedetail.'.$this->Ecommerce->Ecommercedetail->primaryKey => $id]];
            $this->request->data = $this->Ecommerce->Ecommercedetail->find('first', $options);
            $produits = $this->Ecommerce->Ecommercedetail->Produit->findList();
        }

        $depots = $this->Ecommerce->Ecommercedetail->Depot->find('list');
        $categorieproduits = $this->Ecommerce->Ecommercedetail->Produit->Categorieproduit->find('list');
        $this->set(compact('produits', 'role_id', 'depot_id', 'depots', 'categorieproduits'));
        $this->layout = false;
    }

    public function changestate($ecommerce_id = null, $etat = -1)
    {
        $details = $this->Ecommerce->Ecommercedetail->find('all', ['conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id]]);
        if (empty($details)) {
            $this->Session->setFlash('Aucun produit saisie ! ', 'alert-danger');

            return $this->redirect($this->referer());
        }
        $this->Ecommerce->id = $ecommerce_id;
        if ($this->Ecommerce->saveField('etat', $etat)) {
            $this->Session->setFlash("L'enregistrement a été effectué avec succès.", 'alert-success');
        } else {
            $this->Session->setFlash('Il y a un problème', 'alert-danger');
        }

        return $this->redirect($this->referer());
    }

    public function CheckRemise($ecommerce_id = null)
    {
        $ecommerce = $this->Ecommerce->find('first', ['conditions' => ['Ecommerce.id' => $ecommerce_id]]);
        $remise = (isset($ecommerce['Ecommerce']['remise']) and !empty($ecommerce['Ecommerce']['remise'])) ? (float) $ecommerce['Ecommerce']['remise'] : 0;
        $details = $this->Ecommerce->Ecommercedetail->find('all', ['contain' => ['Produit'], 'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id]]);

        $EcommercedetailData = [];
        foreach ($details as $k => $v) {
            // TVA
            $tva = (isset($v['Produit']['tva_vente']) and !empty($v['Produit']['tva_vente'])) ? (int) $v['Produit']['tva_vente'] : 20;
            $division_tva = (1 + $tva / 100);

            // Quantité & Prix de vente
            $qte_cmd = (isset($v['Ecommercedetail']['qte_cmd'])) ? $v['Ecommercedetail']['qte_cmd'] : 0;
            $qte = (isset($v['Ecommercedetail']['qte']) and $v['Ecommercedetail']['qte'] > 0) ? $v['Ecommercedetail']['qte'] : $qte_cmd;
            $prix_vente_ht = round($v['Ecommercedetail']['prix_vente'] / $division_tva, 2);
            $prix_vente_ttc = $v['Ecommercedetail']['prix_vente'];

            // Calcule total
            $total_ht = round($prix_vente_ht * $qte, 2);
            $total_ttc = round($prix_vente_ttc * $qte, 2);

            $montant_remise = ($total_ttc >= 0) ? (float) ($total_ttc * $remise) / 100 : 0;

            $total_ttc = $total_ttc - $montant_remise;

            // Data structure
            $EcommercedetailData[] = [
                'id' => $v['Ecommercedetail']['id'],
                'montant_remise' => $montant_remise,
                'total' => $total_ht,
                'ttc' => $total_ttc,
                'remise' => $remise,
            ];
        }

        if ($this->Ecommerce->Ecommercedetail->saveMany($EcommercedetailData)) {
            $this->calculatrice($ecommerce_id);
        }

        return true;
    }

    public function calculatrice($ecommerce_id = null)
    {
        $ecommerce = $this->Ecommerce->find('first', ['conditions' => ['id' => $ecommerce_id]]);
        $remise = (isset($ecommerce['Ecommerce']['remise']) and !empty($ecommerce['Ecommerce']['remise'])) ? (float) $ecommerce['Ecommerce']['remise'] : 0;
        $details = $this->Ecommerce->Ecommercedetail->find('all', ['conditions' => ['ecommerce_id' => $ecommerce_id]]);

        $tva = 0;
        $montant_tva = 0;
        $total_a_payer_ht = 0;
        $total_a_payer_ttc = 0;
        $nombre_lines = count($details);
        foreach ($details as $k => $value) {
            $total_a_payer_ttc = $total_a_payer_ttc + $value['Ecommercedetail']['ttc'];
            $total_a_payer_ht = $total_a_payer_ht + $value['Ecommercedetail']['total'];
            $montant_tva = $montant_tva + $value['Ecommercedetail']['montant_tva'];
            $tva = $tva + $value['Ecommercedetail']['tva'];
        }
        $tva = round($tva / $nombre_lines, 2);
        $montant_remise = ($total_a_payer_ttc >= 0) ? round(($total_a_payer_ttc * $remise) / 100, 2) : 0;
        $total_apres_reduction = round($total_a_payer_ttc - $montant_remise, 2);

        $data['Ecommerce'] = [
            'id' => $ecommerce_id,
            'tva' => $tva,
            'montant_tva' => $montant_tva,
            'montant_remise' => $montant_remise,
            'total_a_payer_ht' => $total_a_payer_ht,
            'total_a_payer_ttc' => $total_a_payer_ttc,
            'total_apres_reduction' => $total_apres_reduction,
        ];

        if ($this->Ecommerce->save($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function mail($ecommerce_id = null)
    {
        $ecommerce = $this->Ecommerce->find('first', ['contain' => ['Client'], 'conditions' => ['Ecommerce.id' => $ecommerce_id]]);
        $email = (isset($ecommerce['Client']['email']) and !empty($ecommerce['Client']['email'])) ? $ecommerce['Client']['email'] : '';
        if ($this->request->is(['post', 'put'])) {
            $this->request->data['Email']['ecommerce_id'] = $ecommerce_id;
            if ($this->Ecommerce->Email->save($this->request->data)) {
                $url = $this->generatepdf($ecommerce_id);
                $email_id = $this->Ecommerce->Email->id;
                if ($this->Ecommerce->Email->saveField('url', $url)) {
                    $settings = $this->GetParametreSte();
                    $to = [$this->data['Email']['email']];
                    $objet = (isset($this->data['Email']['objet'])) ? $this->data['Email']['objet'] : '';
                    $content = (isset($this->data['Email']['content'])) ? $this->data['Email']['content'] : '';
                    $attachments = ['Ecommerces' => ['mimetype' => 'application/pdf', 'file' => $url]];
                    if ($this->sendEmail($settings, $objet, $content, $to, $attachments)) {
                        $this->Session->setFlash('Votre email a été anvoyer avec succès.', 'alert-success');
                    } else {
                        $this->Session->setFlash("Problème d'envoi de mail", 'alert-danger');
                    }
                }
            } else {
                $this->Session->setFlash('Il y a un problème', 'alert-danger');
            }

            return $this->redirect($this->referer());
        }

        $this->set(compact('email'));
        $this->layout = false;
    }

    public function generatepdf($ecommerce_id = null)
    {
        $user_id = $this->Session->read('Auth.User.id');
        $role = $this->Session->read('Auth.User.role_id');
        $details = [];
        if ($this->Ecommerce->exists($ecommerce_id)) {
            $options = ['contain' => ['Client' => ['Ville']], 'conditions' => ['Ecommerce.'.$this->Ecommerce->primaryKey => $ecommerce_id]];
            $data = $this->Ecommerce->find('first', $options);

            $details = $this->Ecommerce->Ecommercedetail->find('all', [
                'conditions' => ['Ecommercedetail.ecommerce_id' => $ecommerce_id],
                'contain' => ['Produit'],
            ]);
        }
        $societe = $this->GetSociete();

        App::uses('LettreHelper', 'View/Helper');
        $LettreHelper = new LettreHelper(new View());

        $view = new View($this, false);
        $style = $view->element('style', ['societe' => $societe]);
        $header = $view->element('header', ['societe' => $societe, 'title' => 'BON COMMANDE']);
        $footer = $view->element('footer', ['societe' => $societe]);
        $ville = (isset($data['Client']['Ville']['id'])) ? strtoupper($data['Client']['Ville']['libelle']).'<br/>' : '';
        $ice = (isset($data['Client']['id']) and !empty($data['Client']['ice'])) ? 'ICE : '.strtoupper($data['Client']['ice']).'<br/>' : '';
        $adresse = (isset($data['Client']['id']) and !empty($data['Client']['adresse'])) ? strtoupper($data['Client']['adresse']).'<br/>' : '';

        $html = '
			<html>
			<head>
				<title>BON COMMANDE N° : '.$data['Ecommerce']['barcode'].'</title>
			    '.$style.'
			</head>
			<body>

			    '.$header.'

			    '.$footer.'

			    <div>

				    <table class="info" width="100%">
				        <tbody>
				            <tr>
				                <td style="width:50%;text-align:center;">
				                    <h4 class="container"">BON COMMANDE N° : '.$data['Ecommerce']['barcode'].'</h4>
				                </td>
				                <td style="width:50%;text-align:center;">
				                    <h4 class="container"">DATE : '.$data['Ecommerce']['date'].'</h4>
				                </td>
				            </tr>
				            <tr>
				                <td style="width:50%;text-align:center;"></td>
				                <td style="width:50%;text-align:left;">
				                    <h4 class="container">
					                    '.strtoupper($data['Client']['designation']).'<br/>
					                    '.$adresse.'
					                    '.$ville.'
					                   	'.$ice.'
				                    </h4>
				                </td>
				            </tr>
				        </tbody>
				    </table>

				    <table class="details" width="100%">
				        <thead>
				            <tr>
				                <th>Désignation </th>
				                <th>Quantité </th>
				                <th>Prix</th>
				                <th>Montant total</th>
				            </tr>
				        </thead>
				        <tbody>';
        foreach ($details as $tache) {
            $html .= '<tr>
				                    <td nowrap>'.$tache['Produit']['libelle'].'</td>
				                    <td nowrap style="text-align:right;">'.$tache['Ecommercedetail']['qte'].'</td>
				                    <td nowrap style="text-align:right;">'.number_format($tache['Ecommercedetail']['prix_vente'], 2, ',', ' ').'</td>
				                    <td nowrap style="text-align:right;">'.number_format($tache['Ecommercedetail']['total'], 2, ',', ' ').'</td>
				                </tr>';
        }
        $html .= '
				                <tr >
				                    <td style="border:none;"></td>
				                    <td style="border:none;"></td>
				                    <td class="total">NET A PAYER</td>
				                    <td class="total">'.number_format($data['Ecommerce']['total_apres_reduction'], 2, ',', ' ').'</td>
				                </tr>
				        </tbody>
				    </table>

				    <table width="100%">
				        <tbody>
				            <tr>
				                <td style="width:50%;text-align:left;font-weight:bold;">
				                    <u>Arrêtée la présente de la commande à la somme de :</u>
				                </td>
				                <td style="width:50%;text-align:left;font-weight:bold;">
				                    '.strtoupper($LettreHelper->NumberToLetter(strval($data['Ecommerce']['total_apres_reduction']))).' DHS
				                </td>
				            </tr>
				        </tbody>
				    </table>

			    </div>

			    '.$footer.'

			</body>
		</html>';

        //echo $html;die;
        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->render();
        $canvas = $dompdf->get_canvas();
        $font = Font_Metrics::get_font('helvetica', 'bold');
        $canvas->page_text(512, 820, 'Page : {PAGE_NUM}/{PAGE_COUNT}', $font, 8, [0, 0, 0]);
        $output = $dompdf->output();
        $destination = WWW_ROOT.'pdfs';
        if (!file_exists($destination)) {
            mkdir($destination, true, 0700);
        }
        file_put_contents($destination.DS.'Ecommerce.'.$data['Ecommerce']['date'].'-'.$data['Ecommerce']['id'].'.pdf', $output);

        return $destination.DS.'Ecommerce.'.$data['Ecommerce']['date'].'-'.$data['Ecommerce']['id'].'.pdf';
    }


    public function generateBonPreparation($id = null)
    {
        $this->layout = null; // Pas de layout global
        $this->autoRender = false;

        if (!$this->Ecommerce->exists($id)) {
            throw new NotFoundException(__('Commande non trouvée'));
        }

        // Récupération des données
        $data = $this->Ecommerce->find('first', [
            'conditions' => ['Ecommerce.id' => $id],
            'contain' => [],
        ]);

        // Récupération des informations du client
        $this->loadModel('Client');

        $client = $this->Client->find('first', [
            'conditions' => ['Client.id' => $data['Ecommerce']['client_id']],
            'fields' => ['designation'],
            'recursive' => -1
        ]);

        $data['Client']['designation'] = !empty($client['Client']['designation']) ? $client['Client']['designation'] : '<i>Client introuvable</i>';

        $details = $this->Ecommerce->Ecommercedetail->find('all', [
            'conditions' => ['Ecommercedetail.ecommerce_id' => $id],
            'contain' => []
        ]);
        
        $this->loadModel('Produit');
        
        foreach ($details as &$detail) {
            $produit = $this->Produit->find('first', [
                'conditions' => ['Produit.id' => $detail['Ecommercedetail']['produit_id']], // produit_id est en réalité code_barre
                'fields' => ['libelle'],
                'recursive' => -1
            ]);
            $detail['Produit']['libelle'] = !empty($produit['Produit']['libelle']) ? $produit['Produit']['libelle'] : '<i>Produit introuvable</i>';
        }


        // Appel la function changeStatus pour changer le statut de la commande
        $online_id = $data['Ecommerce']['online_id'];
        $this->changeStatus($online_id, 'in_preparation');

        $view = new View($this, false);
        $view->viewPath = 'Ecommerces';
        $view->set(compact('data', 'details'));
        $html = $view->render('generate_bon_preparation');

        // Génération PDF
        App::import('Vendor', 'dompdf', ['file' => 'dompdf' . DS . 'dompdf_config.inc.php']);
        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Bon_preparation_{$data['Ecommerce']['barcode']}.pdf", ['Attachment' => false]);
    }

    public function changeStatus($id = null, $status = null, $shipment = null)
    {        

        // if $status = ready_for_delivery il faut récuprer la date et l'heure d'aujourd'hui et ajouter +5min et créer une variable planned_start_time avec ce format : 2025-05-05 22:59
        if ($shipment == 'self') {
            $now_date = date('Y-m-d');
            $now_time = date('H:i');
            // $now_time = "20:00";
        } else {
            $now_date = NULL;
            $now_time = NULL;
        }

        $this->autoRender = false;

        if (!$id) {
            throw new NotFoundException(__('Commande non trouvée'));
        }

        $parametres = $this->GetParametreSte();
        $user = $parametres['User'];
        $password = $parametres['Password'];
        $url = $parametres['Api update pos'];


        // $ecommerce = $this->Ecommerce->find('first', [
        //    'conditions' => ['Ecommerce.id' => $id],
        //    'contain' => ['Ecommercedetail']
        // ]);
    
        // $onlineId = $ecommerce['Ecommerce']['online_id'];
        $onlineId = $id;
        
        $payload = [
            'site' => 1,
            'id' => (int)$onlineId,
            'status' => $status,
            'date' => $now_date,
            'time' => $now_time,
        ];

        $jsonData = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($user . ':' . $password)
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->Session->setFlash("Erreur CURL : $error", 'default', [], 'error');
            $this->redirect($this->referer());
        }

        curl_close($ch);

        // Décoder la réponse
        $decoded = json_decode($response, true);

        // Si l'API a répondu avec succès
        if (!empty($decoded['success']) && $decoded['success'] === true) {
            // Mettre à jour le statut dans la base locale
            $this->Ecommerce->id = $id;
            $this->Ecommerce->saveField('statut', $status);

            $this->Session->setFlash('Commande mise à jour avec succès. Nouveau statut : ' . $status, 'default', [], 'success');
        } else {
            $msg = !empty($decoded['message']) ? $decoded['message'] : 'Erreur inconnue lors de la mise à jour.';
            $this->Session->setFlash('Erreur API : ' . $msg, 'default', [], 'error');
        }

       // $this->redirect($this->referer());
    }



    public function changeStatusBackup($id = null, $status = null)
    {

        // $status = 'confirmed'; // ou 'shipped', 'delivered', etc.
        
        $this->autoRender = false;

        if (!$id) {
            throw new NotFoundException(__('Commande non trouvée'));
        }

        $parametres = $this->GetParametreSte();
        $user = $parametres['User'];
        $password = $parametres['Password'];
        $url = $parametres['Api update pos'];

        $ecommerce = $this->Ecommerce->find('first', [
            'conditions' => ['Ecommerce.id' => $id],
            'contain' => ['Ecommercedetail']
        ]);

        $line_items = [];
        if (!empty($ecommerce['Ecommercedetail'])) {
            foreach ($ecommerce['Ecommercedetail'] as $item) {
                $line_items[] = [
                    'id' => (int)$item['id'],
                    'product_id' => (int)$item['produit_id'],
                    'quantity' => (float)$item['qte'],
                    'unit_price' => number_format($item['prix_vente'], 4, '.', '')
                ];
            }
        }
    
        $onlineId = $ecommerce['Ecommerce']['online_id'];

        $payload = [
            'site' => 1,
            'id' => (int)$onlineId,
            'status' => $status,
            'payment_method' => 'cod',
            'shipment' => 'delivery',
            'line_items' => $line_items
        ];

        $jsonData = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($user . ':' . $password)
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->Session->setFlash("Erreur CURL : $error", 'default', [], 'error');
            $this->redirect($this->referer());
        }

        curl_close($ch);

        // Décoder la réponse
        $decoded = json_decode($response, true);

        // Si l'API a répondu avec succès
        if (!empty($decoded['success']) && $decoded['success'] === true) {
            // Mettre à jour le statut dans la base locale
            $this->Ecommerce->id = $id;
            $this->Ecommerce->saveField('statut', $status);

            $this->Session->setFlash('Commande mise à jour avec succès. Nouveau statut : ' . $status, 'default', [], 'success');
        } else {
            $msg = !empty($decoded['message']) ? $decoded['message'] : 'Erreur inconnue lors de la mise à jour.';
            $this->Session->setFlash('Erreur API : ' . $msg, 'default', [], 'error');
        }

       // $this->redirect($this->referer());
    }


    
}
