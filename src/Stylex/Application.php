<?php

namespace Stylex;

use Silex\Application\TwigTrait;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\TwigServiceProvider;

class Application extends \Silex\Application
{
    use TwigTrait;
    use UrlGeneratorTrait;

    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $this->register(new TwigServiceProvider(), array(
          'twig.path' => $values['templates'],
        ));
        $this->register(new ServiceProvider());
    }
}
