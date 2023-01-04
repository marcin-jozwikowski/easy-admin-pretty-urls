# EasyAdmin Pretty URLs

### Symfony Bundle that introduces customizable routes to EasyAdmin

---

## Instalation 
1. Install the bundle by running
   ```shell
   composer require marcin-jozwikowski/easy-admin-pretty-urls
   ```
1. Enable the bundle by adding it to your `config/bundles.php`
   ```php
   [
   ...
    MarcinJozwikowski\EasyAdminPrettyUrls\EasyAdminPrettyUrlsBundle::class => ['all' => true],
   ]
   ```
1. Add a routes set pointing to a directory containing your Controllers
   ```yaml
   pretty_routes_name:
    resource: 'src/Controller'
    type: 'pretty_routes'
   ```
   The `resource` is a directory path relative to your projects root directory. Type must always equal to `pretty_routes`. 

## Configuration

* ### Change route names' prefix

  To change the default `pretty` prefix used in route names set the parameter in your `services.yaml`
  ```yaml
    parameters:
      easy_admin_pretty_urls.route_prefix: 'new_prefix'
  ```

  Or create a `config/packages/easyadmin_pretty_urls.yaml` file with
  ```yaml
    easy_admin_pretty_urls:
      route_prefix: 'new_prefix'
  ```
* ### Define routes manually

  Create a route that will replace your default EasyAdmin CRUD action.
    ```yaml
    pretty_foobar_index:
        path: /foobar-url
        controller: \App\Controller\EasyAdmin\DashboardController::index
        defaults:
            crudControllerFqcn: \App\Controller\FoobarCrudController
            crudAction: index
    ```
    * `controller` value must point to your projects DashboardController
    * `defaults` `crudControllerFqcn` and `crudAction` must point to your target CRUD controller and its action.
    * `path` can be anything of your choosing
    * Route name must match the pattern `<prefix>_<name>_<action>` with `<action>` equal to `crudAction` and `<name>` being the target controller class name (not FQCN - just the last part) stripped of `Crud` and `Controller`, written in _snake_case_. `<prefix>` is set to `pretty` by default. See Configuration to ways to change it.
## Troubleshooting

* ### Routes not working

  If your routes are still not generated despite being added, look into your logs for `'Pretty route not found'` with `debug` level. Those will list all the EasyAdmin routes that did not have their pretty counterparts.

  Most probably there's some naming missmatch.
