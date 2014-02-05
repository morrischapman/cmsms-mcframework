<?php

class MCFCriteria {

	var $order_by = array();
	var $conditions = array();
	var $conditions_or = array();
	var $filters = array();
	var $group_by = array();
	var $selected_columns = array();
	
	var $limit;
	var $offset;
	
	var $values = array();
	
	// CONST
	
  /** Comparison type. */
  const EQUAL = "=";
  const NOT_EQUAL = "<>";
  const ALT_NOT_EQUAL = "!=";
  const GREATER_THAN = ">";
  const LESS_THAN = "<";
  const GREATER_EQUAL = ">=";
  const LESS_EQUAL = "<=";
  const LIKE = " LIKE ";
  const MULTILIKE = " MULTILIKE ";
  const MULTINOTLIKE = " MULTINOTLIKE ";
  const NOT_LIKE = " NOT LIKE ";
  const UPCOMING = " UPCOMING ";
  const DATE_UPCOMING = " DATE_UPCOMING ";
  const PAST = " PAST ";
  const PAST_OR_NOW = " PAST_OR_NOW ";
  const DATE_PAST_OR_NOW = " DATE_PAST_OR_NOW ";

  /** PostgreSQL comparison type */
  const ILIKE = " ILIKE ";

  /** PostgreSQL comparison type */
  const NOT_ILIKE = " NOT ILIKE ";

  /** Comparison type. */
  const CUSTOM = "CUSTOM";

  /** Comparison type. */
  const DISTINCT = "DISTINCT ";

  /** Comparison type. */
  const IN = " IN ";

  /** Comparison type. */
  const NOT_IN = " NOT IN ";

  /** Comparison type. */
  const ALL = "ALL ";

  /** Comparison type. */
  const JOIN = "JOIN";

  /** Binary math operator: AND */
  const BINARY_AND = "&";

  /** Binary math operator: OR */
  const BINARY_OR = "|";

  /** "Order by" qualifier - ascending */
  const ASC = "ASC";

  /** "Order by" qualifier - descending */
  const DESC = "DESC";

  /** "IS NULL" null comparison */
  const ISNULL = " IS NULL ";

  /** "IS NOT NULL" null comparison */
  const ISNOTNULL = " IS NOT NULL ";

  const ISNOTEMPTY = ' IS NOT EMPTY ';
  const ISEMPTY = ' IS EMPTY ';

  /** "CURRENT_DATE" ANSI SQL function */
  const CURRENT_DATE = "CURRENT_DATE";

  /** "CURRENT_TIME" ANSI SQL function */
  const CURRENT_TIME = "CURRENT_TIME";

  /** "CURRENT_TIMESTAMP" ANSI SQL function */
  const CURRENT_TIMESTAMP = "CURRENT_TIMESTAMP";

  /** "LEFT JOIN" SQL statement */
  const LEFT_JOIN = "LEFT JOIN";

  /** "RIGHT JOIN" SQL statement */
  const RIGHT_JOIN = "RIGHT JOIN";

  /** "INNER JOIN" SQL statement */
  const INNER_JOIN = "INNER JOIN";

	public function __construct()
	{
	  return $this;
	}

	public function addAscendingOrderByColumn($column)
	{
		$this->order_by[] = array ('column' => $column, 'order' => self::ASC);
	}
	
	public function addDescendingOrderByColumn($column)
	{
		$this->order_by[] = array ('column' => $column, 'order' => self::DESC);
	}
	
	public function addSelectColumn($column_name)
	{
		$this->selected_columns[] = $column_name;
	}
	
	public function addGroupByColumn($column_name)
	{
		$this->group_by[] = $column_name;
	}
	
	public function add($column, $value, $condition = self::EQUAL, $extra_query = '')
	{
		if ($condition == self::IN) {
			if (empty($value)) {
				$column = 1;
				$value = -1;
				$condition = self::EQUAL;
			}
		}
		$this->conditions[] = array ('column' => $column, 'value' => $value, 'condition' => $condition, 'extra_query' => $extra_query);
	}
	
