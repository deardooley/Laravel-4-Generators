<?php namespace Way\Generators\Parsers;

class ModelRelationshipsParser {

    /**
     * Parse a string of relationsips, like
     * address:hasOne, tags:morphedByMany
     *
     * @param string $relationships
     * @return array
     */
    public function parse($relationships)
    {
        if ( ! $relationships) return [];

        // name:string, age:integer
        // name:string(10,2), age:integer
        $relationships = preg_split('/\s?,\s/', $relationships);

        $parsed = [];

        foreach($relationships as $index => $relationship)
        {
            // Example:
            // name:string:nullable => ['name', 'string', 'nullable']
            // name:string(15):nullable
            $chunks = preg_split('/\s?:\s?/', $relationship, null);

            // The first item will be our property
            $property = array_shift($chunks);

            // The next will be the schema type
            $type = array_shift($chunks);

            $args = null;

            // See if args were provided, like:
            // name:string(10)
            if (preg_match('/(.+?)\(([^)]+)\)/', $type, $matches))
            {
                $type = $matches[1];
                $args = $matches[2];
            } else if (preg_match('/(.+?)\(\)/', $type, $matches))
            {
              $type = $matches[1];
            }
            // Finally, anything that remains will
            // be our decorators
            $decorators = $chunks;

            $parsed[$index] = ['field' => trim($property), 'type' => trim($type)];

            if (isset($args)) $parsed[$index]['args'] = $args;
            if ($decorators) $parsed[$index]['decorators'] = $decorators;
        }

        return $parsed;
    }

}
