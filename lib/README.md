## packages

Packages likely to cause conflicts with other plugins (by loading multiple incompatible versions).
Their namespaces are isolated by Mozart. Packages are committed directly to the repo.

To regenerate scoped packages (after updating versions in composer.json):

```bash
ddev composer require --dev coenjacobs/mozart:^0.7.1 -W
ddev exec vendor/bin/mozart compose
ddev composer remove --dev coenjacobs/mozart
ddev composer install
```

Mozart configuration is in `composer.json` under `extra.mozart`.
