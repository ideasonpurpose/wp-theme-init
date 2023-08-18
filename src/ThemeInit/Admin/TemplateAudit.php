<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

use IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable;

/**
 * A class to collect post-state labels. Initially, the only label here is
 * the 404 page template.
 *
 * TODO: Add a dropdown menu to the Pages admin list which would let us select
 * a subset of pages based on which template they're using. Counts in the Audit
 * Table should link to a filtered Pages view showing Pages using that Template
 */
class TemplateAudit
{
    public $option_per_page;
    public $ListTable;
    public $submenu_id;
    public $theme_name;

    public function __construct($args = [])
    {
        $this->option_per_page = 'template_audit_templates_per_page';

        add_filter('manage_edit-page_columns', [$this, 'addColumns']);
        add_action('manage_page_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_action('admin_menu', [$this, 'addTemplateAdminMenuInit']);

        /**
         * This filter is needed to actually store our option value in metadata
         */
        add_filter('set-screen-option', [$this, 'setOption'], 10, 3);
    }

    /**
     * Store our option value in metadata
     */
    public function setOption($status, $option, $value)
    {
        if ($option === $this->option_per_page) {
            return $value;
        }
    }

    /**
     * Add template column to WP Admin for Page listings
     * TODO: Should be restricted to Administrators?
     */
    public function addColumns($cols)
    {
        $newCols = [];

        foreach ($cols as $key => $value) {
            if ($key === 'date') {
                $newCols['template'] = 'Template';
            }
            $newCols[$key] = $value;
        }
        return $newCols;
    }

    /**
     * Render WP Admin columns with template data
     */
    public function renderColumns($col, $id)
    {
        switch ($col) {
            case 'template':
                $meta = get_post_meta($id, '_wp_page_template', true);
                $templates = wp_get_theme()->get_page_templates();

                if (!in_array($meta, ['', 'default'])) {
                    printf('<strong>%s</strong><br>%s', $templates[$meta], $meta);
                } else {
                    echo '<span aria-hidden="true">—</span>';
                }
                break;
        }
    }

    /**
     *
     * Add a submenu to Appearance and define several admin-only properties
     *
     * Also defines $this->submenu_id and $this->ListTable for use in later admin-only methods
     */
    public function addTemplateAdminMenuInit()
    {
        $this->ListTable = new ListTable();

        $this->submenu_id = add_submenu_page(
            'themes.php',
            'Template Audit',
            'Template Audit',
            'manage_options',
            'iop-template-audit',
            [$this, 'templateAdminPage']
        );

        add_action('load-appearance_page_iop-template-audit', [
            $this,
            'templateAdminPageScreenOptions',
        ]);
    }

    /**
     * Render Admin page content
     */
    public function templateAdminPage()
    {
        $theme_name = wp_get_theme()->get('Name');

        $this->ListTable->prepare_items();
        echo '<div class="wrap">';
        echo "<h1 class=\"wp-heading-inline\">Template Audit: {$theme_name}</h1>";
        $this->ListTable->display();
        echo '</div>';
    }

    /**
     * Enable the Screen Options menu on Admin screens
     */
    public function templateAdminPageScreenOptions()
    {
        $screen = get_current_screen();

        /**
         * Note: $this->submenu_id is set in $this->addTemplateAdminMenuInit
         *       which seems kind of fragile.
         */
        if (!is_object($screen) || $screen->id != $this->submenu_id) {
            return;
        }

        add_screen_option('per_page', [
            'label' => 'Templates per page',
            'default' => 20,
            'option' => $this->option_per_page,
        ]);
    }
}
