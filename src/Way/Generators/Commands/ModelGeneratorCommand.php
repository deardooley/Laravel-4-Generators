<?php namespace Way\Generators\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Way\Generators\Parsers\ModelRelationshipsParser;
use Way\Generators\Generator;
use Way\Generators\ModelCreator;
use Config;


class ModelGeneratorCommand extends GeneratorCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a model';

    /**
     * @var \Way\Generators\ModelGenerator
     */
    protected $generator;

    /**
     * @var ModelWriter
     */
    private $modelCreator;

    private $modelRelationshipsParser;

    /**
     * @param Filesystem $file
     */
    public function __construct(
        Generator $generator,
        ModelRelationshipsParser $modelRelationshipsParser,
        ModelCreator $modelCreator
    )
    {
        $this->generator = $generator;
        $this->modelCreator = $modelCreator;
        $this->modelRelationshipsParser = $modelRelationshipsParser;

        parent::__construct($generator);
    }

    /**
     * Execute the console command
     */
    public function fire()
    {
        $filePathToGenerate = $this->getFileGenerationPath();

        try
        {
            $this->generator->make(
                $this->getTemplatePath(),
                $this->getTemplateData(),
                $filePathToGenerate,
                ($this->option('force') == 1)
            );

            $this->info("Created: {$filePathToGenerate}");
        }

        catch (FileAlreadyExists $e)
        {
            $this->error("The file, {$filePathToGenerate}, already exists! I don't want to overwrite it.");
        }

        // Now that the file has been generated,
        // let's run dump-autoload to refresh everything
        if ( ! $this->option('testing'))
        {
          //  $this->call('dump-autoload');
        }
    }

    /**
     * The path where the file will be created
     *
     * @return mixed
     */
    protected function getFileGenerationPath()
    {
        $path = $this->getPathByOptionOrConfig('path', 'model_target_path');

        return $path. '/' . ucwords($this->argument('modelName')) . '.php';
    }

    /**
     * Fetch the template data
     *
     * @return array
     */
    protected function getTemplateData()
    {
        // We also need to parse the model relationships, if provided
        //
        $relationships = $this->modelRelationshipsParser->parse($this->option('relationships'));
        // $this->info(print_r($relationships,1));
        return [
            'NAME' => ucwords($this->argument('modelName')),
            'TABLE' => $this->modelCreator->table($this->option('table')),
            'RELATIONSHIPS' => $this->modelCreator->relationships($relationships)
        ];
    }

    /**
     * Get path to the template for the generator
     *
     * @return mixed
     */
    protected function getTemplatePath()
    {
        return $this->getPathByOptionOrConfig('templatePath', 'model_template_path');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['modelName', InputArgument::REQUIRED, 'The name of the desired Eloquent model']
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
          ['path', null, InputOption::VALUE_REQUIRED, 'Where should the file be created?'],
          ['templatePath', null, InputOption::VALUE_REQUIRED, 'The location of the template for this generator'],
          ['table', null, InputOption::VALUE_OPTIONAL,'The name of the table this model represents'],
          ['relationships', null, InputOption::VALUE_OPTIONAL,'The foreign relationships to stub out'],
          ['testing', null, InputOption::VALUE_OPTIONAL, 'For internal use only.'],
          ['force', null, InputOption::VALUE_NONE, 'For internal use only.']
        );
    }

}
