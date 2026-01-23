<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Loader {
    protected $actions = [];
    protected $filters = [];
    protected $shortcodes = [];

    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes[] = [
            'tag' => $tag,
            'component' => $component,
            'callback' => $callback
        ];
    }

    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
        return $hooks;
    }

    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'], 
                [$hook['component'], $hook['callback']], 
                $hook['priority'], 
                $hook['accepted_args']
            );
        }
        
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'], 
                [$hook['component'], $hook['callback']], 
                $hook['priority'], 
                $hook['accepted_args']
            );
        }
        
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'], 
                [$shortcode['component'], $shortcode['callback']]
            );
        }
    }

    /**
     * Get counts for debugging
     */
    public function get_hook_counts() {
        return [
            'actions' => count($this->actions),
            'filters' => count($this->filters),
            'shortcodes' => count($this->shortcodes)
        ];
    }

    /**
     * Get all registered hooks for debugging
     */
    public function get_registered_hooks() {
        return [
            'actions' => $this->actions,
            'filters' => $this->filters,
            'shortcodes' => $this->shortcodes
        ];
    }
}