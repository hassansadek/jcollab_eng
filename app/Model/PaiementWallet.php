<?php

class PaiementWallet extends AppModel
{
    public $displayField = 'reference';

    public $belongsTo =
        ['Wallet', 'Client', 'Creator' => [
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
            $this->data[$this->alias]['reference'] = 'PW-'.str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        if (!empty($this->data[$this->alias]['date_c'])) {
            $this->data[$this->alias]['date_c'] = $this->dateTimeFormatBeforeSave($this->data[$this->alias]['date_c']);
        }

        return true;
    }

    public function afterFind($results, $primary = false)
    {
        foreach ($results as $key => $val) {
            if (isset($val[$this->alias]['date_c'])) {
                $results[$key][$this->alias]['date_c'] = $this->dateTimeFormatAfterFind($val[$this->alias]['date_c']);
            }
        }

        return $results;
    }
}
