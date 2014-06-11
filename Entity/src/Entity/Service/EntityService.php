<?php

/* 
 * Copyright (c) 2014 Lero9 Limited
 * All Rights Reserved
 * This software is subject to our terms of trade and any applicable licensing agreements.
 */

namespace Entity\Service;

use \Zend\ServiceManager\ServiceLocatorAwareInterface;
use \Zend\ServiceManager\ServiceLocatorInterface;
use Magelink\Exception\MagelinkException;
use Magelink\Exception\NodeException;
use \Zend\Db\TableGateway\TableGateway;

/**
 * The EntityService provides the API to all entity data in the system, and provides search, retrieval, update and delete services.
 *
 * @package Entity\Service
 */
class EntityService implements ServiceLocatorAwareInterface {

    /**
     * Helper used for saving records to the database
     * @var \Entity\Helper\Saver
     */
    protected $_saver;

    /**
     * Retreive Saving helper
     * @see $_saver
     * @return \Entity\Helper\Saver
     */
    protected function getSaver(){
        if($this->_saver){
            return $this->_saver;
        }
        $this->_saver = new \Entity\Helper\Saver();
        $this->_saver->setServiceLocator($this->getServiceLocator());
        return $this->_saver;
    }

    /**
     * Helper used for loading records from the database
     * @var \Entity\Helper\Loader
     */
    protected $_loader;

    /**
     * Retrieve Loading helper
     * @see $_loader
     * @return \Entity\Helper\Loader
     */
    protected function getLoader(){
        if($this->_loader){
            return $this->_loader;
        }
        $this->_loader = new \Entity\Helper\Loader();
        $this->_loader->setServiceLocator($this->getServiceLocator());
        return $this->_loader;
    }

    /**
     * Helper used for loading records from the database using MLQL
     * @var \Entity\Helper\Querier
     */
    protected $_querier;

    /**
     * Retrieve Querying helper
     * @see $_querier
     * @return \Entity\Helper\Querier
     */
    protected function getQuerier(){
        if($this->_querier){
            return $this->_querier;
        }
        $this->_querier = new \Entity\Helper\Querier();
        $this->_querier->setServiceLocator($this->getServiceLocator());
        return $this->_querier;
    }

    /**
     * Loads the entity with the given ID from the database for the given node.
     * 
     * @param int $nodeId
     * @param int $entityId
     * @return \Entity\Entity|null
     */
    public function loadEntityId($nodeId, $entityId)
    {
        $this->verifyNodeId($nodeId);

        if(is_string($entityId)){
            if(is_numeric($entityId)){
                $entityId = intval($entityId);
            }else{
                throw new NodeException('Invalid entity ID passed to loadEntityId - '.$entityId);
            }
        }

        $entityTypeId = $this->getLoader()->getEntityTypeId($entityId);
        $this->getServiceLocator()->get('logService')->log(
            \Log\Service\LogService::LEVEL_DEBUG,
            'loadeid',
            'loadEntityId - '.$nodeId.' - '.$entityId.' ('.$entityTypeId.')',
            array('node_id'=>$nodeId, 'entity_id'=>$entityId), array('entity'=>$entityId)
        );

        $attributes = $this->getServiceLocator()->get('nodeService')
            ->getSubscribedAttributeCodes($nodeId, $entityTypeId);
        $result = $this->getLoader()->loadEntities(
            $entityTypeId,
            0,
            array('ENTITY_ID'=>$entityId),
            $attributes,
            array('ENTITY_ID'=>'eq'),
            array('limit'=>1, 'node_id'=>$nodeId)
        );

        if(!$result || !count($result)){
            return null;
        }else{
            foreach($result as $entity){
                // Return first row
                return $entity;
            }
        }
    }

    /**
     * Reloads the provided entity from the database to capture any freshly updated data.
     * @param \Entity\Entity $entity The entity to reload (passed by reference)
     * @return \Entity\Entity
     */
    public function reloadEntity(\Entity\Entity &$entity)
    {
        $entity = $this->loadEntityId($entity->getLoadedNodeId(), $entity->getId());
        if (!$entity) {
            throw new NodeException('Cannot reload entity - potentially deleted?');
        }
        return $entity;
    }
    
    /**
     * Loads the entity with the provided unique key from the database for the given node.
     * 
     * @param int $node_id
     * @param int|string $entity_type
     * @param string $store_id
     * @param string $unique_id
     * @return \Entity\Entity|null
     */
    public function loadEntity ( $node_id, $entity_type, $store_id, $unique_id ) {
        $this->verifyNodeId($node_id);
        $this->verifyEntityType($entity_type);
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'loade', 'loadEntity - ' . $node_id . ' - ' . $entity_type . ' - ' . $store_id . ' - ' . $unique_id, array('node_id'=>$node_id, 'entity_type'=>$entity_type, 'store_id'=>$store_id, 'unique_id'=>$unique_id));

