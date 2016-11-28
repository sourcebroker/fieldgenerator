# TYPO3 Extension ``fieldgenerator``

This extension allows to create content of one field based on other fields. 
Controlled fully by TCA settings. 
Support for nested property read. 
Useful to create record keywords for nested structures.
 
### Installation

Install the extension using composer ``composer require sourcebroker/fieldgenerator``.

### Usage

1) Find TCA config you want to modify and add configuration. Example:
    
           'fieldsGenerator' => [
                'repositoryClass' => SourceBroker\Recipes\Domain\Repository\RecipeRepository::class,
                'generate' => [
                    'keywords' => [
                        'fields' => 'name,sections.steps.description,sections.ingredients.name',
                    ]
                ]
            ],

        
2) The field is filled with content on record save (hook processDatamap_afterDatabaseOperations).
   
   You can also use cli command to initial generation of fields:
   
   For one table:
   
   ``  php ./typo3/cli_dispatch.phpsh extbase fieldgenerator:generatefortable tx_recipes_domain_model_recipe``
   
   For all tables:
   
   ``  php ./typo3/cli_dispatch.phpsh extbase fieldgenerator:generateforalltables``

### TODO

1) Remove need to set the repositoryClass in TCA config of "fieldsGenerator" section.