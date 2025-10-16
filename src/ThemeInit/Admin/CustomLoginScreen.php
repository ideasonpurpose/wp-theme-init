<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

class CustomLoginScreen
{
    public $siteLogo;
    public $byline;
    public $footer;

    public function __construct($siteLogo = null, $byline = null, $footer = null)
    {
        $this->siteLogo = $siteLogo;
        if ($byline === null) {
            $href = '<a href="https://www.ideasonpurpose.com" target="_blank">Ideas On Purpose</a>';
            $this->byline = sprintf(__('A WordPress site by %s', 'iopwp'), $href);
        } else {
            $this->byline = $byline;
        }
        $this->footer = $footer;

        add_action('login_enqueue_scripts', [$this, 'load_styles']);
        add_filter('login_message', [$this, 'login_message']);
        add_action('login_footer', [$this, 'footer']);
    }

    public function load_styles()
    {
        $css_path = __DIR__ . '/CustomLoginScreen/CustomLoginScreen.css';
        $theme_path = get_template_directory();
        $css_url = get_template_directory_uri() . str_replace($theme_path, '', $css_path);
        wp_enqueue_style('iop-custom-login-screen-styles', $css_url, [], false, 'all');
    }

    public function login_message($message)
    {
        $siteLogo = '';
        if ($this->siteLogo) {
            if (is_callable($this->siteLogo)) {
                $siteLogo = call_user_func($this->siteLogo);
            } else {
                $siteLogo = $this->siteLogo;
            }
        }
        if (!empty($siteLogo)) {
            $siteLogo = sprintf('<div id="iop-client-logo">%s</div>', $siteLogo);
        }

        return $message .
            "<div id='iop-login-message'>
                {$siteLogo}
                <div id='iop-login-byline'>{$this->byline}</div>
            </div>";
    }

    public function footer()
    {
        $url = 'https://ideasonpurpose.com';
        $title = 'Ideas on Purpose';
        $logo = file_get_contents(__DIR__ . '/CustomLoginScreen/iop-logo.svg');

        if ($this->footer) {
            if (is_callable($this->footer)) {
                $footer = call_user_func($this->footer);
            } else {
                $footer = $this->footer;
            }
        }

        $footer ??= sprintf(
            '<a href="%s" target="_blank" title="%s">%s</a>',
            esc_url($url),
            esc_attr($title),
            $logo,
        );
        echo "<div id='iop-login-footer'>{$footer}</div>";
    }
}
