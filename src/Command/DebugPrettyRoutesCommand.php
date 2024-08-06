<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Command;

use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyRoutesLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pretty-routes:debug',
    description: 'Dry-run generating pretty routes for EasyAdmin from provided resource',
    aliases: ['debug:pretty-routes'],
)]
class DebugPrettyRoutesCommand extends Command
{
    public function __construct(
        private PrettyRoutesLoader $prettyRoutesLoader,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configure(): void
    {
        $this
            ->addArgument('resource', InputArgument::REQUIRED, 'The resource to run generation for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resource = (string) $input->getArgument('resource');
        $routes = $this->prettyRoutesLoader->load($resource);

        $table = new Table($output);
        $table->setHeaders(['Name', 'Path', 'CRUD Controller', 'CRUD Action']);
        foreach ($routes as $routeName => $route) {
            $table->addRow([
                $routeName,
                $route->getPath(),
                $route->getDefault('crudControllerFqcn'),
                $route->getDefault('crudAction'),
            ]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}
