<?php

/**
 * Date: 29/04/14
 * Time: 18:42
 * Author: Jean-Christophe Cuvelier <jcc@morris-chapman.com>
 */
class MCFAdminTabs
{

    var $tabs = array();
    var $contents = array();
    var $selected_tab;

    /** @var CMSModule $module */
    var $module;

    /**
     * @param CMSModule $module
     * @param array $params
     */
    public function __construct(&$module, array $params)
    {
        $this->selected = isset($params['active_tab']) ? $params['active_tab'] : null;
        $this->module = $module;
    }

    public function addTab($name, $title)
    {
        $this->tabs[trim($name)] = $title;
    }

    public function addTabs(array $tabs)
    {
        foreach ($tabs as $name => $title) {
            $this->addTab($name, $title);
        }
    }

    public function setTabContent($name, $content)
    {
        $this->contents[trim($name)] = $content;
    }

    public function getTabContent($name)
    {
        return isset($this->contents[trim($name)])?$this->contents[trim($name)]:null;
    }

    public function headers()
    {
        $headers = $this->module->StartTabHeaders();

        foreach($this->tabs as $name => $title)
        {
            $headers .= $this->module->SetTabHeader($name, $title, ($name === $this->selected));
        }

        $headers .= $this->module->EndTabHeaders();

        return $headers;
    }

    public function getTab($name)
    {
        return $this->module->StartTab($name) . $this->getTabContent($name) . $this->module->EndTab();
    }

    public function tabs()
    {
        $tabs = $this->module->StartTabContent();

        foreach($this->tabs as $name => $title)
        {
            $tabs .= $this->getTab($name);
        }

        $tabs .= $this->module->EndTabContent();

        return $tabs;
    }


} 