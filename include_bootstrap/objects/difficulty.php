<?php

class Difficulty extends DbObject
{
  public static string $table_name = 'difficulty';

  public string $name;
  public int $sort;

  #region Abstract Functions
  function get_field_set()
  {
    return array(
      'name' => $this->name,
      'sort' => $this->sort,
    );
  }
  static function static_field_set()
  {
    return [
      'name',
      'sort',
    ];
  }


  function apply_db_data($arr, $prefix = '')
  {
    $this->id = intval($arr[$prefix . 'id']);
    $this->name = $arr[$prefix . 'name'];
    $this->sort = intval($arr[$prefix . 'sort']);
  }

  protected function do_expand_foreign_keys($DB, $depth, $expand_structure)
  {
  }

  protected function get_expand_list($level, $expand_structure)
  {
    return [];
  }

  protected function apply_expand_data($data, $level, $expand_structure)
  {
  }
  #endregion

  #region Find Functions
  #endregion

  #region Utility Functions
  function __toString()
  {
    return "(Difficulty, id:{$this->id}, name:'{$this->name}')";
  }

  #endregion
}
