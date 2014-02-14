<?php

namespace UniMapper\Extension\NetteExtension;

class Panel implements \Nette\Diagnostics\IBarPanel
{

    protected $repositories = array();

    public function registerRepository(\UniMapper\Repository $repository)
    {
        $this->repositories[] = $repository;
    }

    public function getTab()
    {
        ob_start();
        require __DIR__ . "/Panel.tab.phtml";
        return ob_get_clean();
    }

    public function getPanel()
    {
        ob_start();
        $repositories = $this->repositories;
        require __DIR__ . "/Panel.panel.phtml";
        return ob_get_clean();
    }

}