        $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type);

        $result = $this->getLoader()->loadEntities($entity_type, $store_id, array('UNIQUE_ID'=>$unique_id), $attributes, array('UNIQUE_ID'=>'eq'), array('limit'=>1, 'node_id'=>$node_id));

        if(!$result || !count($result)){
            return null;
        }else{
            foreach($result as $ent){
                // Return first row
                return $ent;
            }
        }

        return null;
    }
    
    /**
     * Loads the entity identified by the given local_id from the database for the given node.
     * 
     * @param int $node_id
     * @param int|string $entity_type
     * @param string $store_id
     * @param string $local_id
     * @return \Entity\Entity|null
     */
    public function loadEntityLocal ( $node_id, $entity_type, $store_id, $local_id ) {
        $this->verifyNodeId($node_id);
        $this->verifyEntityType($entity_type);
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'loadeloc', 'loadEntityLocal - ' . $node_id . ' - ' . $entity_type . ' - ' . $store_id . ' - ' . $local_id, array('node_id'=>$node_id, 'entity_type'=>$entity_type, 'store_id'=>$store_id, 'local_id'=>$local_id));


        $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type);

        $result = $this->getLoader()->loadEntities($entity_type, $store_id, array('LOCAL_ID'=>$local_id), $attributes, array('LOCAL_ID'=>'eq'), array('linked_to_node'=>$node_id, 'limit'=>1, 'node_id'=>$node_id));

        if(!$result || !count($result)){
            return null;
        }else{
            foreach($result as $ent){
                // Return first row
                return $ent;
            }
        }

        return null;
    }
    
    /**
     * Loads all the child entities of the provided Entity, for the given node. Empty array if none exist.
     * 
     * @param int $node_id
     * @param \Entity\Entity $parent
     * @param int|string $entity_type_id
     * @return \Entity\Entity[]
     */
    public function loadChildren ( $node_id, \Entity\Entity $parent, $entity_type_id ) {
        $this->verifyNodeId($node_id);
        $this->verifyEntityType($entity_type_id);
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'loadchildren', 'loadChildren - ' . $node_id . ' - ' . $parent->getId() . ' - ' . $entity_type_id, array('node_id'=>$node_id, 'parent_id'=>$parent->getId(), 'entity_type_id'=>$entity_type_id), array('entity'=>$parent));


        $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type_id);

        $result = $this->getLoader()->loadEntities($entity_type_id, 0, array('PARENT_ID'=>$parent->getId()), $attributes, array('PARENT_ID'=>'eq'), array('node_id'=>$node_id));

        if(!$result || !count($result)){
            return array();
        }else{
            return $result;
        }
    }

    /**
     * Loads the parent entity of the provided Entity, for the given node. Null if not specified.
     * 
     * @param int $node_id
     * @param \Entity\Entity $child
     * @return \Entity\Entity|null
     */
    public function loadParent ( $node_id, \Entity\Entity $child ) {
        $this->verifyNodeId($node_id);
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'loadparent', 'loadParent - ' . $node_id . ' - ' . $child->getId(), array('node_id'=>$node_id, 'parent_id'=>$child->getId()), array('entity'=>$child));


        if($child->getParentId()){
            return $this->loadEntityId($node_id, $child->getParentId());
        }else{
            return null;
        }
    }
    
    /**
     * Searches for entities that match the given parameters.
     * 
     * $searchData is an associative array where the keys are attribute codes, and the values are the contents to match.
     * There is also a $searchType array, which defines how the values in the first array are treated. The search types are as follows:
     * * eq - Default for all scalar values, does an exact equals search, or in the case of an attribute that has multiple values in the DB, an exact equals search on any of the values in the DB.
     * * all_eq - Same as eq but in the case of an attribute with multiple values in the DB, all values must match the provided value.
     * * in - Default for all numeric array values, does an IN query, or in the case of an attribute that has multiple values in the DB, does an IN query on any of the values
     * * all_in - Same as in but in the case of an attribute with multiple values in the DB, all values must be in the provided values.
     * * multi_key - Only for multi type attributes, checks if the provided value is used as a key in any of the DB entries.
     * * multi_value - Only for multi type attributes, checks if the provided value is used as a value in any of the DB entries.
     * * gt - Same as eq but does a greater-than search instead of equals.
     * * all_gt - Same as all_eq but does a greater-than search instead of equals.
     * * lt - Same as eq but does a less-than search instead of equals
     * * all_lt - Same as all_eq but does a less-than search instead of equals
     * * not_eq - Does an exact not equals search, or in the case of an attribute that has multiple values in the DB, checks that none of them are equal to this value.
     * * not_in - Same as not_eq but performs either a check the DB value is not one of the provided values, or if there are multiple values in the DB, that none of them are equal to any of the provided values,

     * In addition to the above, the options array can contain the following keys:
     * * order - An associative array of attribute codes to sort by, organized with the attribute code as the key and the direction as the value.
     * * limit - The maximum number of rows to return
     * * offset - The number of rows to skip
     * 
     * @param int $node_id
     * @param int|string $entity_type
     * @param string|array|false $store_id
     * @param array $searchData
     * @param array $searchType
     * @param array $options
     * @param array $attributes If specified, an array of attribute codes to load (should only be used when you want to load only a couple for performance reasons)
     * @return \Entity\Entity[]
     */
    public function locateEntity ( $node_id, $entity_type, $store_id, $searchData, $searchType = array(), $options = array(), $attributes = null ) {
        if($node_id !== 0){
            $this->verifyNodeId($node_id);
        }
        $this->verifyEntityType($entity_type);

        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'locate', 'locateEntity - ' . $node_id . ' - ' . $entity_type . ' - ' . $store_id . '. '.PHP_EOL.'SD: '.PHP_EOL . var_export($searchData, true) . PHP_EOL.'; ST: '.PHP_EOL . var_export($searchType, true) . PHP_EOL . '; OPT: '.PHP_EOL.var_export($options, true).PHP_EOL, array(), array('node'=>$node_id));

        if($attributes == null){
            $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type);
        }

        if(!isset($options['node_id'])){
            $options['node_id'] = $node_id;
        }

        $result = $this->getLoader()->loadEntities($entity_type, $store_id, $searchData, $attributes, $searchType, $options);
        return $result;
    }

    /**
     * Performs the same actions as locateEntity() however returns the count of all items instead. Used for paginating the results above.
     * @see locateEntity()
     * @param int $node_id
     * @param int|string$entity_type
     * @param int $store_id
     * @param array $searchData
     * @param array $searchType
     * @param array $options
     * @return int
     * @throws \Magelink\Exception\MagelinkException
     */
    public function countEntity ( $node_id, $entity_type, $store_id, $searchData, $searchType = array(), $options = array() ) {
        if($node_id !== 0){
            $this->verifyNodeId($node_id);
        }
        $this->verifyEntityType($entity_type);

        if(!array_key_exists('count', $options)){
            $options['count'] = '*';
        }

        if(!array_key_exists('node_id', $options)){
            $options['node_id'] = $node_id;
        }

        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'count', 'countEntity - ' . $node_id . ' - ' . $entity_type . ' - ' . $store_id . '. '.PHP_EOL.'SD: '.PHP_EOL . var_export($searchData, true) . PHP_EOL.'; ST: '.PHP_EOL . var_export($searchType, true) . PHP_EOL . '; OPT: '.PHP_EOL.var_export($options, true).PHP_EOL, array(), array('node'=>$node_id));

        $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type);

        $result = $this->getLoader()->loadEntities($entity_type, $store_id, $searchData, $attributes, $searchType, $options);
        if(!is_int($result)){
            throw new \Magelink\Exception\MagelinkException('Invalid result for countEntity - ' . gettype($result));
        }
        return $result;
    }

    /**
     * Performs the same actions as locateEntity() however returns an aggregated result
     *
     * @see locateEntity()
     * @param int $nodeId
     * @param int|string $entityType
     * @param int $storeId
     * @param array $aggregate
     * @param array $searchData
     * @param array $searchType
     * @param array $options
     * @throws \Magelink\Exception\MagelinkException
     * @return int
     */
    public function aggregateEntity($nodeId, $entityType, $storeId, $aggregate,
        $searchData = array(), $searchType = array(), $options = array())
    {
        $this->verifyNodeId($nodeId);
        $this->verifyEntityType($entityType);

        if(!array_key_exists('node_id', $options)){
            $options['node_id'] = $nodeId;
        }

        $options['aggregate'] = $aggregate;
        $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($nodeId, $entityType);
        $result = $this->getLoader()
            ->loadEntities($entityType, $storeId, $searchData, $attributes, $searchType, $options);
        if ($result instanceof \ArrayObject) {
            $result = $result->getArrayCopy();
        }

        return $result;
    }


    /**
     * Ensure all the attributes for the given node are loaded into the provided Entity, and if provided, also the attributes identified by $additional_attributes.
     * 
     * Used if Entities are passed around or if passed to core code that needs more data.
     * The original Entity instance should not be modified, instead it should be cloned and the new Entity returned.
     * 
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @param array $additional_attributes
     * @return \Entity\Entity
     */
    public function enhanceEntity ( $node_id, \Entity\Entity $entity, $additional_attributes = array() ) {
        if($node_id !== false){
            $this->verifyNodeId($node_id);

            $attributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity->getType());
            $attributes = array_unique(array_merge($additional_attributes, $attributes));
        }else{
            $attributes = $additional_attributes;
        }

        // Remove already loaded attributes
        foreach($attributes as $k=>$v){
            if(!$v || !strlen($v)){
                unset($attributes[$k]);
                continue;
            }
            if($entity->hasAttribute($v)){
                unset($attributes[$k]);
                continue;
            }
        }

        if(count($attributes)){
            $this->getLoader()->enhanceEntity($entity, $attributes);
        }

        return $entity;
    }

    /**
     * Begins an entity transaction.
     *
     * The transaction MUST be committed or rolled back, or the entire request/process will be stuck in a transaction.
     *
     * @param string $id An arbitrary entity ID
     */
    public function beginEntityTransaction($id){
        $this->getSaver()->beginTransaction('enttr-'.$id);
    }

    /**
     * Commits an entity transaction.
     *
     * @param string $id
     */
    public function commitEntityTransaction($id){
        $this->getSaver()->commitTransaction('enttr-'.$id);
    }

    /**
     * Rolls back an entity transaction
     *
     * @param string $id
     */
    public function rollbackEntityTransaction($id){
        $this->getSaver()->rollbackTransaction('enttr-'.$id);
    }

    /**
     * Creates a new entity in the DB, loads it, and returns the newly loaded Entity object.
     * This Entity is not linked to the node at this stage.
     * 
     * @param int $node_id
     * @param int|string $entity_type
     * @param string $store_id
     * @param string $unique_id
     * @param array $data
     * @param \Entity\Entity|int $parent
     * @throws MagelinkException
     * @return \Entity\Entity
     */
    public function createEntity ( $node_id, $entity_type, $store_id, $unique_id, $data, $parent = null ) {
        $this->verifyNodeId($node_id);
        $this->verifyEntityType($entity_type);

        if(is_object($parent)){
            if($parent instanceof \Entity\Entity){
                $parent = $parent->getId();
            }else{
                throw new NodeException('Invalid object type ' . get_class($parent) . ' passed to createEntity!');
            }
        }

        $allowedAttributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity_type, true);
        foreach($data as $k=>$v){
            if(strlen(trim($k)) == 0){
                unset($data[$k]);
                continue;
            }
            if(!in_array($k, $allowedAttributes)){
                throw new NodeException('Invalid attribute specified for update ' . $k);
            }
        }

        $id = $this->getSaver()->createEntity($entity_type, $store_id, $unique_id, ($parent ? $parent : 0), $data);

        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'create', 'createEntity - ' . $node_id . ' - new entity ' . $id . ' is ' . $entity_type, array('type'=>$entity_type, 'store'=>$store_id, 'unique'=>$unique_id), array('entity'=>$id, 'node'=>$node_id));

        $entity = $this->loadEntityId($node_id, $id);

        $transformedData = $this->getServiceLocator()->get('routerService')->processTransforms($entity, $data, $node_id, \Entity\Update::TYPE_CREATE);
        if(count($transformedData)){
            $this->silentUpdateEntity($entity, $transformedData, false);
            $data = array_merge($data, $transformedData);
        }

        $this->getAdapter()->query('UPDATE router_stat_type SET `count` = `count` + 1 WHERE entity_type_id = ' . $entity_type . ';', \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        if($node_id !== 0){
            $this->getServiceLocator()->get('routerService')->distributeUpdate($entity, $data, $node_id, \Entity\Update::TYPE_CREATE);
        }

        return $entity;
    }
    
    /**
     * Creates an entity identifier entry to link the given entity to this node.
     * 
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @param string $local_id
     * @throws MagelinkException
     */
    public function linkEntity ( $node_id, \Entity\Entity $entity, $local_id ) {
        $this->verifyNodeId($node_id);

        $existing = $this->getTableGateway('entity_identifier')->select(array('entity_id'=>$entity->getId(), 'node_id'=>$node_id));
        if($existing && count($existing)){
            throw new NodeException('Entity is already linked - ' . $entity->getId() . ' with node ' . $node_id);
        }

        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'link', 'linkEntity - ' . $node_id . ' - ' . $entity->getId() . ': ' . $local_id, array('local'=>$local_id), array('entity'=>$entity, 'node'=>$node_id));

        $res = $this->getTableGateway('entity_identifier')->insert(array(
            'entity_id'=>$entity->getId(),
            'node_id'=>$node_id,
            'store_id'=>$entity->getStoreId(),
            'local_id'=>$local_id,
        ));
        if(!$res){
            throw new MagelinkException('Unknown error in linkEntity');
        }
    }
    
    /**
     * Removes the entity identifier entry to unlink the given entity from this node.
     * 
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @throws MagelinkException
     */
    public function unlinkEntity ( $node_id, \Entity\Entity $entity ) {
        $this->verifyNodeId($node_id);

        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'unlink', 'unlinkEntity - ' . $node_id . ' - ' . $entity->getId(), array(), array('entity'=>$entity, 'node'=>$node_id));

        $res = $this->getTableGateway('entity_identifier')->delete(array(
            'entity_id'=>$entity->getId(),
            'node_id'=>$node_id,
        ));
        if(!$res){
            throw new NodeException('Tried to unlink entity that was not linked');
        }
    }

    /**
     * Returns the local ID for the given entity on this node, if it exists.
     *
     * @param $node_id
     * @param \Entity\Entity|int $entity
     * @return string|null
     */
    public function getLocalId ( $node_id, $entity ) {
        if(is_object($entity)){
            $entity = $entity->getId();
        }
        $res = $this->getTableGateway('entity_identifier')->select(array(
            'entity_id'=>$entity,
            'node_id'=>$node_id,
        ));
        foreach($res as $row){
            return $row['local_id'];
        }
        return null;
    }

    /**
     * Retrieves the Local ID for an entity from remote Nodes
     *
     * @param int $node_id The ID of the node doing the retrieving
     * @param \Entity\Entity $entity The Entity to request an ID for
     * @param string $remote_type What type of Node to retrieve for
     * @param bool $allowMultiple If true, returns all found Local IDs as an array, otherwise simply returns the first.
     * @return int|array|null The local ID found or an array of local IDs found
     */
    public function getRemoteId ( $node_id, \Entity\Entity $entity, $remote_type, $allowMultiple=true ){
        $this->verifyNodeId($node_id);

        $nodeRes = $this->getTableGateway('node')->select(array('type'=>$remote_type));
        if(!$nodeRes || !count($nodeRes)){
            return ($allowMultiple ? array() : null);
        }

        $nodeIds = array();
        foreach($nodeRes as $row){
            $nodeIds[] = $row['node_id'];
        }

        $ret = array();

        $res = $this->getTableGateway('entity_identifier')->select(array('node_id'=>$nodeIds, 'entity_id'=>$entity->getId()));
        foreach($res as $row){
            if($row['local_id'] == null || $row['local_id'] == ''){
                continue;
            }
            if(!$allowMultiple){
                return $row['local_id'];
            }else{
                $ret[] = $row['local_id'];
            }
        }

        if($allowMultiple){
            return $ret;
        }else{
            return null;
        }
    }

    /**
     * Returns all nodes linked to the given entity along with their local IDs
     *
     * @param \Entity\Entity|int $entity
     * @return string[] Array with keys being Node IDs and values being local IDs
     */
    public function getAllLinks( $entity ){
        if(is_object($entity)){
            $entity = $entity->getId();
        }
        $res = $this->getTableGateway('entity_identifier')->select(array(
            'entity_id'=>$entity,
        ));
        $return = array();
        foreach($res as $row){
            $return[$row['node_id']] = $row['local_id'];
        }
        return $return;
    }
    
    /**
     * Updates the entities updated_at timestamp.
     * If an array of attribute codes is provided, also updates their updated_at timestamps.
     * No update or event entries are created.
     * 
     * @param \Entity\Entity $entity
     * @param string[] $attributes
     */
    public function touchEntity ( \Entity\Entity $entity, $attributes = array() ) {
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'touch', 'touchEntity - ' . $entity->getId(), array(), array('entity'=>$entity));
        $this->getSaver()->touchEntity($entity, $attributes);
    }

    /**
     * Update a records parent ID without performing any other actions. Does NOT update updated_at.
     *
     * @param \Entity\Entity|int $child
     * @param \Entity\Entity|int $parent
     * @throws \Magelink\Exception\MagelinkException
     */
    public function setEntityParent( $child, $parent ) {

        if(is_object($parent)){
            if($parent instanceof \Entity\Entity){
                $parent = $parent->getId();
            }else{
                throw new NodeException('Invalid object type ' . get_class($parent) . ' passed to setEntityParent!');
            }
        }
        if(is_object($child)){
            if($child instanceof \Entity\Entity){
                $child = $child->getId();
            }else{
                throw new NodeException('Invalid object type ' . get_class($child) . ' passed to setEntityParent!');
            }
        }
        $this->getSaver()->setEntityParent($child, $parent);
    }
    
    /**
     * Updates the given entity with the provided data.
     * The $merge parameter represents whether the provided data should replace the existing data or be merged into it. If this is set to true all values that are already arrays will have the new data provided appended to the end, and any multi-type attributes will be run through array_merge with the new data taking precedence where keys are the same.
     * Alternatively, $merge can be provided as an associative array with the key being the attribute code, and the value being a boolean representing whether to merge or replace data. When specified individually this allows turning single values into array values. All non-specified keys default to false (i.e. replace only).
     * 
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @param array $data
     * @param boolean|array $merge
     * @throws MagelinkException
     */
    public function updateEntity ( $node_id, \Entity\Entity $entity, $data, $merge = false ) {
        $this->verifyNodeId($node_id);

        $allowedAttributes = $this->getServiceLocator()->get('nodeService')->getSubscribedAttributeCodes($node_id, $entity->getType(), true);
        foreach($data as $k=>$v){
            if(strlen(trim($k)) == 0){
                unset($data[$k]);
                continue;
            }
            if(!in_array($k, $allowedAttributes)){
                throw new NodeException('Invalid attribute specified for update ' . $k);
            }
        }
        $preData = $data;

        $transformedData = $this->getServiceLocator()->get('routerService')->processTransforms($entity, $data, $node_id, \Entity\Update::TYPE_UPDATE);
        foreach($transformedData as $k=>$v){
            if(is_array($merge) && array_key_exists($k, $merge)){
                $merge[$k] = false;
            }
            $data[$k] = $v;
        }
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUGEXTRA, 'update_tf', 'updateEntity - transform gave ' . count($transformedData) . ' updates for - ' . $entity->getId(), array('tfdata'=>$transformedData, 'predata'=>$preData), array('entity'=>$entity, 'node'=>$node_id));

        $attributes = $this->getSaver()->saveEntity($entity, $data, $merge);

        if(!count($attributes)){
            $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_WARN, 'update_same', 'updateEntity - All data was the same - ' . $node_id . ' - ' . $entity->getId(), array('data'=>$data), array('entity'=>$entity, 'node'=>$node_id));
        }else{
            $changedData = array();
            foreach($attributes as $att){
                $changedData[$att] = $data[$att];
            }
            $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_INFO, 'update', 'updateEntity - Keys updated - ' . $node_id . ' - ' . $entity->getId(), array('updated'=>$attributes, 'keys'=>array_keys($data), 'tfkeys'=>array_keys($transformedData)), array('entity'=>$entity, 'node'=>$node_id));
            $this->getServiceLocator()->get('routerService')->distributeUpdate($entity, $changedData, $node_id, \Entity\Update::TYPE_UPDATE);
        }
    }

    /**
     * Perform a silent update of data to an entity - nothing is logged nor processed. SHOULD NOT BE USED WITHOUT EXTREME REASON.
     *
     * @param \Entity\Entity $entity
     * @param array $data
     * @param array|bool merge
     * @return array
     */
    public function silentUpdateEntity(\Entity\Entity $entity, $data, $merge=false){
        return $this->getSaver()->saveEntity($entity, $data, $merge);
    }

    /**
     * Updates the given Entity's Unique ID to the provided value.
     * Not automatically distributed.
     *
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @param string $new_unique_id
     */
    public function updateEntityUnique( $node_id, \Entity\Entity $entity, $new_unique_id ) {
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_INFO, 'update_unique', 'updateEntityUnique - setting ID ' . $new_unique_id . ' for ' . $entity->getId(), array('new_id'=>$new_unique_id), array('entity'=>$entity, 'node'=>$node_id));

        $this->getSaver()->setEntityUnique($entity->getId(), $new_unique_id);
        $this->getSaver()->touchEntity($entity);
    }
    
    /**
     * Deletes the given Entity from the system.
     * Only works if this node is the only one linked to the Entity, or if no nodes are linked to the Entity. If there are remaining links an Exception will be thrown.
     * 
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @throws MagelinkException If other nodes are linked to this entity, or if invalid data is passed.
     */
    public function deleteEntity ( $node_id, \Entity\Entity $entity ) {
        $this->verifyNodeId($node_id);

        $res = $this->getTableGateway('entity_identifier')->select(array(
            'entity_id'=>$entity->getId(),
        ));
        foreach($res as $row){
            if($row['node_id'] == $node_id){
                continue; // Don't care about this node
            }
            throw new NodeException('Cannot delete entity ' . $entity->getId() . ' - still linked to node ' . $row['node_id']);
        }

        $this->getServiceLocator()->get('routerService')->processTransforms($entity, $entity->getAllData(), $node_id, \Entity\Update::TYPE_DELETE);
        $this->getServiceLocator()->get('routerService')->distributeUpdate($entity, array(), $node_id, \Entity\Update::TYPE_DELETE);
        $this->unlinkEntity($node_id, $entity);

        $this->getSaver()->deleteEntity($entity);

    }
    
    /**
     * Dispatch an action on a provided entity.
     *
     * @param int $node_id
     * @param \Entity\Entity $entity
     * @param string $action_type
     * @param array $actionData
     * @return bool indicator, if the action was successful
     */
    public function dispatchAction($nodeId, \Entity\Entity $entity, $actionType, $actionData)
    {
        $this->verifyNodeId($nodeId);

        return $this->getServiceLocator()->get('routerService')
            ->distributeAction($entity, $nodeId, $actionType, $actionData);
    }

    /**
     * Load all assigned Entity Comments for a given Entity
     * @param \Entity\Entity $entity
     * @return \Entity\Comment[]
     */
    public function loadEntityComments ( \Entity\Entity $entity ) {

        $res = $this->getTableGateway('entity_comment')->select(array('entity_id'=>$entity->getId()));

        $ret = array();
        foreach($res as $row){
            $ret[] = new \Entity\Comment($entity, (array)$row);
        }

        return $ret;
    }

    /**
     * Create a new Entity Comment
     *
     * @param \Entity\Entity $entity The Entity to attach the new comment to
     * @param string $source A description of where this comment came from (user name, automated process name, etc)
     * @param string $title The comment title
     * @param string $body The comment body
     * @param string $reference_id The entity-specific reference ID to compare this comment (optional)
     * @param bool $customer_visible Whether this comment should be visible to the customer (optional, default false)
     * @param int|bool $node_id The node ID of the creating node
     * @throws MagelinkException If we fail to create the comment
     * @return \Entity\Comment
     */
    public function createEntityComment ( \Entity\Entity $entity, $source, $title, $body, $reference_id='', $customer_visible=false, $node_id=false ) {
        $row = array(
            'entity_id'=>$entity->getId(),
            'reference_id'=>$reference_id,
            'timestamp'=>date('Y-m-d H:i:s'),
            'source'=>$source,
            'title'=>$title,
            'body'=>$body,
            'customer_visible'=>($customer_visible ? 1 : 0),
        );

        $res = $this->getTableGateway('entity_comment')->insert($row);
        $row['comment_id'] = $this->getAdapter()->getDriver()->getLastGeneratedValue();

        if(!$res || !$row['comment_id']){
            throw new MagelinkException('Error creating entity comment for ' . $entity->getId());
        }

        if($node_id){
            $this->dispatchAction($node_id, $entity, 'comment', array('source'=>$source, 'title'=>$title, 'body'=>$body, 'customer_visible'=>$customer_visible, 'timestamp'=>date('Y-m-d H:i:s'), 'comment_id'=>$row['comment_id']));
        }

        return new \Entity\Comment($entity, $row);
    }

    /**
     * Parse a MLQL query (for debugging)
     * @param string $mlql The MLQL to be parsed (see separate MLQL docs)
     * @return array
     */
    public function parseQuery($mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'parsemlql', 'parseQuery: ' . $mlql, array('query'=>$mlql));
        return $this->getQuerier()->parseQuery($mlql);
    }

    /**
     * Execute a MLQL query and return all rows as associative arrays
     * @param string $mlql The MLQL to be executed (see separate MLQL docs)
     * @throws MagelinkException If the MLQL is invalid or contains a syntax error
     * @return array
     */
    public function executeQuery($mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql', 'executeQuery: ' . $mlql, array('query'=>$mlql));
        try{
            $resp = $this->getQuerier()->executeQuery($mlql);
            $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_r', 'Result: ' . var_export($resp, true), array('ret'=>$resp));
            return $resp;
        }catch(\Exception $e){
            throw new MagelinkException('Error executing MLQL: ' . $mlql, 0, $e);
        }
    }

    /**
     * Execute a MLQL query and return the first column as an array
     * @param string $mlql The MLQL to be executed (see separate MLQL docs)
     * @throws MagelinkException If the MLQL is invalid or contains a syntax error
     * @return array
     */
    public function executeQueryColumn($mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_col', 'executeQueryColumn: ' . $mlql, array('query'=>$mlql));
        try{
            $data = $this->getQuerier()->executeQuery($mlql);
        }catch(\Exception $e){
            throw new MagelinkException('Error executing MLQL: ' . $mlql, 0, $e);
        }
        $ret = array();
        foreach($data as $row){
            $ret[] = array_shift($row);
        }
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_col_r', 'Result: ' . var_export($ret, true), array('ret'=>$ret));
        return $ret;
    }

    /**
     * Execute a MLQL query and return an associative array using the result columns k and v
     * @param string $mlql The MLQL to be executed (see separate MLQL docs)
     * @throws MagelinkException If the MLQL is invalid or contains a syntax error
     * @return array
     */
    public function executeQueryAssoc($mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_assoc', 'executeQueryAssoc: ' . $mlql, array('query'=>$mlql));
        try{
            $data = $this->getQuerier()->executeQuery($mlql);
        }catch(\Exception $e){
            throw new MagelinkException('Error executing MLQL: ' . $mlql, 0, $e);
        }
        $ret = array();
        foreach($data as $row){
            $ret[$row['k']] = $row['v'];
        }
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_assoc_r', 'Result: ' . var_export($ret, true), array('ret'=>$ret));
        return $ret;
    }

    /**
     * Execute a MLQL query and return the first column of the first row
     * @param string $mlql The MLQL to be executed (see separate MLQL docs)
     * @throws MagelinkException If the MLQL is invalid or contains a syntax error
     * @return mixed|null
     */
    public function executeQueryScalar($mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_sca', 'executeQueryScalar: ' . $mlql, array('query'=>$mlql));
        try{
            $resp = $this->getQuerier()->executeQueryScalar($mlql);
            $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_sca_r', 'Result: ' . var_export($resp, true), array('ret'=>$resp));
            return $resp;
        }catch(\Exception $e){
            throw new MagelinkException('Error executing MLQL: ' . $mlql, 0, $e);
        }
    }

    /**
     * Execute a MLQL query and return an array of entities - the query must return a column "entity_id".
     *
     * If any of the entities cannot be loaded, that array entry will be null.
     * The query may optionally return a column "key" which will be used as the array key.
     *
     * @param int $node_id The ID of the node executing this query
     * @param string $mlql The MLQL to be executed (see separate MLQL docs)
     * @throws MagelinkException If the MLQL is invalid or contains a syntax error
     * @return \Entity\Entity[]
     */
    public function executeQueryEntities($node_id, $mlql){
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_col', 'executeQueryColumn: ' . $mlql, array('query'=>$mlql));
        try{
            $data = $this->getQuerier()->executeQuery($mlql);
        }catch(\Exception $e){
            throw new MagelinkException('Error executing MLQL: ' . $mlql, 0, $e);
        }
        $ret = array();
        $ids = array();
        foreach($data as $row){
            $id = intval($row['entity_id']);
            $ids[] = $id;
            $ent = $this->loadEntityId($node_id, $id);
            if(isset($row['key'])){
                $ret[$row['key']] = $ent;
            }else{
                $ret[] = $ent;
            }
        }
        $this->getServiceLocator()->get('logService')->log(\Log\Service\LogService::LEVEL_DEBUG, 'execmlql_col_r', 'Result: ' . var_export($ids, true), array('ids'=>$ids));
        return $ret;
    }

    /**
     * Verify that given node ID is valid (and transform as needed)
     * 
     * @param int $node_id The node ID to process (by-reference)
     * @throws MagelinkException If the passed node ID is invalid
     * @return int The processed node ID
     */
    protected function verifyNodeId(&$node_id){
        if($node_id === 0){
            return 0;
        }
        if($node_id instanceof \Node\Entity\Node){
            $node_id = $node_id->getId();
        }
        if($node_id instanceof \Node\AbstractNode){
            $node_id = $node_id->getNodeId();
        }
        if($node_id <= 0 || !is_int($node_id)){
            throw new \Magelink\Exception\NodeException('Invalid node ID passed to EntityService');
        }

        return $node_id;
    }
    
    /**
     * Verify that given entity type is valid (and transform as needed)
     * 
     * @param int|string $entity_type The entity type to process (by-reference)
     * @throws MagelinkException If the passed entity type is invalid
     * @return int Processed entity type
     */
    protected function verifyEntityType(&$entity_type){
        $entity_type_in = $entity_type;
        //if($entity_type instanceof Entity\Model\Type){
        //    $entity_type = $entity_type->getId();
        //}
        if(is_string($entity_type)){
            $entity_type = $this->getServiceLocator()->get('entityConfigService')->parseEntityType($entity_type);
        }
        if($entity_type <= 0 || !is_int($entity_type)){
            throw new \Magelink\Exception\NodeException('Invalid entity type passed to EntityService - ' . $entity_type_in . ' - ' . $entity_type);
        }

        return $entity_type;
    }

    /**
     * Return the database adapter to be used to communicate with Entity storage.
     * @return \Zend\Db\Adapter\Adapter
     */
    protected function getAdapter(){
        return $this->getServiceLocator()->get('zend_db');
    }

    /**
     * Cache of preloaded table gateways
     * @var TableGateway[]
     */
    protected $_tgCache = array();

    /**
     * Returns a new TableGateway instance for the requested table
     * @param string $table
     * @return \Zend\Db\TableGateway\TableGateway
     */
    protected function getTableGateway($table){
        if(isset($this->_tgCache[$table])){
            return $this->_tgCache[$table];
        }
        $this->_tgCache[$table] = new TableGateway($table, $this->getServiceLocator()->get('zend_db'));
        return $this->_tgCache[$table];
    }

    /**
     * @var ServiceLocatorInterface The service locator
     */
    protected $_serviceLocator;

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->_serviceLocator;
    }


}