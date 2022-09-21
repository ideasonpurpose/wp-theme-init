<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

use IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable;

// @codeCoverageIgnoreStart
// if (!class_exists('WP_List_Table')) {
//     require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
// }
// @codeCoverageIgnoreEnd

// use \WP_List_Table;
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
    public function __construct($args = [])
    {
        $this->option_per_page = 'template_audit_templates_per_page';

        add_filter('manage_edit-page_columns', [$this, 'addColumns']);
        add_action('manage_page_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_action('admin_menu', [$this, 'addTemplateAdminMenu']);

        $theme = wp_get_theme();
        $this->named_templates = $theme->get_page_templates();

        /**
         * This filter is needed to actually store our option value in metadata
         */
        add_filter(
            'set-screen-option',
            function ($status, $option, $value) {
                if ($option === $this->option_per_page) {
                    return $value;
                }
            },
            10,
            3
        );
    }

    public function getTemplates()
    {
        $this->named_templates = wp_get_theme()->get_page_templates();
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

                if (!in_array($meta, ['', 'default'])) {
                    // printf('<span title="%s">%s</span', $meta, $this->named_templates[$meta]);
                    printf('<strong>%s</strong><br>%s', $this->named_templates[$meta], $meta);
                } else {
                    echo '<span aria-hidden="true">—</span>';
                }
                break;
        }
    }

    /**
     *
     * Add a submenu to Appearance
     */
    public function addTemplateAdminMenu()
    {
        $theme = wp_get_theme();
        $this->named_templates = $theme->get_page_templates();
        $this->theme_name = $theme->get('Name');

        $this->id = add_submenu_page(
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
        $table = new ListTable();
        $table->prepare_items();

        echo '<div class="wrap">';
        echo "<h1 class=\"wp-heading-inline\">Template Audit: {$this->theme_name}</h1>";
        $table->display();

        echo '</div>';
    }

    /**
     * Enable the Screen Options menu on Admin screens
     */
    public function templateAdminPageScreenOptions()
    {
        $screen = get_current_screen();

        if (!is_object($screen) || $screen->id != $this->id) {
            return;
        }

        add_screen_option('per_page', [
            'label' => 'Templates per page',
            'default' => 20,
            'option' => $this->option_per_page,
        ]);
    }
}



// /**
//  * Customized subClass of WP_List_Table
//  */
// class ListTable extends \WP_List_Table
// {
//     public function prepare_items()
//     {
//         $columns = $this->get_columns();
//         $hidden = $this->get_hidden_columns();
//         $sortable = $this->get_sortable_columns();

//         $user_id = get_current_user_id();
//         $screen = get_current_screen();
//         $screen_option = $screen->get_option('per_page', 'option');

//         $per_page = get_user_meta($user_id, $screen_option, true);

//         if (empty($per_page) || $per_page < 1) {
//             $per_page = $screen->get_option('per_page', 'default');
//         }

//         $data = $this->data();
//         usort($data, [$this, 'sortData']);

//         $currentPage = $this->get_pagenum();
//         $totalItems = count($data);
//         $this->set_pagination_args([
//             'total_items' => $totalItems,
//             'per_page' => $per_page,
//         ]);

//         $data = array_slice($data, ($currentPage - 1) * $per_page, $per_page);

//         $this->_column_headers = [$columns, $hidden, $sortable];
//         $this->items = $data;
//     }

//     public function get_columns()
//     {
//         $columns = [
//             'id' => 'ID',
//             'name' => 'Name',
//             'file' => 'File',
//             'count' => 'In Use',
//         ];

//         return $columns;
//     }

//     public function get_hidden_columns()
//     {
//         return ['id'];
//     }

//     public function get_sortable_columns()
//     {
//         return ['name' => ['name', true], 'count' => ['count', false]];
//     }

//     public function data()
//     {
//         $theme = wp_get_theme();
//         $named_templates = $theme->get_page_templates();

//         /**
//          * get all pages where the meta_key is set to _wp_page_template
//          */
//         $all_pages = get_pages(['meta_key' => '_wp_page_template']);
//         $templateCounts = [];
//         foreach ($all_pages as $page) {
//             $template = $page->meta_value;
//             if (!array_key_exists($template, $templateCounts)) {
//                 $templateCounts[$template] = 0;
//             }
//             $templateCounts[$template]++;
//         }

//         $i = 0;
//         $data = [];
//         foreach ($named_templates as $file => $name) {
//             $count = $templateCounts[$file] ?? 0;
//             $data[] = ['id' => $i++, 'name' => $name, 'file' => $file, 'count' => $count];
//         }

//         return $data;
//     }

//     /**
//      * Handle sorting of rows on either name or count
//      * @return int
//      */
//     public function sortData($a, $b)
//     {
//         $orderby = $_GET['orderby'] ?? 'name';
//         $order = $_GET['order'] ?? 'asc';
//         $result = 0;

//         switch ($orderby) {
//             case 'name':
//                 $result = strcmp($a['name'], $b['name']);
//                 break;
//             case 'count':
//                 $result = $a['count'] <=> $b['count'];
//                 break;
//             default:
//                 break;
//         }

//         return $order === 'asc' ? $result : -$result;
//     }

//     public function column_default($item, $column_name)
//     {
//         switch ($column_name) {
//             case 'name':
//                 return sprintf('<strong>%s</strong>', $item[$column_name]);
//                 break;
//             case 'count':
//                 return $item[$column_name] != 0 ? $item[$column_name] : '--';
//                 break;
//             case 'id':
//             case 'file':
//             default:
//                 return $item[$column_name];
//                 break;
//         }
//     }
// }
