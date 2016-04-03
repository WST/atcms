<?php

/**
* AT CMS version 2.0.0
* Система управления содержимым веб-сайта
* © 2007–2008 группа разработчиков AT CMS
* ----------------------------------------------------------------
* Главный разработчик: Илья “WatchRooster” Аверков
* Интерпретатор BBML от Алексея “shade” Золотова
* Многие идеи заимствованы из проекта ArigatoCMS,
* разработанного Алексеем “Arigato” Акатовым в 2006 г.
* Выражаю также благодарность следующим лицам:
* MayKoPSKiy, Erik, Вася Триллер
* ----------------------------------------------------------------
* http://shamangrad.net/project.php?act=view&prj=atcms2
* ----------------------------------------------------------------
* Лицензировано на условиях GNU GPL v 2.0,
* за более подробной информацией смотрите COPYING.
* ----------------------------------------------------------------
*
*/

defined('ATCMS') || die('Error');

class dbserver
{
	private $cnx = false; //Пожалуйста, не спрашивайте, почему именно так!
	public $db_queries_count = 0;
	private $die_function = 'die';
	private $dbname = '';
	private $server_is_new = false;
	private $stack = array(); //Стек
	private $i = 0;
	
	/**
	* Сообщение об ошибке MySQL и остановка выполнения
	*/
	
	private function dberr()
	{
		$note = '';
		if($this->cnx)
		{
			if(($men = mysql_errno($this->cnx)) !== 0)
			{
				$errdesc = $men .' '.mysql_error($this->cnx);
				if($men == 2002) $note = '<br>AT CMS note: the MySQL server seems not to be running!';
			}
			else $errdesc = 'unknown error';
		}
		else
		{
			if(($men = mysql_errno()) !== 0)
			{
				$errdesc = $men .' '.mysql_error();
				if($men == 2002) $note = '<br>AT CMS note: the MySQL server seems not to be running!';
			}
			else $errdesc = 'unknown error';
		}
		call_user_func($this->die_function, '<b>A MySQL error has occured</b><br>' . htmlspecialchars($errdesc) . $note);
	}
	
	/**
	* Конструктор
	*/

	public function __construct($host, $port, $user, $database, $password, $die_function = 'die')
	{
		function_exists('mysql_connect') || $die_function('MySQL extension was not found!');
		$this->die_function = $die_function;
		$this->dbname = $database;
		$host = empty($port) ? $host : $host.':'.$port;
		($this->cnx = @mysql_connect($host, $user, $password)) || $this->dberr();
		$c = $this->db_query('SELECT version() AS ver');
		$ver = $this->db_result($c, 0, 'ver');
		$ver = explode('.', $ver);
		if($ver[0] == 4 && $ver[1] >=1 || $ver[0] >= 5) // в паскале ещё нужно писать then begin %)
		{
			$this->db_query('SET NAMES utf8');
			$this->db_query('SET character_set_client=UTF8');
			$this->server_is_new = true;
		} // end; %)))
		$this->db_freeresult($c);
		@mysql_select_db($database, $this->cnx) || $this->dberr();
	}
	
	/**
	* Отсоединение от MySQL
	*/
	
	public function db_close()
	{
		@mysql_close($this->cnx);
	}
	
	/**
	* Запрос в MySQL и инкремент счётчика запросов
	* @param string SQL-запрос
	* @retval bool признак успешного выполнения
	*/

	public function db_query($qstr)
	{
		($retval = @mysql_query($qstr, $this->cnx)) || $this->dberr($qstr);
		$this->stack[++$this->i] = & $retval;
		$this->db_queries_count++;
		return $retval;
	}
	
	/**
	* Упрощённая выборка (на всякий случай)
	*/
	
	public function dbx_select($table, $cols, $where='', $orderby='', $ordertype='ASC', $limit=0, $offset=0)
	{
		$sql = 'SELECT ' . (is_array($cols) ? implode(', ', $cols) : $cols) . ' FROM ' . $table;
		if($where !== '') $sql = ' WHERE ' . $where;
		if($orderby !== '') $sql .= ' ORDER BY ' . $orderby . ' ' . $ordertype;
		if($limit !== 0) $sql .= ' ' . $this->db_limit($offset, $limit);
		return $this->db_query($sql);
	}
	
