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

            // Create panel service
            $builder->addDefinition($this->prefix("panel"))
                ->setClass("UniMapper\Extension\NetteExtension\Panel")
                ->addSetup(
                    'Nette\Diagnostics\Debugger::$bar->addPanel(?)',
                    array('@self')
                )
                ->addSetup(
                    'Nette\Diagnostics\Debugger::$blueScreen->addPanel(?)',
                    array('UniMapper\Extension\NetteExtension::renderException')
                );

            // Create logger service
            $builder->addDefinition($this->prefix("logger"))
                ->setClass("UniMapper\Logger");
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        if ($builder->parameters["debugMode"]) {

            // Register panel
            $builder->getDefinition("application")
                ->addSetup(
                    '$service->onStartup[] = ?',
                    array(array($this->prefix("@panel"), "getTab"))
                );

            // Find registered repository services
            $panel = $builder->getDefinition($this->prefix("panel"));
            foreach ($builder->getDefinitions() as $serviceName => $serviceDefinition) {

                $class = $serviceDefinition->class !== NULL ? $serviceDefinition->class : $serviceDefinition->factory->entity;
                if (class_exists($class) && is_subclass_of($class, "UniMapper\Repository")) {


                    $builder->getDefinition($serviceName)->addSetup("setLogger");

                    // Register repository into the panel
                    $panel->addSetup('registerRepository', "@" . $serviceName);
                }
            }
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
        $configurator->onCompile[] = function ($config, \Nette\Config\Compiler $compiler) use ($class) {
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