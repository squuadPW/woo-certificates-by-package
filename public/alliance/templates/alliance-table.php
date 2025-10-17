<?php
    if (!defined('ABSPATH')) {
        exit;
    }

    if (!function_exists('edusystem_alliances_get_sort_link')) {
        function edusystem_alliances_get_sort_link($endpoint_url, $column, $current_orderby, $current_order) {
            if ($current_orderby === $column) {
                $new_order = $current_order === 'ASC' ? 'DESC' : 'ASC';
                $icon_class = $current_order === 'ASC' ? 'edusystem-sort-up' : 'edusystem-sort-down';
                $icon_html = '<i class="sort-icon '.$icon_class.'"></i>';
                $class = 'sorted ' . strtolower($current_order);
            } else {
                $new_order = 'ASC';
                $icon_html = '<i class="sort-icon edusystem-sort-both"></i>';
                $class = 'sortable';
            }

            $query_args = array_diff_key($_GET, array_flip(['orderby', 'order']));
            $query_args['orderby'] = $column;
            $query_args['order'] = $new_order;

            if (isset($query_args['pageds'])) {
                unset($query_args['pageds']);
            }

            $url = esc_url(add_query_arg($query_args, $endpoint_url));

            return array('url' => $url, 'icon' => $icon_html, 'class' => $class);
        }
    }

    $name_sort = edusystem_alliances_get_sort_link($endpoint_url, 'name', $current_orderby, $current_order);
    $status_sort = edusystem_alliances_get_sort_link($endpoint_url, 'status', $current_orderby, $current_order);
    $created_at_sort = edusystem_alliances_get_sort_link($endpoint_url, 'created_at', $current_orderby, $current_order);
?>
<div class="edusystem-alliances-header">
    <h2><?php echo __('Listado de Alianzas Registradas', 'edusystem'); ?></h2>
</div>
<?php if (!empty($alliances) && empty($search_term)) { ?>
    <div class="edusystem-alliance-toolbar">
        <?php if ($table_exist && !empty($alliances)) { ?>
            <p class="woocommerce-result-count">
                <?php
                    $start_range = (($current_page - 1) * $posts_per_page) + 1;
                    $end_range = min($start_range + $posts_per_page - 1, $total_alliances);
                    printf(
                        esc_html__('Mostrando %1$d - %2$d de %3$d alianzas registradas.', 'edusystem'),
                        $start_range,
                        $end_range,
                        $total_alliances
                    );
                ?>
            </p>
        <?php } ?>
        <form method="get" class="edusystem-alliance-search-form">
            <input
                type="search"
                name="alliance_search"
                placeholder="<?php esc_attr_e('Nombre o Correo electrónico', 'edusystem'); ?>"
                value="<?php echo esc_attr($search_term); ?>"
            />
            <button type="submit" class="button button-primary btn-primary">
                <?php esc_html_e('Buscar', 'edusystem'); ?>
            </button>
            <?php
                if ( $current_page > 1 ) {
                    echo '<input type="hidden" name="pageds" value="1" />';
                }
            ?>
        </form>
    </div>
<?php } ?>

<?php if (!$table_exist) : ?>
    <p class="woocommerce-info">
        <?php echo __('El sistema de alianzas no está completamente configurado o no hay datos disponibles en este momento. Si el problema persiste, contacte a soporte.', 'edusystem'); ?>
    </p>
<?php elseif (empty($alliances)) : ?>
    <?php
        if (!empty($search_term)) {
            $clear_url = remove_query_arg(['alliance_search', 'paged']);
            echo '<p class="woocommerce-info">';
                printf(
                    esc_html__('No se encontraron alianzas que coincidan con su búsqueda: "%s".', 'edusystem'),
                    esc_html($search_term)
                );
            echo '</p>';
            echo '<p><a href="' . esc_url($clear_url) . '" class="button woocommerce-button">' . esc_html__('Mostrar todas las alianzas', 'edusystem') . '</a></p>';
            return;
        } else { ?>
            <p class="woocommerce-error"><?php echo __('No hay alianzas disponibles.', 'edusystem'); ?></p>
    <?php } ?>
<?php else : ?>
    <table class="woocommerce-MyAccount-alliances-table shop_table_responsive my_account_orders">
        <thead>
            <tr>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--row">
                    <span class="nobr">#</span>
                </th>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--name <?php echo esc_attr($name_sort['class']); ?>">
                    <a href="<?php echo $name_sort['url']; ?>">
                        <span class="nobr"><?php echo esc_html__('Name', 'edusystem'); ?></span>
                        <?php echo $name_sort['icon']; ?>
                    </a>
                </th>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--email">
                    <span class="nobr"><?php echo esc_html__('Email', 'edusystem'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--level">
                    <span class="nobr"><?php echo esc_html__('Level', 'edusystem'); ?></span>
                </th>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--status <?php echo esc_attr($status_sort['class']); ?>">
                    <a href="<?php echo $status_sort['url']; ?>">
                        <span class="nobr"><?php echo esc_html__('Status', 'edusystem'); ?></span>
                        <?php echo $status_sort['icon']; ?>
                    </a>
                </th>
                <th class="woocommerce-MyAccount-alliances-table__header woocommerce-MyAccount-alliances-table__header--created_at <?php echo esc_attr($created_at_sort['class']); ?>">
                    <a href="<?php echo $created_at_sort['url']; ?>">
                        <span class="nobr"><?php echo esc_html__('Created at', 'edusystem'); ?></span>
                        <?php echo $created_at_sort['icon']; ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alliances as $key => $alliance) : ?>
                <tr class="woocommerce-MyAccount-alliances-table__row">
                    <td class="woocommerce-MyAccount-alliances-table__cell woocommerce-MyAccount-alliances-table__cell--row" data-title="#">
                        <?php
                            $row_number = ($current_page - 1) * $posts_per_page + $key + 1;
                            echo esc_html($row_number);
                        ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Name', 'edusystem'); ?>">
                        <?php echo esc_html(strtoupper($alliance->name)); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Email', 'edusystem'); ?>">
                        <?php echo esc_html($alliance->email); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Level', 'edusystem'); ?>">
                        <?php
                            $level_id = isset($alliance->level_id) ? $alliance->level_id : '0';
                            echo esc_html($module->get_name_level($level_id));
                        ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Status', 'edusystem'); ?>">
                        <?php
                            $status = isset($alliance->status) ? $alliance->status : '0';
                            echo esc_html($module->get_name_status_alliance($status));
                        ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Created at', 'edusystem'); ?>">
                        <?php
                            $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $alliance->created_at);
                            $timestamp = $datetime->getTimestamp();
                            echo esc_html(ucfirst(wp_date('F j, Y', $timestamp)));
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    // Generates pagination links
    if ($total_pages > 1) {
        $paginate_args = array(
            'base' => $endpoint_url.'%_%',
            'format' => '?pageds=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'type'=> 'list',
        );
        // If there are already parameters in the URL, use 'add_query_arg'
        $existing_args = array();
        if (isset($_GET['action'])) {
            $existing_args['action'] = $_GET['action'];
        }
        if (isset($_GET['orderby'])) {
            $existing_args['orderby'] = $_GET['orderby'];
        }
        if (isset($_GET['order'])) {
            $existing_args['order'] = $_GET['order'];
        }
        if (!empty($existing_args)) {
            $paginate_args['add_args'] = $existing_args;
        }
    ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-border woocommerce-alliances-pagination">
            <?php echo paginate_links($paginate_args); ?>
        </div>
    <?php } ?>
<?php endif; ?>