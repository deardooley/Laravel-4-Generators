<?php namespace Way\Generators;

use Way\Generators\Filesystem\Filesystem;
use Way\Generators\Compilers\TemplateCompiler;
use Way\Generators\Syntax\Model;
use Exception;

class ModelCreator {

    /**
     * @var Filesystem\Filesystem
     */
    private $file;

    /**
     * @var Compilers\TemplateCompiler
     */
    private $compiler;

    /**
     * @var Way\Generators\Syntax\Model
     */
    private $modelSyntax;

    /**
     * @param Filesystem $file
     * @param TemplateCompiler $compiler
     */
    function __construct(Filesystem $file, TemplateCompiler $compiler)
    {
        $this->file = $file;
        $this->compiler = $compiler;

        $this->modelSyntax = new Model($this->file, $this->compiler);
    }

    /**
     * Returns optional table name
     *
     * @param string $tableName
     * @return string if $tableName, empty otherwise
     */
    public function table($tableName='')
    {
      return $this->modelSyntax->table($tableName);
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
      return $this->modelSyntax->relationships($relationships);
    }
}
