<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Command;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pretty-routes:dump',
    description: 'Dump all pretty routes from given resource in YAML format',
)]
class DumpRoutesDefinitions extends Command
{
    public function __construct(
        private PrettyRoutesLoader $prettyRoutesLoader,
        private bool $prettyUrlsIncludeMenuIndex,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $resource = (string) $input->getArgument('resource');
        $routes = $this->prettyRoutesLoader->load($resource);

        foreach ($routes as $routeName => $route) {
            $output->writeln($routeName.':');
            $output->writeln('  path: '.$route->getPath());
            if (!$input->getOption('skipController')) {
                $output->writeln('  controller: '.$route->getDefault('_controller'));
            }
            $output->writeln('  defaults:');
            $output->writeln('      crudControllerFqcn: '.$route->getDefault('crudControllerFqcn'));
            $output->writeln('      crudAction: '.$route->getDefault('crudAction'));
            if ($this->prettyUrlsIncludeMenuIndex) {
                $output->writeln('      menuPath: '.$route->getDefault('menuPath'));
            }
            $output->writeln('');
        }

        return self::SUCCESS;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configure(): void
    {
        $this
            ->addArgument('resource', InputArgument::REQUIRED, 'The resource to run generation for')
            ->addOption('skipController', 's', InputOption::VALUE_OPTIONAL, 'Should default controller be skipped', 0);
    }
}
