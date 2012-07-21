<?php

require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatFlydown.php';
require_once 'Swat/SwatEntry.php';

/**
 * Composite widget that handles provstate entry and cascades from a country
 * flydown
 *
 * If the selected country has no provstate data, a free-text entry is
 * displayed.
 *
 * @package   Store
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreProvStateEntry extends SwatInputControl
{
	// {{{ public properties

	/**
	 * Selected provstate id
	 *
	 * @var integer
	 */
	public $provstate_id = null;

	/**
	 * Manually entered provstate value
	 *
	 * @var string
	 */
	public $provstate_other = null;

	/**
	 * Country and provstate data in the form:
	 *
	 * <code>
	 * <?php
	 * array(
	 *     'country_id' => array(
	 *         'title'      => 'Country Title',
	 *         'provstates' => array(
	 *             array(
	 *                 'id'    => 'provstate_id',
	 *                 'title' => 'Provstate Title',
	 *             ),
	 *             array(
	 *                 'id'    => 'provstate_id',
	 *                 'title' => 'Provstate Title',
	 *             ),
	 *             ...
	 *         ),
	 *     ),
	 *     'country_id' => array(
	 *         'title'      => 'Country Title',
	 *         'provstates' => null,
	 *     ),
	 *     ...
	 * );
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	public $data = array();

	// }}}
	// {{{ protected properties

	/**
	 * Country flydown controlling this provstate entry
	 *
	 * @var SwatFlydown
	 */
	protected $country = null;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/store/javascript/store-provstate-entry.js',
			Store::PACKAGE_ID
		);
	}

	// }}}
	// {{{ public function setCountryFlydown()

	public function setCountryFlydown(SwatFlydown $country_flydown)
	{
		$this->country_flydown = $country_flydown;
	}

	// }}}
	// {{{ public function process()

	public function process()
	{
		$flydown = $this->getCompositeWidget('flydown');
		$flydown->show_blank = true;
		$flydown->serialize_values = false;

		parent::process();

		$this->validate();

		$this->provstate_id = $this->getCompositeWidget('flydown')->value;
		$this->provstate_other = $this->getCompositeWidget('entry')->value;

		// handle special mode flag set by JavaScript that says which type
		// of input was used.
		$raw_data = $this->getForm()->getFormData();
		if (isset($raw_data[$this->id.'_mode'])) {
			if ($raw_data[$this->id.'_mode'] == 'flydown') {
				$this->provstate_other = null;
			}
			if ($raw_data[$this->id.'_mode'] == 'entry') {
				$this->provstate_id = null;
			}
		}

		// If JavaScript was not used, default to flydown unless the blank
		// option was selected.
		if ($this->provstate_id == '') {
			$this->provstate_id = null;
		} else {
			$this->provstate_other = null;
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible) {
			return;
		}

		parent::display();

		$div = new SwatHtmlTag('div');
		$div->id = $this->id;
		$div->class = 'store-provstate-entry';
		$div->open();

		$flydown = $this->getCompositeWidget('flydown');

		// make flat array of provstates for non-JS users
		$provstates = array();
		foreach ($this->data as $country => $data) {
			if (isset($data['provstates']) && is_array($data['provstates'])) {
				foreach ($data['provstates'] as $provstate) {
					$provstates[$provstate['id']] = $provstate['title'];
				}
			}
		}

		// sort alphabetically
		asort($provstates);

		// add options to flydown and display
		$flydown->show_blank = true;
		$flydown->serialize_values = false;
		$flydown->options = array();
		$flydown->addOptionsByArray($provstates);
		$flydown->value = $this->provstate_id;
		$flydown->display();

		// display provstate other entry
		$entry = $this->getCompositeWidget('entry');
		$entry->value = $this->provstate_other;
		$entry->display();

		$div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$javascript = sprintf(
			'var %s_obj = new StoreProvStateEntry(%s, %s);',
			$this->id,
			SwatString::quoteJavaScriptString($this->id),
			json_encode($this->data)
		);

		if ($this->country_flydown instanceof SwatFlydown) {
			$javascript.= sprintf(
				'%s_obj.setCountryFlydown(%s);',
				$this->id,
				SwatString::quoteJavaScriptString($this->country_flydown->id)
			);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$flydown = new SwatFlydown($this->id.'_flydown');
		$this->addCompositeWidget($flydown, 'flydown');

		$entry = new SwatEntry($this->id.'_entry');
		$this->addCompositeWidget($entry, 'entry');
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		// only validate provstate if country flydown is set
		if (!($this->country_flydown instanceof SwatFlydown)) {
			return;
		}

		// only validate provstate if country was selected
		$country_id = $this->country_flydown->value;
		if ($country_id === null) {
			return;
		}

		$flydown = $this->getCompositeWidget('flydown');
		$provstate_id = $flydown->value;

		if (isset($this->data[$country_id]) &&
			is_array($this->data[$country_id]['provstates'])) {
			$found = false;
			$provstates = $this->data[$country_id]['provstates'];
			foreach ($provstates as $provstate) {
				if ($provstate['id'] == $provstate_id) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				$message_content = sprintf(
					Store::_(
						'The selected %%s is not a province or state '.
						'of %s%s%s.'
					),
					'<strong>',
					SwatString::minimizeEntities(
						$this->data[$country_id]['title']
					),
					'</strong>'
				);

				$message = new SwatMessage($message_content, 'error');
				$message->content_type = 'text/xml';
				$this->addMessage($message);
			}
		}
	}

	// }}}
}

?>