	public function addOr($column, $value, $condition = self::EQUAL, $extra_query = '')
	{
	  if ($condition == self::IN) {
			if (empty($value)) {
				$column = 1;
				$value = -1;
				$condition = self::EQUAL;
			}
		}
		$this->conditions_or[] = array ('column' => $column, 'value' => $value, 'condition' => $condition, 'extra_query' => $extra_query);
	}
	
	
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}
	
	public function setOffset($offset) {
		$this->offset = $offset;
	}
	
	// REQUEST BUILDING
  
  public function buildCondition($condition)
  {
    switch ($condition['condition']) {
      case ' IN ':
        $db = cms_utils::get_db();
        foreach ($condition['value'] as &$value) {
          // $value = "'" . mysqli_real_escape_string($value) . "'";
          $value = $db->qstr($value);
        }
        unset($value);
        $query = $condition['column'] . ' IN (' . implode(',', $condition['value']) . ')';
        break;
      case ' MULTILIKE ':
        $values = explode(',',$condition['value']);
        $queries = array();
        foreach($values as $value)
        {
          $queries[] = $this->buildMultiLike($condition['column'], $value);
        }        
        $query = '(' . implode(' OR ', $queries) . ')';

        break;
      case ' MULTINOTLIKE ':
        $values = explode(',',$condition['value']);
        $queries = array();
        foreach($values as $value)
        {
          $queries[] = $this->buildMultiLike($condition['column'], $value, ' AND ', 'NOT');
        }        
        $query = '(' . implode(' AND ', $queries) . ')';

        break;
      case ' IS EMPTY ':
        $query =  '(' . $condition['column'] . ' IS NULL OR ' . $condition['column'] . ' = ? )';
        $this->values[] = '';
        break;
      case ' IS NOT EMPTY ':
        $query =  '(' . $condition['column'] . ' IS NOT NULL AND ' . $condition['column'] . ' != ? )' ;
        $this->values[] = '';
        break;
      case ' IS NULL ':
      case ' IS NOT NULL ':
        $query = $condition['column'] . $condition['condition'];
        break;
      case ' UPCOMING ':
        $query = $condition['column'] . ' >= NOW()';
        break;
      case ' DATE_UPCOMING ':
        $query = $condition['column'] . ' >= DATE(NOW())';
        break;
      case ' PAST ':
        $query = $condition['column'] . ' < NOW()';
        break;
      case ' PAST_OR_NOW ':
        $query = $condition['column'] . ' <= NOW()';
        break;
      case ' DATE_PAST_OR_NOW ':
        $query = $condition['column'] . ' <= DATE(NOW())';
        break;
      default:
        $query = $condition['column'] . ' ' . $condition['condition'] . ' ?';
        $this->values[] = $condition['value'];
        break;
    }

    if(isset($condition['extra_query']))
    {
      $query = '(' . $query . ' ' . $condition['extra_query'] . ')';
    }

    return $query;
  }

  public function buildMultiLike($column, $value, $andor = ' OR ', $not = '')
  {
    $conditions = array();
    $conditions[] = $column . ' ' . $not . ' LIKE ?';
    $this->values[] = $value;
    $conditions[] = $column . ' ' . $not . ' LIKE ?';
    $this->values[] = $value . '|||%';
    $conditions[] = $column . ' ' . $not . ' LIKE ?';
    $this->values[] = '%|||' . $value;
    $conditions[] = $column . ' ' . $not . ' LIKE ?';
    $this->values[] = '%|||' . $value . '|||%';

    return '('. implode($andor, $conditions) . ')';
    
    // $query = ' (';
    // $query .= $column . ($not)?' NOT LIKE ?':' LIKE ?';
    // $this->values[] = $value;
    // $query .= ($not)?' AND ':' OR ';
    // $query .= $column . ($not)?' NOT LIKE ?':' LIKE ?';
    // $this->values[] = $value . '|||%';
    // $query .= ($not)?' AND ':' OR ';
    // $query .= $column . ($not)?' NOT LIKE ?':' LIKE ?';
    // $this->values[] = '%|||' . $value;
    // $query .= ($not)?' AND ':' OR ';
    // $query .= $column . ($not)?' NOT LIKE ?':' LIKE ?';
    // $this->values[] = '%|||' . $value . '|||%';
    // $query .= ') ';
    // 
    // return $query;
  }

  public  function buildConditions($table_name = null)
  {
    /* FIXME: Proceed with all the possible conditions */

    $query = null;
    // WHERE

    if (count($this->conditions) > 0)
    {
      $query .= ' WHERE ';
      
      $conditions = array();

      foreach ($this->conditions as $condition)
      {
        $conditions[] = $this->buildCondition($condition);
      }
      
      $query .= implode(' AND ', $conditions);
    }
    
    // OR CONDITIONS
    if (count($this->conditions_or) > 0)
    {
      if (count($this->conditions) > 0)
      {
        $query .= ' AND ';
      }
      else
      {
        $query .= ' WHERE ';
      } 
        $conditions_or = array();

        foreach ($this->conditions_or as $condition)
        {
          $conditions_or[] = $this->buildCondition($condition);
        }

        $query .= '(' . implode(' OR ', $conditions_or) . ')';
    }
    
    
    // GROUP BY

    if(count($this->group_by) > 0)
    {
      $query .= ' GROUP BY ' . implode (', ', $this->group_by);
    }
    
    // ORDER BY
    
    if (count($this->order_by) > 0)
    {
      $query .= ' ORDER BY ';
      
      $orders = array();
      
      foreach ($this->order_by as $order_by)
      {
        $orders[] = $order_by['column'] . ' ' . $order_by['order'];
      }
      
      $query .= implode(' ,', $orders);
    }


    $offset = $this->offset ? $this->offset : 0;
    $limit = $this->limit ? $this->limit : PHP_INT_MAX;
    $query .= ' LIMIT ' .$offset.','.$limit;
    return $query;
  }

	public function buildQuery($table_name, $column_with_db = false)
	{
		$query = 'SELECT ';
		
		if (count($this->selected_columns) > 0)
		{
			foreach ($this->selected_columns as $column) 
			{
				$query .= $column . ' ';
			}
		}
		else 
		{
		  if($column_with_db)
		  {
		   $query .= ' ' . $table_name . '.* '; 
		  }
		  else
		  {		   
  			$query .= ' * '; 
		  }
		}
		
		$query .= 'FROM ' . $table_name;
				
		$query .= $this->buildConditions($table_name);
		return $query;		
	}

	public function getValues() {
		return $this->values;
	}

}

?>