<?php namespace Way\Generators\Syntax;

class CreateTable extends Table {

    /**
     * Build string for creating a
     * table and columns
     *
     * @param $migrationData
     * @param $fields
     * @return mixed
     */
    public function create($migrationData, $fields)
    {
        $migrationData = ['method' => 'create', 'table' => $migrationData['table']];

        // Let's add in the foreign key columns if they have not been
        // explicitly added by the user.
        $originalFields = array_slice($fields, 0);
        foreach($originalFields as $field)
        {
          if ( $field['type'] === 'foreign' )
          {
              if (!$this->hasForeignKeyColumn($field['field'], $originalFields))
              {
                  $foreignKeyColumn = [
                      'field' => $field['field'],
                      'type' => 'integer',
                      'decorators' => [ 'unsigned' ]];

                  array_unshift($fields, $foreignKeyColumn);
              }
          }
        }

        // All new tables should have an identifier
        // Let's add that for the user automatically
        array_unshift($fields, ['field' => 'id', 'type' => 'increments']);

        // We'll also add timestamps to new tables for convenience
        array_push($fields, ['field' => '', 'type' => 'timestamps']);


        return (new AddToTable($this->file, $this->compiler))->add($migrationData, $fields);
    }

    /**
     * Check for an existing column definition for an
     * existing foreign key name
     *
     * @param $columnName
     * @param $fields
     * @return mixed
     */
    private function hasForeignKeyColumn($columnName, $fields)
    {
      foreach(array_slice($fields, 0) as $index=>$field)
      {
        if ( $field['type'] === 'integer' && $field['field'] === $columnName )
        {
            return true;
        }
      }

      return false;
  }

}
