<?php
declare(strict_types=1);
require_once __DIR__ . '/EntitySchema.php';
use CRM_Civineonotify_ExtensionUtil as E;
use Civi\Civineonotify\Schema\EntitySchema;
return ['name'=>'MatrixRoom','table'=>'civicrm_matrix_room','class'=>'CRM_Civineonotify_DAO_MatrixRoom','getInfo'=>fn()=>['title'=>E::ts('Matrix Room'),'add'=>'0.0.1'],'getIndices'=>fn()=>['matrix_room_connection_default'=>['fields'=>['connection_id'=>TRUE,'is_default'=>TRUE],'add'=>'0.0.1']],'getFields'=>fn()=>EntitySchema::withTitles([
  'id'=>['sql_type'=>'int unsigned','primary_key'=>TRUE,'auto_increment'=>TRUE,'required'=>TRUE,'add'=>'0.0.1'],'connection_id'=>['sql_type'=>'int unsigned','required'=>TRUE,'input_type'=>'EntityRef','entity_reference'=>['entity'=>'MatrixConnection','key'=>'id','on_delete'=>'CASCADE'],'add'=>'0.0.1'],'name'=>['sql_type'=>'varchar(128)','required'=>TRUE,'add'=>'0.0.1'],'description'=>['sql_type'=>'text','add'=>'0.0.1'],'room_id'=>['sql_type'=>'varchar(255)','add'=>'0.0.1'],'room_alias'=>['sql_type'=>'varchar(255)','add'=>'0.0.1'],'is_active'=>['sql_type'=>'boolean','default'=>TRUE,'add'=>'0.0.1'],'is_default'=>['sql_type'=>'boolean','default'=>FALSE,'add'=>'0.0.1'],'created_date'=>['sql_type'=>'timestamp','default'=>'CURRENT_TIMESTAMP','add'=>'0.0.1'],'modified_date'=>['sql_type'=>'timestamp','default'=>'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP','add'=>'0.0.1'],])];
