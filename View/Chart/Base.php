<?php

namespace Lightning\View\Chart;

use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;

abstract class Base extends Page {
    public $id = 'chart';

    protected $renderer = '';
    protected $numberFormat = 'float';
    protected $width = 600;
    protected $height = 450;
    protected $page = 'chart';
    protected $ajax = false;

    public function __construct() {
        parent::__construct();
        JS::add('/js/Chart.min.js', false);
        JS::startup('lightning.stats.init()');

        // Prepare the JS.
        JS::set('chart.' . $this->id . '.renderer', $this->renderer);
        JS::set('chart.' . $this->id . '.url', '/' . Request::getLocation());
        JS::set('chart.' . $this->id . '.params.start', ['source' => 'start']);
        JS::set('chart.' . $this->id . '.params.number_format', $this->numberFormat);
        JS::set('chart.' . $this->id . '.params.diff', !empty($this->diff));
        if (!empty($this->data)) {
            JS::set('chart.' . $this->id . '.data', $this->data);
        }
        JS::set('chart.' . $this->id . '.ajax', $this->ajax);
    }

    public function get() {
        Template::getInstance()->set('chart', $this);
    }

    public function renderCanvas() {
        return '<canvas id="' . $this->id . '" height="' . $this->height . '" width="' . $this->width . '"></canvas>';
    }
}