	/**
	* Освободить память от результата запроса
	* @param resource результат запроса
	* @retval boolean признак успешной очистки
	*/
	
	public function db_freeresult($query_result)
	{
		return mysql_free_result($query_result);
	}
	
	/**
	* Создание таблицы (для совместимости с PostgreSQL)
	* @param string имя создаваемой таблицы
	* @param array список столбцов (TODO: формат будет описан в руководстве по API)
	* @todo ^
	* @param string имя столбца для первичного ключа
	* @retval bool признак успешного выполнения
	*/
	
	public function create_table($table_name, $cols, $pk, $uk)
	{
		$sql = 'CREATE TABLE ' . $table_name . ' (';
		$tocreate = array();
		foreach($cols as $cname=>$ctype)
		{
			$tocreate[] .= '`' . $cname. '` ' . $ctype;
		}
		$sql .= implode(', ', $tocreate);
		if(!empty($pk) && isset($cols[$pk])) $sql .= ', PRIMARY KEY (`' . $pk . '`)';
		if(!empty($uk) && isset($cols[$uk])) $sql .= ', UNIQUE KEY `' . $uk . '` (`' . $uk . '`)';
		$sql .= ')';
		if($this->server_is_new) $sql .= ' DEFAULT CHARSET=utf8 TYPE=MyISAm';
		
		return $this->db_query($sql);
	}
	
	/**
	* Удалить таблицу или список таблиц
	* @param mixed название таблицы или массив названий таблиц для удаления
	* @retval bool признак успешного удаления
	*/
	
	public function db_delete_table($table)
	{
		if(is_array($table))
		{
			$table = implode(', ', $table);
			$sql = 'DROP TABLE ' . $table;
		}
		else
		{
			$sql = 'DROP TABLE ' . $table;
		}
		return $this->db_query($sql);
	}
	
	/**
	* Создание ассоциативного и нумерованного массива из текущего ряда результата
	* @param resource результат запроса
	* @retval array результирующий массив
	*/

	public function db_fetcharray($query_result = NULL)
	{
		if(is_null($query_result))
		{
			$retval = mysql_fetch_array($this->stack[$this->i], MYSQL_BOTH);
			if($retval === false)
			{
				$this->db_freeresult($this->stack[$this->i--]);
			}
		}
		else
		{
			$retval = mysql_fetch_array($query_result, MYSQL_BOTH);
		}
		return $retval;
	}
	
	/**
	* Создание ассоциативного массива из текущего ряда результата
	* @param resource результат запроса
	* @retval array результирующий массив
	*/
	
	public function db_fetchassoc($query_result = NULL)
	{
		if(is_null($query_result))
		{
			$retval = mysql_fetch_assoc($this->stack[$this->i]);
			if($retval === false)
			{
				$this->db_freeresult($this->stack[$this->i--]);
			}
		}
		else
		{
			$retval = mysql_fetch_assoc($query_result);
		}
		return $retval;
	}
	
	/**
	* Создание нумерованного массива из текущего ряда результата
	* @param resource результат запроса
	* @retval array результирующий массив
	*/
	
	public function db_fetchrow($query_result = NULL)
	{
		if(is_null($query_result))
		{
			$retval = mysql_fetch_row($this->stack[$this->i]);
			if($retval === false)
			{
				$this->db_freeresult($this->stack[$this->i--]);
			}
		}
		else
		{
			$retval = mysql_fetch_row($query_result);
		}
		return $retval;
	}
	
	/**
	* Получить число строк, содержащихся в результате запроса
	* @param resource результат запроса
	* @retval int число строк
	*/

	public function db_numrows($query_result)
	{
		return mysql_num_rows($query_result);
	}
	
	/**
	* Получить число строк, содержащихся в результате запроса
	* @param resource результат запроса
	* @retval int число строк
	*/
	
	public function db_escape($str)
	{
		return function_exists('mysql_real_escape_string') ? mysql_real_escape_string($str, $this->cnx) : addslashes($str);
	}
	
