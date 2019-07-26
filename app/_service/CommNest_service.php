<?php
namespace Gajija\service;

use Gajija\model\CommNest_model;
use Gajija\service\_traits\db\Service_DBCommNest_Trait;
use system\traits\DB_NestedSet_Trait;

/**
 * :: 공용 서비스.... 
 * 
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class CommNest_service extends CommNest_model
{
	use Service_DBCommNest_Trait, DB_NestedSet_Trait;
}