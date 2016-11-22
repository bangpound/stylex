<?php

namespace Stylex;

use Michelf\Markdown;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

class ServiceProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        // Load all data files
        if (file_exists($app['data'])) {
            $finder = $app['finder'];
            $finder->files()->in($app['data'])->name('*.yml');
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $data = $app['yaml']->parse($file->getContents());
                $app['twig']->addGlobal($file->getBasename('.yml'), $data);
            }
        }

        // Load all sample content
        $content = [];
        if (file_exists($app['content'])) {
            $finder = $app['finder'];
            $finder->files()->in($app['content'].'/*')->name('*.md');
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                list(, $data, $body) = explode("---\n", $file->getContents());
                $data = $app['yaml']->parse($data);
                $data['content'] = Markdown::defaultTransform($body);
                $content[basename(dirname($file->getPathname()))][] = $data;
                $content[basename(dirname($file->getPathname()))][$file->getBasename('.md')] = $data;
            }
        }
        $app['twig']->addGlobal('content', $content);

        $app->mount('/', $this->connect($app));
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        // Setup the front controller
        /*
         * @param $page
         * @param \Silex\Application|\Stylex\Application $app
         * @return mixed
         */
        $controller->get('/{page}', function ($page, Application $app) {
            $app['twig']->addGlobal('current_page', '/'.(($page == 'index') ? '' : $page));

            return $app->render($page.'.html');
        })->value('page', 'index');

        return $controller;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['yaml'] = function () {
            return new Parser();
        };

        $pimple['finder'] = $pimple->factory(function () {
            return new Finder();
        });
    }
}
