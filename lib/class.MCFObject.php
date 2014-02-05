<?php

abstract class MCFObject
{

    /**
     * @var
     */
    protected $id;
    /**
     * @var
     */
    protected $created_at;
    /**
     * @var
     */
    protected $created_by;
    /**
     * @var
     */
    protected $updated_at;
    /**
     * @var
     */
    protected $updated_by;

    /**
     * Database fields mapping
     * @var array
     */
    protected static $table_fields = array(
        'id' => 'I KEY AUTO',
        'created_at' => 'DT',
        'created_by' => 'I',
        'updated_at' => 'DT',
        'updated_by' => 'I',
    );

    /**
     * Database indexed fields
     * @var array
     */
    protected static $table_fields_indexes = array('id');

    const TABLE_NAME = '';

    /**
     * Table relations
     * Example:
     *  array(
     *      'relation_name' => array(
     *          'local' => 'local field',
     *          'remote' => 'remote field',
     *          'class' => 'Remote class'
     *      )
     * );
     * @var array
     */
    protected static $table_relations = array();
    // TODO
    // $cascade => List of child objects // Possibly db constraint ?
    // $forein_keys

    public static $blacklist = array(
        'tablefields'
    );

    public static function getTableFields()
    {
        if ($parent = get_parent_class(get_called_class())) {
            return $parent::getTableFields() + static::$table_fields;
        } else {
            return static::$table_fields;
        }
    }

    public static function retrieveTableFields()
    {
        $fields = array();

        foreach (static::getTableFields() as $field_name => $field_type) {
            $fields[] = $field_name . ' ' . $field_type;
        }
        return $fields;
    }

    public static function getTableFieldIndexes()
    {
        if ($parent = get_parent_class(get_called_class())) {
            return $parent::getTableFieldIndexes() + static::$table_fields_indexes;
        } else {
            return static::$table_fields_indexes;
        }
    }

    /**
     * @return
     */
    public function getId()
    {
        return $this->id;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }


    public function __get($name)
    {
        $method = 'get' . self::Camelise($name);

        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (method_exists($this, $name)) {
            return $this->$name();
        } elseif (property_exists($this, $name)) {

            return $this->$name;
        } else {
            throw new Exception('Property ' . $name . ' do not exists in ' . __CLASS__);
        }
    }

    public static function Camelise($name, $glue = '')
    {
        $words = explode('_', $name);
        foreach ($words as &$word) {
            $word = ucfirst($word);
        }
        return implode($glue, $words);
    }

    public function save($insert = false)
    {
        if (empty($this->id) || $insert) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    private function insert()
    {
        $db = cms_utils::get_db();
        $query = 'INSERT INTO ' . cms_db_prefix() . static::TABLE_NAME;

        $fields = array();
        $exclude = array();
        $values = array();

        if (!empty($this->id)) {
            $fields[] = 'id = ?';
            $values[] = $this->getId();
            $exclude[] = 'id';
        }

        $fields[] = 'created_at = NOW()';
        $exclude[] = 'created_at';

        $fields[] = 'created_by = ?';
        $values[] = get_userid(false);
        $exclude[] = 'created_by';

        $fields[] = 'updated_at = NOW()';
        $exclude[] = 'updated_at';

        $fields[] = 'updated_by = ?';
        $values[] = get_userid(false);
        $exclude[] = 'updated_by';


        foreach (static::getTableFields() as $field_name => $field_type) {
            if (!in_array($field_name, $exclude)) {
                $fields[] = $field_name . ' = ?';
                $values[] = $this->__get($field_name);
            }
        }

        $query .= ' SET ' . implode(', ', $fields);

        $result = $db->Execute($query, $values);

        if ($result === false) {
            throw new Exception('Impossible to execute query: ' . $query);
        }

        if (empty($this->id)) {
            $this->id = $db->Insert_ID();
        }

        return $this;
    }

    private function update()
    {
        $db = cms_utils::get_db();
        $query = 'UPDATE ' . cms_db_prefix() . static::TABLE_NAME;

        $fields = array();
        $exclude = array();
        $values = array();

        $exclude[] = 'id';
        $exclude[] = 'created_at';
        $exclude[] = 'created_by';

        $fields[] = 'updated_at = NOW()';
        $exclude[] = 'updated_at';

        $fields[] = 'updated_by = ?';
        $values[] = get_userid(false);
        $exclude[] = 'updated_by';

        foreach (static::getTableFields() as $field_name => $field_type) {
            if (!in_array($field_name, $exclude)) {
                $fields[] = $field_name . ' = ?';
                $values[] = $this->__get($field_name);
            }
        }

        $query .= ' SET ' . implode(', ', $fields) . ' WHERE id = ?';

        $values[] = $this->getId();

        $result = $db->Execute($query, $values);

        if ($result === false) {
            throw new Exception('Impossible to execute query: ' . $query);
        }

        return $this;
    }

    public function delete()
    {
        if ($this->getId()) {
            $db = cms_utils::get_db();
            $query = 'DELETE FROM ' . cms_db_prefix() . static::TABLE_NAME . ' WHERE id = ?';
            $result = $db->Execute($query, array($this->getId()));
            if ($result !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param MCFCriteria $c
     * @return array
     */
    public static function doSelect(MCFCriteria $c)
    {
        $db = cms_utils::get_db();
        $query = $c->buildQuery(cms_db_prefix() . static::TABLE_NAME);
        $result = $db->execute($query, $c->values);

        $entities = array();

        while ($result && ($row = $result->FetchRow())) {

            $entities[] = static::populate($row);
        }
        return $entities;
    }

    /**
     * @param MCFCriteria $c
     * @return int
     */
    public static function doCount(MCFCriteria $c)
    {
        $db = cms_utils::get_db();
        $query = $c->buildQuery(cms_db_prefix() . static::TABLE_NAME);
        $result = $db->execute($query, $c->values);

        if($result)
        {
            return (int) $result->NumRows();
        }
        return 0;
    }

    protected static function populate($row, $entity = null)
    {
        if(is_null($entity))
        {
            $entity = new static();
        }
        foreach (static::getTableFields() as $name => $type) {
            if (isset($row[$name])) $entity->__set($name, $row[$name]);
        }
        return $entity;
    }

    /**
     * @param MCFCriteria $c
     * @return mixed|null
     */
    public static function doSelectOne(MCFCriteria $c)
    {

        $c->setLimit(1);
        $result = static::doSelect($c);

        if (count($result) > 0) {
            reset($result);
            return current($result);
        } else {
            return null;
        }
    }

    public static function retrieveByPk($pk)
    {
        $c = new MCFCriteria();
        $c->add('id', $pk);
        return static::doSelectOne($c);
    }

    public static function retrieveById($id)
    {
        return static::retrieveByPk($id);
    }

    public static function createTable()
    {
        $db = cms_utils::get_db();
        $dict = NewDataDictionary($db);
        $sql = $dict->CreateTableSQL(cms_db_prefix() . static::TABLE_NAME, implode(',', static::retrieveTableFields()));
        $dict->ExecuteSQLArray($sql);

        $idxname = strtolower(static::TABLE_NAME) . '_indexes';
        $sqlarray = $dict->CreateIndexSQL($idxname, cms_db_prefix() . static::TABLE_NAME, implode(',', static::getTableFieldIndexes()));
        $dict->ExecuteSQLArray($sqlarray);
    }

    public static function deleteTable()
    {
        $db = cms_utils::get_db();
        $dict = NewDataDictionary($db);
        $sql = $dict->DropTableSQL(cms_db_prefix() . static::TABLE_NAME);
        $dict->ExecuteSQLArray($sql);
    }
}