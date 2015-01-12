<?php

/**
 * @package toolkit
 */

require_once FACE . '/interface.exportablefield.php';
require_once FACE . '/interface.importablefield.php';
require_once EXTENSIONS . '/paged_textarea_field/lib/Spyc.php';

/**
 * < Description >
 */
class fieldPaged_Textarea extends Field implements ExportableField, ImportableField
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Paged Textarea');
        $this->_required = true;

        // Set default
        $this->set('show_column', 'no');
        $this->set('required', 'no');
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function canFilter()
    {
        return false;
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `value` MEDIUMTEXT,
              `value_formatted` MEDIUMTEXT,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              FULLTEXT KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

/*-------------------------------------------------------------------------
    Utilities:
-------------------------------------------------------------------------*/

    protected function __applyFormatting($data, $validate = false, &$errors = null)
    {
        $result = '';

        if ($this->get('formatter')) {
            $formatter = TextformatterManager::create($this->get('formatter'));
            $result = $formatter->run($data);
        }

        if ($validate === true) {
            include_once(TOOLKIT . '/class.xsltprocess.php');

            if (!General::validateXML($result, $errors, false, new XsltProcess)) {
                $result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
                $result = $this->__replaceAmpersands($result);

                if (!General::validateXML($result, $errors, false, new XsltProcess)) {
                    return false;
                }
            }
        }

        return $result;
    }

    private function __replaceAmpersands($value)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['size'])) {
            $settings['size'] = 15;
        }
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        $div = new XMLElement('div', null, array('class' => 'two columns'));

        $label = Widget::Label(__('Number of default rows'));
        $label->setAttribute('class', 'column');
        $input = Widget::Input('fields['.$this->get('sortorder').'][size]', (string)$this->get('size'));
        $label->appendChild($input);
        $div->appendChild($label);
        $div->appendChild($this->buildFormatterSelect($this->get('formatter'), 'fields['.$this->get('sortorder').'][formatter]', __('Text Formatter')));
        $wrapper->appendChild($div);

        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $label = Widget::Label(
            __('Page to output'),
            Widget::Input(
                'fields['.$this->get('sortorder').'][output_page]',
                (string)$this->get('output_page')
            ),
            'column'
        );
        $div->appendChild($label);

        $input = Widget::Input('fields['.$this->get('sortorder').'][default_page_one]', 'yes', 'checkbox');
        if ($this->get('default_page_one') == 'yes') {
            $input->setAttribute('checked', 'checked');
        }
        $div->appendChild(
            Widget::Label(
                __('If invalid page, default to page 1'),
                new XMLElement('div', $input),
                'column'
            )
        );
        $wrapper->appendChild($div);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function commit()
    {
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = array();

        if ($this->get('formatter') != 'none') {
            $fields['formatter'] = $this->get('formatter');
        }

        $fields['size'] = $this->get('size');
        $fields['output_page'] = $this->get('output_page');
        $fields['default_page_one'] = $this->get('default_page_one') ? 'yes' : 'no';

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

    public function displayPublishPanel(
        XMLElement &$wrapper,
        $data = null,
        $flagWithError = null,
        //$fieldnamePrefix = null,
        //$fieldnamePostfix = null,
        $entry_id = null
    )
    {
        $label = new XMLElement('p', $this->get('label'), array('class' => 'label'));
        if ($this->get('required') != 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }
        $wrapper->appendChild($label);

        $border_box = new XMLElement('div', null, array('class' => 'border-box'));

        $ctrl_panel = new XMLElement('div', null, array('class' => 'control-panel'));
        $button_set = new XMLElement('div', null, array('class' => 'button-set'));
        $button_set->appendChild(
            new XMLElement('button', 'Add', array('type' => 'button', 'class' =>'action', 'data-action' => 'add', 'title' => 'Add page'))
        );
        $button_set->appendChild(
            new XMLElement('button', 'Add <', array('type' => 'button', 'class' => 'action add-before', 'data-action' => 'add-before', 'title' => 'Add page before current page'))
        );
        $button_set->appendChild(
            new XMLElement('button', 'Add >', array('type' => 'button', 'class' => 'action add-after', 'data-action' => 'add-after', 'title' => 'Add page after current page'))
        );
        $button_set->appendChild(new XMLElement('button', 'Remove', array('type' => 'button', 'class' => 'action remove', 'data-action' => 'remove', 'title' => 'Remove current page')));
        $ctrl_panel->appendChild($button_set);

        $ctrl_panel->appendChild(new XMLElement('div', null, array('class' => 'button-set pages')));
        $border_box->appendChild($ctrl_panel);

        // Pages
        $values = array();
        $value_first = '';
        if (isset($data['value'])) {
            $values = Spyc::YAMLLoadString($data['value']);
            if (is_array($values) and !empty($values)) {
                $value_first = array_shift($values);
            }
        }
        $textarea = Widget::Textarea(
            'fields[' . $this->get('element_name') . '][]',
            (int)$this->get('size'),
            50,
            (strlen($value_first) != 0 ? General::sanitize($value_first) : null),
            array('class' => 'current')
        );
        if ($this->get('formatter') != 'none') {
            $textarea->setAttribute('class', 'current ' . $this->get('formatter'));
        }
        $border_box->appendChild($textarea);

        foreach ($values as $value) {
            $textarea = Widget::Textarea(
                'fields[' . $this->get('element_name') . '][]',
                (int)$this->get('size'),
                50,
                (strlen($value) != 0 ? General::sanitize($value) : null)
            );
            if ($this->get('formatter') != 'none') {
                $textarea->setAttribute('class', $this->get('formatter'));
            }
            $border_box->appendChild($textarea);
        }

        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($div, $flagWithError));
        } else {
            $wrapper->appendChild($border_box);
        }
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;
        if ($this->get('required') == 'yes' && strlen(trim($data)) == 0) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));
            return self::__MISSING_FIELDS__;
        }

        /*if ($this->__applyFormatting($data, true, $errors) === false) {
            $message = __('‘%s’ contains invalid XML.', array($this->get('label'))) . ' ' . __('The following error was returned:') . ' <code>' . $errors[0]['message'] . '</code>';
            return self::__INVALID_FIELDS__;
        }
*/
        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;

        if (!is_array($data)) {
            $data = array($data);
        }