	/**
	* Получить одну клетку результата
	* @param resource результат запроса
	* @param int номер строки
	* @param mixed номер или название столбца
	* @retval mixed содержимое ячейчи
	*/
	
	public function db_result($query_result, $row, $column)
	{
		return @mysql_result($query_result, $row, $column);
	}
	
	/**
	* Получить LIMIT (для совместимости с PostgreSQL)
	* @author © Василий Триллер, 2006 год
	* @note взято из движка конференций dbB с минимальными изменениями
	* @param int OFFSET выборки
	* @param int LIMIT выборки
	* @retval string требуемый SQL-код
	*/
	
	public function db_limit($start, $cnt)
	{
		if($start == 0) return 'LIMIT ' . $cnt;
		else return 'LIMIT ' . $start . ', ' . $cnt;
	}
	
	/**
	* Автоматически сгенерированное в ходе последнего запроса значение PK
	* @retval int ID
	*/
	
	public function db_insert_id()
	{
		return mysql_insert_id($this->cnx);
	}
	
	/**
	* Получить число строк, изменённых в ходе запроса
	* @param resource результат запроса
	* @retval int число строк
	*/
	
	public function db_affected_rows($query_result)
	{
		return mysql_affected_rows($this->cnx);
	}
	
	/**
	* 
	*/
	
	public function db_fetchfield($query_result)
	{
		$retval = array();
		$f = mysql_fetch_field($query_result);
		
		$retval['name'] = $f->name;
		$retval['type'] = $f->type;
		
		return $retval;
	}
	
	public function db_numfields($query_result)
	{
		return mysql_num_fields($query_result);
	}
	
	/**
	* Получить заголовок столбца результата
	* @param resuorce результат запроса
	* @param int номер столбца
	* @retval string заголовок столбца
	*/
	
	public function db_fieldname($query_result, $offset)
	{
		return mysql_field_name($query_result, $offset);
	}
	
	public function db_countrows($table, $column = NULL, $where = '')
	{
		$sql = 'SELECT count(' . (is_null($column) ? '*' : $column) . ') AS cnt FROM ' . $table . ($where == '' ? '' : ' WHERE ' . $where);
		$c = $this->db_query($sql);
		$retval = $this->db_result($c, 0, 'cnt');
		$this->db_freeresult($c);
		
		return $retval;
	}
	
	/**
	* Выполнить оптимизацию (дефрагментацию) БД
	* @retval bool const true
	*/
	
	public function db_optimize_tables()
	{
		/// TODO: делать дефрагментацию только реально нуждающихся в оной таблиц
		for($d=mysql_list_tables($this->dbname, $this->cnx); $d_res=mysql_fetch_row($d); true)
		{
			$sql = 'OPTIMIZE TABLE ' . $d_res[0];
			$this->db_query($sql);
		}
		$this->db_freeresult($d);
		return true;
	}
	
	public function db_execute($text)
	{
		$scheme = atcms_parse_scheme_data($text);
		foreach($scheme as $k=>$v)
		{
			$pk = NULL;
			$uk = NULL;
			if(isset($v['__PK']))
			{
				$pk = $v['__PK'];
				unset($v['__PK']);
			}
			if(isset($v['__UK']))
			{
				$uk = $v['__UK'];
				unset($v['__UK']);
			}
			$this->create_table(DB_TABLES_PREFIX . $k, $v, $pk, $uk);
		}
	}
	/*
	public function swap_records($table, $id_column, $column, $val1, $val2)
	{
		if($val1 === $val2) return false;
		$sql = 'SELECT ' . $id_column . ' FROM ' . $table . ' WHERE pos=' . $val2;
		$nr_id_q = $this->db->db_query($sql);
		$nr_id = $this->db->db_result($nr_id_q, 0, 'id_element');
		$this->db->db_freeresult($nr_id_q);
		return ($this->db->db_query('UPDATE ' . STRUCTURE_TABLE . ' SET pos=' . $val2 . ' WHERE parent=' . $parent . ' AND pos=' . $val1) && $this->db->db_query('UPDATE ' . STRUCTURE_TABLE.' SET pos=' . $val1 . ' WHERE parent=' . $parent . ' AND id_element=' . $nr_id));
	}
	*/
}
?>
