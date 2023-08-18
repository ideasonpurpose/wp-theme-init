<?php
namespace IdeasOnPurpose\ThemeInit\Admin\TemplateAudit;

// @codeCoverageIgnoreStart
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
// @codeCoverageIgnoreEnd

/**
 * Customized subClass of WP_List_Table
 */
class ListTable extends \WP_List_Table
{
    public $_column_headers;
    public $items;

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $user_id = get_current_user_id();
        $screen = get_current_screen();
        $screen_option = $screen->get_option('per_page', 'option');

        $per_page = get_user_meta($user_id, $screen_option, true);

        if (empty($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }

        $data = $this->data();
        usort($data, [$this, 'sortData']);

        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $per_page,
        ]);

        $data = array_slice($data, ($currentPage - 1) * $per_page, $per_page);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = [
            'id' => 'ID',
            'name' => 'Name',
            'file' => 'File',
            'count' => 'In Use',
        ];

        return $columns;
    }

    public function get_hidden_columns()
    {
        return ['id'];
    }

    public function get_sortable_columns()
    {
        return ['name' => ['name', true], 'count' => ['count', false]];
    }

    public function data()
    {
        $theme = wp_get_theme();
        $named_templates = $theme->get_page_templates();

        /**
         * get all pages where the meta_key is set to _wp_page_template
         */
        $all_pages = get_pages(['meta_key' => '_wp_page_template']);
        $templateCounts = [];
        foreach ($all_pages as $page) {
            $template = $page->meta_value;
            if (!array_key_exists($template, $templateCounts)) {
                $templateCounts[$template] = 0;
            }
            $templateCounts[$template]++;
        }

        $i = 0;
        $data = [];
        foreach ($named_templates as $file => $name) {
            $count = $templateCounts[$file] ?? 0;
            $data[] = ['id' => $i++, 'name' => $name, 'file' => $file, 'count' => $count];
        }

        return $data;
    }

    /**
     * Handle sorting of rows on either name or count
     * @return int
     */
    public function sortData($a, $b)
    {
        $orderby = $_GET['orderby'] ?? 'name';
        $order = $_GET['order'] ?? 'asc';
        $result = 0;

        switch ($orderby) {
            case 'name':
                $result = strcmp($a['name'], $b['name']);
                break;
            case 'count':
                $result = $a['count'] <=> $b['count'];
                break;
            default:
                break;
        }

        return $order === 'asc' ? $result : -$result;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                return sprintf('<strong>%s</strong>', $item[$column_name]);
                break;
            case 'count':
                return $item[$column_name] != 0 ? $item[$column_name] : '--';
                break;
            case 'id':
            case 'file':
            default:
                return $item[$column_name];
                break;
        }
    }
}
