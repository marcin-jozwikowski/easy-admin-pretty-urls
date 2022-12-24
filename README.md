# EasyAdmin Pretty URLs

### Symfony Bundle that introduces customizable routes to EasyAdmin

---

## Instalation

1. Create a route that will replace your default EasyAdmin CRUD action.
    ```yaml
    fancy_index:
        path: /fancy-url
        controller: \App\Controller\GeneralController::index
        defaults:
            crudControllerFqcn: \App\Controller\FancyCrudController
            crudAction: index
    ```
   * `controller` value must point to your projects DashboardController
   * `defaults` `crudControllerFqcn` and `crudAction` must point to your target CRUD controller and its action.
   * `path` can be anything of your choosing
   * Route name must match the pattern `<name>_<action>` with `<action>` equal to `crudAction` and name being the target controller class name (not FQCN - just the last part) stripped of `Crud` and `Controller`, written in _snake_case_.
1. Install the bundle by running
   ```shell
   composer require marcin-jozwikowski/easyadmin-pretty-urls
   ```
1. Enable the bundle by adding it to your `./config/bundles.php`
   ```php
   [
   ...
    MarcinJozwikowski\EasyAdminPrettyUrls\EasyAdminPrettyUrlsBundle::class => ['all' => true],
   ]
   ```

## Troubleshooting

* ### Routes not working

  If your routes are still not generated despite being added, look into your logs for `'Pretty route not found'` with `debug` level. Those will list all the EasyAdmin routes that did not have their pretty counterparts.

  Most probably there's some naming missmatch.
