<?php

class Promotion extends AppModel
{
    public $displayField = 'reference';

    public $belongsTo =
        ['Client', 'Produit', 'Categorieproduit' => [
            'className' => 'Categorieproduit',
            'foreignKey' => 'categorie_id',
        ], 'Creator' => [
            'className' => 'User',
            'foreignKey' => 'user_c',
        ]];

    public function findList($conditions = [])
    {
        $depots = $this->find('all', ['order' => [$this->alias.'.reference' => 'asc'], 'conditions' => $conditions]);
        foreach ($depots as $k => $v) {
            $list[$v[$this->alias]['id']] = $v[$this->alias]['reference'].' - '.$v[$this->alias]['date'];
        }

        return (isset($list) and !empty($list)) ? $list : [];
    }

    public function beforeSave($options = [])
    {
        parent::beforeSave($options);
        if (empty($this->data[$this->alias]['id']) and empty($this->data[$this->alias]['reference'])) {
            $number = $this->find('count', ['conditions' => [$this->alias.'.deleted' => [0, 1]]]) + 1;
            $this->data[$this->alias]['reference'] = 'PR-'.str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        if (!empty($this->data[$this->alias]['date_c'])) {
            $this->data[$this->alias]['date_c'] = $this->dateTimeFormatBeforeSave($this->data[$this->alias]['date_c']);
        }
        if (!empty($this->data[$this->alias]['date_limite'])) {
            $this->data[$this->alias]['date_limite'] = date('Y-m-d H:i:s', strtotime($this->data[$this->alias]['date_limite'].' 23:59:59'));
        }

        return true;
    }

    public function afterFind($results, $primary = false)
    {
        foreach ($results as $key => $val) {
            if (isset($val[$this->alias]['date_c'])) {
                $results[$key][$this->alias]['date_c'] = $this->dateTimeFormatAfterFind($val[$this->alias]['date_c']);
            }
            if (isset($val[$this->alias]['date_limite'])) {
                $results[$key][$this->alias]['date_limite'] = $this->dateTimeFormatAfterFind2($val[$this->alias]['date_limite']);
            }
        }

        return $results;
    }
}