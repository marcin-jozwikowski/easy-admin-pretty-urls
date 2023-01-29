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

The following parameters are in use:

  | Parameter | Defalt value                                             | Description                           | 
  |----------------------------------------------------------|---------------------------------------| ----------- |
  | `route_prefix` | `pretty`                                                 | First part of route name              |
  | `default_dashboard` | `App\\Controller\\EasyAdmin\\DashboardController::index` | Controller action to invoke           |
  | `include_menu_index` | `false`                                                  | Should menu index be included in path |

  To change the default values set the parameter in your `services.yaml`
  ```yaml
    parameters:
      easy_admin_pretty_urls.<parameter>: '<new_value>'
  ```

  Or create a `config/packages/easyadmin_pretty_urls.yaml` file with
  ```yaml
    easy_admin_pretty_urls:
      <parameter>: '<new_value>'
  ```

## Fine-tuning

* ### Select actions to create routes for

  By default pretty routes are generated for `index`, `new`, `detail`, `edit`, and `delete` actions.
  
  To change that, add a `PrettyRoutesController` attribute to the controller you want to modify and list the actions you want to have pretty routes in `actions` parameter.
  ```php
  #[PrettyRoutesController(actions: ['index', 'foo', 'bar'])]
  class AnyFancyController {
  ...
  ```

* ### Define routes manually
  Instead of defining a `pretty_routes` routes to automatically parse all classes in a directory you can ceate routes that will replace your default EasyAdmin CRUD actions.
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

* ### Checking the Resource parsing results

  To see what is the outcome of parsing a `pretty_routes` Resource run the following command:
  ```shell
    bin/console debug:pretty-routes <resource>
  ```
