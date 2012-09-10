<?php

require_once 'Store/pages/StoreCheckoutEditPage.php';

/**
 * @package   Store
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCheckoutPayOnAccountPage extends StoreCheckoutEditPage
{
	// {{{ public function getUiXml()

	public function getUiXml()
	{
		return 'Store/pages/checkout-pay-on-account.xml';
	}

	// }}}

	// init phase
	// {{{ public function initCommon()

	public function initCommon()
	{
		$payment_method = null;

		foreach ($this->app->session->order->payment_methods as $method) {
			if ($method->payment_type->isPayOnAccount()) {
				$payment_method = $method;
				break;
			}
		}

		$list = $this->ui->getWidget('pay_on_account_list');

		$options = array(
			'all'  => Store::_('Use available credit to pay for this order'),
			'none' => Store::_('Do not use available credit on this order'),
		);

		$list->addOptionsByArray($options);

		if ($payment_method instanceof StorePaymentMethod) {
			if (count($this->app->session->order->payment_methods) === 0) {
				$list->value = 'all';
			} else {
				$list->value = 'none';
			}
		} else {
			$list->value = 'all';
		}
	}

	// }}}

	// process phase
	// {{{ public function preProcessCommon()

	public function preProcessCommon()
	{
		$list = $this->ui->getWidget('pay_on_account_list');
		$list->process();

		parent::preProcessCommon();
	}

	// }}}
	// {{{ public function processCommon()

	public function processCommon()
	{
		$this->saveDataToSession();
	}

	// }}}
	// {{{ protected function saveDataToSession()

	protected function saveDataToSession()
	{
		$list = $this->ui->getWidget('pay_on_account_list');
		$payment_methods = $this->app->session->order->payment_methods;

		foreach ($payment_methods->getArray() as $payment_method) {
			if ($payment_method->payment_type->isPayOnAccount()) {
				$payment_methods->remove($payment_method);
			}
		}

		if ($list->value ===  'all' || $list->value ===  'custom') {
			$account = $this->app->session->account;

			$class_name = SwatDBClassMap::get('StorePaymentType');
			$type = new $class_name();
			$type->setDatabase($this->app->db);
			$type->loadFromShortname('account');

			$class_name = SwatDBClassMap::get('StoreOrderPaymentMethod');
			$payment_method = new $class_name();
			$payment_method->setDatabase($this->app->db);
			$payment_method->payment_type = $type;
			$payment_method->setMaxAmount($account->available_credit);
			$payment_method->setAdjustable(true);

			$payment_methods->add($payment_method);
		}
	}

	// }}}

	// build phase
	// {{{ public function buildCommon()

	public function buildCommon()
	{
		$available_credit = $this->app->session->account->available_credit;
		$locale = SwatI18NLocale::get($this->app->getLocale());

		$this->ui->getWidget('pay_on_account_field')->title = sprintf(
			Store::_('Available Balance: %s'),
			$locale->formatCurrency($available_credit)
		);
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->ui->getWidget('pay_on_account_note')->visible = false;
	}

	// }}}
}

?>
