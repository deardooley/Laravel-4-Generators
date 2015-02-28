<?php namespace Way\Generators\Syntax;

use Way\Generators\Compilers\TemplateCompiler;
use Way\Generators\Filesystem\Filesystem;
use Way\Generators\InvalidModelRelationship;

class Model {

    /**
     * @var \Way\Generators\Filesystem\Filesystem
     */
    protected $file;

    /**
     * @var \Way\Generators\Compilers\TemplateCompiler
     */
    protected $compiler;

    protected $compiled;

    /**
     * @param Filesystem $file
     * @param TemplateCompiler $compiler
     */
    function __construct(Filesystem $file, TemplateCompiler $compiler)
    {
        $this->compiler = $compiler;
        $this->file = $file;
    }

    /**
     * Fetch the template of the schema
     *
     * @return string
     */
    protected function getTemplate()
    {
        return $this->file->get(__DIR__.'/../templates/model.txt');
    }

    protected function create(string $tableName, array $relationships=[])
    {
      $compiled = $this->compiler->compile($this->getTemplate(), []);

      $compiled = $this->replaceTableWith($this->table($tableName), $compiled);

      return $this->replaceRelationshipsWith($this->relationships($relationships), $compiled);

    }

    /**
     * Build the table definition if a table name is provided.
     * Empty otherwise
     *
     * @param array $relationships
     * @throws Exception
     * @return mixed|string
     */
    public function table($tableName)
    {
      if ($tableName) {
          return sprintf("protected \$table = '%s';", strtolower($tableName));
      } else {
          return '';
      }
    }

    /**
     * Build the source code for relationships based on the parsed
     * definitions.
     *
     * @param array $relationships
     * @throws Exception
     * @return mixed|string
     */
    public function relationships(array $relationships=[])
    {
      $output = '';

      foreach($relationships as $relationship)
      {
          $typeTokens = explode('(', $relationship['type']);
          if (count($typeTokens) > 1) {
            throw new InvalidModelRelationship('Invalid relationship definition '.$typeTokens[0].'. Please remove spaces from the argument list.');
            break;
          }

          $name = $relationship['field'];
          $type = $relationship['type'];

          $this->guardRelationship($type);

          if (in_array($type, ['morphMany', 'morphedToMany', 'morphedByMany', 'hasOne', 'belongsTo', 'hasMany', 'belongsToMany'] ))
          {
            // require two arguments
            if (isset($relationship['args']) && count($relationship['args']) >= 1)
            {
                $output .= sprintf(
                    "public function %s()\n\t{\n\t\treturn \$this->%s(%s)",
                    $name,
                    $relationship['type'],
                    $relationship['args']
                );
            }
            else
            {
              throw new InvalidModelRelationship('Invalid relationship definition '.$type.$relationship['args'].'. Please supply a relationship name on the referenced model.');
            }
          }
          else if ($type == 'morphTo')
          {
            // requires no args
            $output .= sprintf(
                "public function %s()\n\t{\n\t\treturn \$this->morphTo()",
                $name
            );
          }
          else if ($type == 'hasManyThrough')
          {
            if (isset($relationship['args']) && count($relationship['args']) > 1 )
            {
                $output .= sprintf(
                    "public function %s()\n\t{\n\t\treturn \$this->hasManyThrough(%s)",
                    $name,
                    $relationship['args']
                );
            } else {
              throw new InvalidModelRelationship('Invalid relationship definition '.$type.$relationship['args'].'. Please supply both an intermediate and target model for this relationship.');
            }
          }
          else
          {
            if (isset($relationship['args']) && count($relationship['args']) > 0)
            {
                $output .= sprintf(
                    "public function %s()\n\t{\n\t\treturn \$this->%s(%s)",
                    $name,
                    $relationship['type'],
                    $relationship['args']
                );
            } else {
              throw new InvalidModelRelationship('Invalid relationship definition '.$type.$relationship['args'].'. Please supply the target model for this relationship.');
            }
          }

          if (isset($relationship['decorators']))
          {
              $output .= $this->addDecorators($relationship['decorators']);
          }

          $output .= ";\n\t}\n\n\t";
      }

      return $output;
    }

    /**
     * @param $relationshipType
     * @throws Exception
     * @internal param array $relationshipType
     */
    protected function guardRelationship($relationshipType)
    {
        if (!in_array($relationshipType, ['hasOne', 'belongsTo', 'hasMany', 'belongsToMany', 'hasManyThrough', 'morphTo', 'morphTo()', 'morphMany', 'morphedToMany', 'morphedByMany']))
        {
            throw new InvalidModelRelationship('Invalid relationship ' . $relationshipType . '. Please define your relationship as one of "hasOne", "belongsTo", "hasMany", "belongsToMany", "hasManyThrough", "morphTo", "morphMany", "morphedToMany", or "morphedByMany."');
        }
    }

    /**
     * @param $decorators
     * @return string
     */
    protected function addDecorators($decorators)
    {
        $output = '';

        foreach ($decorators as $decorator) {
            $output .= sprintf("->%s", $decorator);

            // Do we need to tack on the parens?
            if (strpos($decorator, '(') === false) {
                $output .= '()';
            }
        }

        return $output;
    }

    /**
     * Replace $RELATIONSHIPS$ in the given template
     * with the provided relationship definitions
     *
     * @param $schema
     * @param $template
     * @return mixed
     */
    protected function replaceRelationshipsWith($relationships, $template)
    {
        return str_replace('$RELATIONSHIPS$', $relationships, $template);
    }

    /**
     * Replace $TABLE$ in the given template
     * with the provided table definition
     *
     * @param $schema
     * @param $template
     * @return mixed
     */
    protected function replaceTableWith($tableDef, $template)
    {
        return str_replace('$TABLE$', $tableDef, $template);
    }

}
