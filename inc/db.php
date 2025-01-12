<?php
namespace WPWAND_PRO;

class WPWAND_DB
{
    function __construct()
    {
        add_action('admin_init', [$this, 'create_table']);
    }

    function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $history_table = $wpdb->prefix . 'wpwand_history';
        $pg_table = $wpdb->prefix . 'wpwand_generated_post';
        $templates_table = $wpdb->prefix . 'wpwand_custom_prompts';
        // $character_table = $wpdb->prefix . 'wpwand_ai_character';
        $schema_wpwand = [];



        if (!$this->table_exist($history_table)) {

            $schema_wpwand[] = "CREATE TABLE $history_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(255) NOT NULL,
            prompt_info LONGTEXT NOT NULL,
            response LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";

        }

        // post generateor table 
        if (!$this->table_exist($pg_table)) {

            $schema_wpwand[] = "CREATE TABLE $pg_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT DEFAULT NULL,
            post_id BIGINT UNSIGNED DEFAULT NULL,
            action_id BIGINT UNSIGNED DEFAULT NULL,
            status VARCHAR(255) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";

        }
        $pg_columns = $wpdb->get_col("DESC $pg_table");
        if (!in_array('action_id', $pg_columns)) {
            $sql = "ALTER TABLE $pg_table ADD COLUMN action_id BIGINT UNSIGNED DEFAULT NULL";

            // Execute the SQL statement
            $wpdb->query($sql);
        }

        if (!$this->table_exist($templates_table)) {

            $schema_wpwand[] = "CREATE TABLE $templates_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            prompt LONGTEXT DEFAULT NULL,
            type VARCHAR(255) DEFAULT 'template',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";

        }



        if (!empty($schema_wpwand)) {

            $this->insert_table($schema_wpwand);
        }


    }

    function insert_table($schema)
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta($schema);

    }

    function table_exist($table_name)
    {
        global $wpdb;

        // SQL query to check if the table exists
        $sql = "SHOW TABLES LIKE '{$table_name}'";

        // Execute the query
        $table_exists = $wpdb->get_var($sql);

        if ($table_exists === $table_name) {
            return true;
        }
        return false;
    }
}

// register_activation_hook(WPWAND_PRO_FILE_, [WPWAND_DB::class, 'create_table']);

$config = new WPWAND_DB();