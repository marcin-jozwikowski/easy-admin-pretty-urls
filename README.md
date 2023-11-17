# EasyAdmin Pretty URLs

### Symfony Bundle that introduces customizable routes to EasyAdmin

## Example
Turn
```
  http://ea-demo.loc/en/easyadmin?crudAction=index&crudControllerFqcn=App%5CController%5CEasyAdmin%5CPostCrudController
```
into
```
  http://ea-demo.loc/en/post_crud/index
```

---

## Instalation 

1. Install the bundle by running
   ```shell
   composer require marcin-jozwikowski/easy-admin-pretty-urls
   ```
   
1. Enable the bundle by adding it to your `config/bundles.php` if not enabled automatically
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
   The `resource` is a directory path relative to your projects root directory. Type must always equal to `pretty_routes`. See _Fine-tuning_ / _Define routes manually_ section to learn how this step can be ommitted.
   
   Other routing structures can be utilized as well, for example:
   ```yaml
    pretty_routes:
      resource: 'src/Controller'
      type: 'pretty_routes'
      prefix: /{_locale}
      requirements:
        _locale: '%app_locales%'
      defaults:
        _locale: '%locale%'
   ```

1. Make your main DashboardController extend `\MarcinJozwikowski\EasyAdminPrettyUrls\Controller\PrettyDashboardController` or manually override the a default template like so:
   ```php
   public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->overrideTemplate('layout', '@EasyAdminPrettyUrls/layout.html.twig')
            ->overrideTemplate('crud/field/association', '@EasyAdminPrettyUrls/crud/field/association.html.twig');
    }
   ```


## Configuration

The following parameters are in use:

  | Parameter            | Defalt value                                             | Description                                  | 
  |----------------------|---------------------------------------|----------------------------------------------|
  | `route_prefix`       | `pretty`                                                 | First part of route name                     |
  | `default_dashboard`  | `App\\Controller\\EasyAdmin\\DashboardController::index` | Controller action to invoke                  |
  | `include_menu_index` | `false`                                                  | Should menu index be included in path        |
  | `drop_entity_fqcn`   | `false`                                                  | Should `entityFqcn` be removed from the URLs |

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

## Twig

There are one function, and one filter being registered by a Twig extension in this bundle:

* `pretty_urls_include_menu_index()` Function returns the `include_menu_index` value from Configuration
* `|pretty_urls_remove_actions` Filter removed the unnecessary query elements from the URL string 

## Fine-tuning

* ### Define custom URL

  By default, the URL is created as `<class_name>/<action_name>`.

  To change that behavior specify `path` value in `PrettyRoutesController` attribute for the whole controller, and/or
  in `PrettyRoutesAction` attribute for the specific action.

  The following configuration will result in the action URL of `special/list` instead of the default `any_fancy/index`.
  ```php
  #[PrettyRoutesController(path: 'special')]
  class AnyFancyController {
  
    #[PrettyRoutesAction(path: 'list')]
    public function index() {
      // .... 
    }
  }
  ```

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
  * Route name must match the pattern `<prefix>_<name>_<action>` with 
    * `<action>` equal to `crudAction` value from the defaults
    * `<name>` being the target controller class name (not FQCN - just the last part) stripped of `Controller`, written in _snake_case_
    * `<prefix>` is set to `pretty` by default. See Configuration to ways to change it.
  * When routes are defined manually the _Installation step 3_ is not required.

  You can generate a YAML routes configuration for existing controllers for further manual modifications by running
  ```shell
    bin/console pretty-routes:dump <resource>
  ```

## Troubleshooting

* ### Routes not working

  If your routes are still not generated despite being added, check your logs for `'Pretty route not found'` with `debug` level. Those will list all the EasyAdmin routes that did not have their pretty counterparts.

  Most probably, there's some naming missmatch.

* ### Checking the Resource parsing results

  To see what is the outcome of parsing a `pretty_routes` Resource run the following command:
  ```shell
    bin/console pretty-routes:debug <resource>
  ```