/*
        $return_value = '';
        $return_value_formatted = '';
        foreach ($data as $value) {
            $return_value .= '<page>' . htmlentities($value) . '</page>';
            $formatted_value = $this->__applyFormatting($value, true, $errors);
            if ($formatted_value === false) {
                $formatted_value = General::sanitize($this->__applyFormatting($value));
            }
            $return_value_formatted .= '<page>' . htmlentities($formatted_value) . '</page>';
        }
*/
        $data_formatted = array();
        foreach ($data as $value) {
            $formatted_value = $this->__applyFormatting($value, true, $errors);
            if ($formatted_value === false) {
                $formatted_value = General::sanitize($this->__applyFormatting($value));
            }
            $data_formatted[] = htmlentities($formatted_value);
        }
        return array(
            'value' => Spyc::YAMLDump($data),
            'value_formatted' => Spyc::YAMLDump($data_formatted)
        );
    }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

    public function fetchIncludableElements()
    {
        if ($this->get('formatter')) {
            return array(
                $this->get('element_name') . ': formatted',
                $this->get('element_name') . ': unformatted'
            );
        }

        return array(
            $this->get('element_name')
        );
    }

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        $attributes = array();

        if (!is_null($mode)) {
            $attributes['mode'] = $mode;
        }

        $params = Symphony::Engine()->Page()->_param;
        $output_page = $this->_settings['output_page'];
        if ($output_page) {
            //
            // Output single page
            //
            $page_out = preg_replace_callback(
                '/{\$([^}]+)}/',
                function($match) use ($params)
                {
                    if (isset($params[$match[1]])) {
                        return $params[$match[1]];
                    } else {
                        return '';
                    }
                },
                $output_page
            );

            if ($mode == 'formatted') {
                if ($this->get('formatter') and isset($data['value_formatted'])) {
                    $pages = Spyc::YAMLLoadString($data['value_formatted']);
                    $valid_page = (is_numeric($page_out) and $page_out > 0 and $page_out <= count($pages));
                    if (!$valid_page and $this->_settings['default_page_one'] == 'yes') {
                        $page_out = 1;
                        $valid_page = true;
                    }
                    if ($valid_page) {
                        $value = $pages[$page_out - 1];
                        $value = $encode ? General::sanitize($value) : $value;
                        $attributes['page'] = $page_out;
                    } else {
                        $value = null;
                        $attributes['error'] = 'No content found';
                    }
                    $wrapper->appendChild(
                        new XMLElement($this->get('element_name'), $value, $attributes)
                    );
                }
            } elseif ($mode == null or $mode == 'unformatted') {
                $pages = Spyc::YAMLLoadString($data['value']);
                $valid_page = (is_numeric($page_out) and $page_out > 0 and $page_out <= count($pages));
                if (!$valid_page and $this->_settings['default_page_one'] == 'yes') {
                    $page_out = 1;
                    $valid_page = true;
                }
                if ($valid_page) {
                    $value = sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $pages[$page_out - 1]));
                    $attributes['page'] = $page_out;
                } else {
                    $value = null;
                    $attributes['error'] = 'No content found';
                }
                $wrapper->appendChild(
                    new XMLElement(
                        $this->get('element_name'), $value, $attributes
                    )
                );
            }
        } else {
            //
            // Output all pages
            //
            if ($mode == 'formatted') {
                if ($this->get('formatter') and isset($data['value_formatted'])) {
                    $pages = Spyc::YAMLLoadString($data['value_formatted']);
                    $page_out = 1;
                    foreach ($pages as $value) {
                        $attributes['page'] = $page_out;
                        $wrapper->appendChild(
                            new XMLElement(
                                $this->get('element_name'),
                                ($encode ? General::sanitize($value) : $value),
                                $attributes
                            )
                        );
                        $page_out++;
                    }
                }
            } elseif ($mode == null or $mode == 'unformatted') {
                $pages = Spyc::YAMLLoadString($data['value']);
                $page_out = 1;
                foreach ($pages as $value) {
                    $attributes['page'] = $page_out;
                    $wrapper->appendChild(
                        new XMLElement(
                            $this->get('element_name'),
                            sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $value)),
                            $attributes
                        )
                    );
                    $page_out++;
                }
            }
        }
    }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getValue' =>       ImportableField::STRING_VALUE,
            'getPostdata' =>    ImportableField::ARRAY_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object)$this->getImportModes();

        if ($mode === $modes->getValue) {
            return $data;
        } elseif ($mode === $modes->getPostdata) {
            return $this->processRawFieldData($data, $status, $message, true, $entry_id);
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Export:
    -------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes()
    {
        return array(
            'getHandle' =>      ExportableField::HANDLE,
            'getFormatted' =>   ExportableField::FORMATTED,
            'getUnformatted' => ExportableField::UNFORMATTED,
            'getPostdata' =>    ExportableField::POSTDATA
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return string|null
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object)$this->getExportModes();

        // Export handles:
        if ($mode === $modes->getHandle) {
            if (isset($data['handle'])) {
                return $data['handle'];
            } elseif (isset($data['value'])) {
                return Lang::createHandle($data['value']);
            }

            // Export unformatted:
        } elseif ($mode === $modes->getUnformatted || $mode === $modes->getPostdata) {
            return isset($data['value'])
                ? $data['value']
                : null;

            // Export formatted:
        } elseif ($mode === $modes->getFormatted) {
            if (isset($data['value_formatted'])) {
                return $data['value_formatted'];
            } elseif (isset($data['value'])) {
                //return General::sanitize($data['value']);
                return $data['value'];
            }
        }

        return null;
    }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (self::isFilterRegex($data[0])) {
            $this->buildRegexSQL($data[0], array('value'), $joins, $where);
        } else {
            if (is_array($data)) {
                $data = $data[0];
            }

            $this->_key++;
            $data = $this->cleanValue($data);
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                    ON (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";
        }

        return true;
    }

    /*-------------------------------------------------------------------------
        Events:
    -------------------------------------------------------------------------*/

    public function getExampleFormMarkup()
    {
        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', (int)$this->get('size'), 50));

        return $label;
    }
}
