<?php
namespace ideasonpurpose\ThemeInit\Extras;

class Shortcodes
{
    public function __construct()
    {
        $this->addShortcodes();
        // add_action('init', [$this, 'addShortcodes']);
    }

    /**
     * Simple key/value lookup table for adding multiple shortcodes
     */
    public $codes = ['email' => 'protectEmail'];

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
    public function protectEmail(array $atts, ?string $content = "")
    {
        if (empty($atts) || !filter_var($atts[0], FILTER_VALIDATE_EMAIL)) {
            return $content;
        }
        $munged_mail = antispambot($atts[0]);
        $content = ($content) ?: $munged_mail;
        $classes = (isset($atts['class'])) ? "class=\"$atts[class]\"" : '';
        return "<a $classes href='mailto:$munged_mail'>$content</a>";
    }
}
