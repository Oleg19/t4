<?php

namespace T4\Orm\Extensions;

use T4\Orm\Exception;
use T4\Orm\Extension;

class Tree
    extends Extension
{

    public function prepareColumns($columns)
    {
        return $columns + [
            '__lft' => ['type' => 'int'],
            '__rgt' => ['type' => 'int'],
            '__lvl' => ['type' => 'int'],
            '__prt' => ['type' => 'link'],
        ];
    }

    public function prepareIndexes($indexes)
    {
        return $indexes + [
            '__lft' => ['columns' => ['__lft']],
            '__rgt' => ['columns' => ['__rgt']],
            '__lvl' => ['columns' => ['__lvl']],
            '__key' => ['columns' => ['__lft', '__rgt', '__lvl']],
            '__prt' => ['columns' => ['__prt']],
        ];
    }

    public function callStatic($class, $method, $argv)
    {
        switch (true) {
            case 'findAllTree' == $method:
                return $class::findAll(['order'=>'__lft']);
                break;
        }
        throw new Exception('Method ' . $method . ' is not found in extension ' . __CLASS__);
    }

    public function call($model, $method, $argv)
    {
        $class = get_class($model);
        switch (true) {
            case 'findAllChildren':
                return $class::findAll([
                    'where'=>'__lft>'.$model->__lft.' AND __rgt<='.$model->__rgt,
                    'order'=>'__lft'
                ]);
            case 'findSubTree':
                return $class::findAll([
                    'where'=>'__lft>='.$model->__lft.' AND __rgt<='.$model->__rgt,
                    'order'=>'__lft'
                ]);
        }
        throw new Exception('Method ' . $method . ' is not found in extension ' . __CLASS__);
    }

}