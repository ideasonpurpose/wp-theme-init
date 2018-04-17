<?php

namespace ideasonpurpose\Extras;

class Shortcodes
{
    public function __construct()
    {
        $this->addShortcodes();
        // if (!shortcode_exists('email')) {
        //     add_shortcode('email', [$this, 'protectEmail']);
        // }
    }

    /**
     * Simple key/value lookup table for adding multiple shortcodes
     */
    public $codes = [
        'email' => 'protectEmail'
    ] ;

    /**
     * Loops through $this->codes and conditionally adds shortcodes
     */
    public function addShortcodes()
    {
        foreach ($this->codes as $code => $func) {
            if (!shortcode_exists($code)) {
                add_shortcode($code, [$this, $func]);
            }
        }
    }

    /**
     * Protect email address shortcode
     *
     *  Example 1: [email name@example.com]
     *  returns <a href="mailto:&#106;&#111;h&#110;&#99;&#111;&#109;"></a>
     *
     *  Example 2: [email name@example.com]email us![/email]
     *  returns <a href="mailto:&#106;&#111;h&#110;&#99;&#111;&#109;">email us!</a>
     */
    public function protectEmail($atts, $content = null)
    {
        if (!is_email($atts[0])) {
            return $content;
        }
        $munged_mail = antispambot($atts[0]);
        $content = ($content) ?: $munged_mail;
        return "<a href='mailto:$munged_mail'>$content</a>";
    }
}
