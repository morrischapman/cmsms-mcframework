<?php
/**
 * Date: 10/03/14
 * Time: 13:05
 * Author: Jean-Christophe Cuvelier <jcc@morris-chapman.com>
 */

class MCFPager {

    var $limit = 0;
    var $offset = 0;

    var $page = 1;
    var $pages;
    var $total_items;
    var $size = 5;

    /** @var CMSModule $module */
    var $cmsmodule;
    var $parameters;
    var $id;
    var $returnid;
    /** @var MCFCriteria $criteria */
    var $criteria;

    public function __construct($cmsmodule, MCFCriteria $criteria, $params, $id, $returnid)
    {
        $this->cmsmodule = $cmsmodule;
        $this->criteria = $criteria;
        $this->id = $id;
        $this->returnid = $returnid;

        $this->parseParameters($params);
    }

    private function parseParameters($params)
    {
        if(isset($params['limit']))
        {
            $this->limit = $params['limit'];
            unset($params['limit']);
        }

        if(isset($params['pager_limit']))
        {
            $this->limit = $params['pager_limit'];
            unset($params['pager_limit']);
        }

        if(isset($params['pager_page']))
        {
            $this->page = $params['pager_page'];
            unset($params['pager_page']);
        }

        if(isset($params['pager_size']))
        {
            $this->size = $params['pager_size'];
            unset($params['pager_size']);
        }

        $this->parameters = $params;
    }

    public function countItems()
    {
        if(empty($this->total_items))
        {
            $c = clone($this->criteria);
            $c->setLimit(0);
            $c->setOffset(0);

            $this->total_items = call_user_func($this->cmsmodule->GetObjectName() . '::doCount', $c);
        }
        return $this->total_items;
    }

    public function countPages()
    {
        if(empty($this->pages))
        {
            $this->pages = ceil($this->countItems() / $this->limit);
        }
        return $this->pages;
    }

    private function hasToPaginate()
    {
        return $this->countPages() > 1;
    }

    public function toArray()
    {
        $pager_array = array();
        $pager_array['has_to_paginate'] = $this->hasToPaginate();
        $pager_array['current'] = $this->page;
        $pager_array['total_pages'] = $this->countPages();
        $pager_array['total_results'] = $this->countItems();

//        $pager_array['pages'] = $this->buildPagesLinks($pager_array);
        $this->buildPagesLinks($pager_array);

        return $pager_array;
    }

    private function buildPagesLinks(&$pager_array)
    {
        $pages = array();

        $start = $this->page - floor($this->size / 2);
        if($start < 1) $start = 1;

        if($start > 1)
        {
            $pager_array['less'] = '...';
        }

        $end = $start + $this->size - 1;
        if($end > $this->countPages())
        {
            $end = $this->countPages();
        }

        for($i = $start; $i <= $end; $i++)
        {
            $pager_array['pages'][] = ($i == $this->page) ? $i : $this->cmsmodule->createLink($this->id, 'default', $this->returnid, $i,
                $this->cmsmodule->ParamsForLink($this->parameters, array(
                        'pager_limit' => $this->limit,
                        'pager_size' => $this->size,
                        'pager_page' => $i
                    ))
                , '');
        }

        if($end < $this->countPages())
        {
            $pager_array['more'] = '...';
        }



        $pager_array['first_page'] = ($this->page > 1) ? $this->cmsmodule->createLink($this->id, 'default', $this->returnid, '',
            $this->cmsmodule->ParamsForLink($this->parameters, array(

                    'pager_limit' => $this->limit,
                    'pager_size' => $this->size,
                    'pager_page' => 1))
            , '', true, true) : false;

        $pager_array['previous_page'] = ($this->page > 1) ? $this->cmsmodule->createLink($this->id, 'default', $this->returnid, '',
            $this->cmsmodule->ParamsForLink($this->parameters, array(

                    'pager_limit' => $this->limit,
                    'pager_size' => $this->size,
                    'pager_page' => $this->page - 1))
            , '', true, true) : false;

        $pager_array['next_page'] = ($this->page < $this->countPages()) ? $this->cmsmodule->createLink($this->id,'default', $this->returnid, '',
            $this->cmsmodule->ParamsForLink($this->parameters, array(

                    'pager_limit' => $this->limit,
                    'pager_size' => $this->size,
                    'pager_page' => $this->page + 1))
            , '', true, true) : false;

        $pager_array['last_page'] = ($this->page < $this->countPages()) ? $this->cmsmodule->createLink($this->id,'default', $this->returnid, '',
            $this->cmsmodule->ParamsForLink($this->parameters, array(
                    'pager_limit' => $this->limit,
                    'pager_size' => $this->size,
                    'pager_page' => $this->countPages()))
            , '', true, true) : false;

        return $pager_array;
    }

    public function getOffset()
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getLimit()
    {
        return $this->limit;
    }
}