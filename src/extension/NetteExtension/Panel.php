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
        $count = 0;
        foreach ($this->repositories as $repository) {
            foreach ($repository->getLogger()->getQueries() as $query) {
                if ($query->result !== null) {
                    $count++;
                }
            }
        }
        require __DIR__ . "/Panel.tab.phtml";
        return ob_get_clean();
    }

    public function getPanel()
    {
        ob_start();
        require __DIR__ . "/Panel.panel.phtml";
        return ob_get_clean();
    }

}