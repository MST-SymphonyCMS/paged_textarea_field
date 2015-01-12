<?php

Class extension_Paged_textarea_field extends Extension
{
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/backend/',
                'delegate' => 'InitaliseAdminPageHead',
                'callback' => 'appendJavaScript'
            )/*,
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePreGenerate',
                'callback' => 'adminPagePreGenerate'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'DataSourcePreExecute',
                'callback' => 'dataSourcePreExecute'
            )*/
        );
    }
    

    /* ********* INSTALL/UPDATE/UNISTALL ******* */

    /**
     * Install extension
     */
    public function install()
    {
        return Symphony::Database()->query("
            CREATE TABLE IF NOT EXISTS `tbl_fields_paged_textarea` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `field_id` int(11) unsigned NOT NULL,
                `formatter` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                `size` int(3) unsigned NOT NULL,
                `output_page` varchar(255),
                `default_page_one` varchar(3) DEFAULT 'no',
                PRIMARY KEY (`id`),
                KEY `field_id` (`field_id`)
            )  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

           /* public function update($previousVersion) {

            if( version_compare($previousVersion, '1.1', '<') ){
                $fields = Symphony::Database()->fetch("SELECT `field_id` FROM `tbl_fields_slug_field`");
                foreach( $fields as $field ){
                    $entries_table = 'tbl_entries_data_'.$field["field_id"];
                    Symphony::Database()->query("ALTER TABLE `{$entries_table}` MODIFY `value` VARCHAR(255) default NULL");
                }
            }

            return true;
        }*/
        
    /**
     * Uninstall
     */
    public function uninstall()
    {
        return Symphony::Database()->query("DROP TABLE IF EXISTS `tbl_fields_paged_textarea`");
    }

    public function appendJavaScript($context)
    {
        $page_callback = Administration::instance()->getPageCallback();
        //print_r($pageCallback);die;
        if ($page_callback['driver'] == 'publish') {
            Administration::instance()->Page->addStylesheetToHead(
                URL . '/extensions/paged_textarea_field/assets/jquery-ui.css'
            );
            Administration::instance()->Page->addStylesheetToHead(
                URL . '/extensions/paged_textarea_field/assets/paged_textarea_field.css'
            );
            Administration::instance()->Page->addScriptToHead(
                URL . '/extensions/paged_textarea_field/assets/jquery-ui.min.js'
            );
            Administration::instance()->Page->addScriptToHead(
                URL . '/extensions/paged_textarea_field/assets/paged_textarea_field.js'
            );
        }
    }

    /**
     * Modify admin pages.
     */
    public function adminPagePreGenerate(&$context)
    {
        $page = $context['oPage'];
        $callback = Symphony::Engine()->getPageCallback();
        $driver = $callback['driver'];

    }
    
    /*public function dataSourcePreExecute(&$context)
    {
        $datasource = $context['datasource'];
        print_r($context);
        exit;
    }*/
}
?>