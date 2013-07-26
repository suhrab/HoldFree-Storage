<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Anton
 * Date: 7/26/13
 * Time: 2:38 PM
 * To change this template use File | Settings | File Templates.
 */

use Phalcon\Mvc\Model,
    Phalcon\Mvc\Model\Message,
    Phalcon\Mvc\Model\Validator\InclusionIn,
    Phalcon\Mvc\Model\Validator\Uniqueness;

class Files extends Model {
    public $id;
    public $absolute_filepath;
    public $created;
    public $uuid;
    public $filesize;
}