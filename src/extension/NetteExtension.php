<?php

namespace UniMapper\Extension;

use Nette\Diagnostics,
    UniMapper\Exceptions\PropertyException;

/**
 * Nette Framework extension.
 */
class NetteExtension extends \Nette\Config\CompilerExtension
{

    /** @var array $defaults Default configuration */
    public $defaults = array();

    /**
     * Processes configuration data
     *
     * @return void
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        if ($builder->parameters["debugMode"]) {
            $builder->getDefinition("application")
                ->addSetup(
                    'Nette\Diagnostics\Debugger::$blueScreen->addPanel(?)',
                    array(get_class() . '::renderException')
                );
        }
    }

    /**
     * Register extension
     *
     * @param \Nette\Configurator $configurator
     */
    public static function register(\Nette\Configurator $configurator)
    {
        $class = get_class();
        $configurator->onCompile[] = function ($config, \Nette\DI\Compiler $compiler) use($class) {
            $compiler->addExtension("unimapper", new $class);
        };
    }

    /**
     * Extend debugger bluescreen
     *
     * @param mixed $exception Exception
     *
     * @return array
     */
    public static function renderException($exception)
    {
        if ($exception instanceof PropertyException
            && $exception->getEntityPath() !== false
        ) {
            $link = Diagnostics\Helpers::editorLink(
                $exception->getEntityPath(),
                $exception->getEntityLine()
            );
            $code = Diagnostics\BlueScreen::highlightFile(
                $exception->getEntityPath(),
                $exception->getEntityLine()
            );
            return array(
                "tab" => "Entity",
                "panel" =>  $link . "\n" . $code
            );
	}
    }

}