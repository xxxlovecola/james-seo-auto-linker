<?php

namespace JamesSeoAutoLinker;

/**
 * Handles plugin settings and options.
 */
class Settings
{
    private string $option_name = 'JamesSEOAutoLinks';
    private array $options = [];
    private array $defaults = [
        'post' => 'on',
        'page' => 'on',
        'comment' => '',
        'excludeheading' => 'on',
        'lposts' => 'on',
        'lpages' => 'on',
        'lcats' => '',
        'ltags' => '',
        'ignore' => 'about',
        'ignorepost' => 'contact',
        'maxlinks' => 3,
        'maxsingle' => 1,
        'minusage' => 1,
        'customkey' => '',
        'customkey_preventduplicatelink' => false,
        'customkey_url' => '',
        'customkey_url_value' => '',
        'customkey_url_datetime' => '',
        'nofolo' => '',
        'onlysingle' => 'on',
        'casesens' => '',
        'allowfeed' => '',
        'maxsingleurl' => 1
    ];

    public function __construct()
    {
        $this->options = $this->load_options();
    }

    /**
     * Get all options or a specific key.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->options;
        }

        return $this->options[$key] ?? $this->defaults[$key] ?? $default;
    }

    /**
     * Update options.
     * 
     * @param array $new_options
     */
    public function update(array $new_options): void
    {
        if (empty($new_options)) {
            return;
        }

        $this->options = array_merge($this->options, $new_options);
        update_option($this->option_name, $this->options);
    }

    /**
     * Load options from database with defaults.
     */
    private function load_options(): array
    {
        $saved = get_option($this->option_name);

        if (!empty($saved) && is_array($saved)) {
            return array_merge($this->defaults, $saved);
        }

        return $this->defaults;
    }

    public function get_option_name(): string
    {
        return $this->option_name;
    }
}
