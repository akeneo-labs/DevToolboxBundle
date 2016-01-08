# DevToolboxBundle
Akeneo PIM developper toolbox

This toolbox contains commands to:
* [Delete attributes](./Resources/doc/attribute-delete.md)
* [Change the scope of an attribute] (./Resources/doc/attribute-set-scopable.md)

## How to install

Modify your composer.json to:

Add the bundle VCS in the composer.json

```json
"repositories": [
    ...
    {
        "type": "vcs",
        "url": "https://github.com/akeneo-labs/DevToolboxBundle.git",
        "branch": "master"
    }
    ...
]
```

set the bundle as a requirement:

```json
"require": {
    ...
    "akeneo-labs/dev-toolbox-bundle": "v1.0.0"
    ...
}
```

Then you can launch the following command 
```shell
composer.phar require akeneo-labs/DevToolboxBundle
```

Now, you can add your new bundle in the app/AppKernel.php file
```php
$bundles[] = new Pim\Bundle\DevToolboxBundle\PimDevToolboxBundle();
```
