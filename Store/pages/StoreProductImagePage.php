<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatToolLink.php';
require_once 'Store/pages/StorePage.php';
require_once 'Store/dataobjects/StoreProduct.php';

/**
 * @package   Store
 * @copyright 2006-2007 silverorange
 */
class StoreProductImagePage extends StorePage
{
	// {{{ public properties

	public $product_id;
	public $image_id;

	// }}}
	// {{{ protected properties

	public $product;
	public $image;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->back_link = new SwatToolLink();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$product_class = SwatDBClassMap::get('StoreProduct');
		$this->product = new $product_class();
		$this->product->setDatabase($this->app->db);
		$this->product->setRegion($this->app->getRegion());
		$this->product->load($this->product_id);
		$this->buildNavBar();
		$this->layout->data->title = sprintf(Store::_('%s: Image'),
			$this->product->title);

		if ($this->image_id === null)
			$this->image = $this->product->primary_image;
		else
			$this->image = $this->product->images->getByIndex($this->image_id);

		if ($this->image === null)
			throw new SiteNotFoundException();

		$this->layout->startCapture('content');

		echo '<div id="product_images" class="large-image-page">';

		$this->displayImage();

		if (count($this->product->images) > 1)
			$this->displayThumbnails();

		echo '</div>';

		$this->layout->endCapture();
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$link = 'store';

		foreach ($this->path as $path_entry) {
			$link .= '/'.$path_entry->shortname;
			$this->layout->navbar->createEntry($path_entry->title, $link);
		}

		$link .= '/'.$this->product->shortname;
		$this->layout->navbar->createEntry($this->product->title, $link);
		$this->layout->navbar->createEntry(Store::_('Image'));
	}

	// }}}
	// {{{ private function displayImage()

	private function displayImage()
	{
		$this->back_link->title = Store::_('Back to Product Page');
		$this->back_link->link =
			$this->layout->navbar->getEntryByPosition(-1)->link;

		$this->back_link->display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'product_image_large';

		$img_tag = $this->image->getImgTag('large');

		if (strlen($img_tag->alt) == 0)
			$img_tag->alt = sprintf(Store::_('Image of %s'),
				$this->product->title);

		$div_tag->open();
		$img_tag->display();
		$div_tag->close();

		if ($this->image->hasOriginal()) {
			$download_link = new SwatToolLink();
			$download_link->link = $this->image->getURI('original');
			$download_link->title = Store::_('Download High Resolution Image');
			$download_link->display();
		}

		if ($this->image->title !== null) {
			$h3_tag = new SwatHtmlTag('h3');
			$h3_tag->setContent($this->image->title);
			$h3_tag->display();
		}

		if ($this->image->description !== null) {
			$description = SwatString::toXHTML(
				SwatString::minimizeEntities(
				$this->image->description));

			$div_tag->setContent($description, 'text/xml');
			$div_tag->id = null;
			$div_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayThumbnails()

	protected function displayThumbnails()
	{
		$li_tag = new SwatHtmlTag('li');

		echo '<ul id="product_secondary_images">';

		foreach ($this->product->images as $image) {
			$selected = ($this->image->id === $image->id);
			$this->displayThumbnail($image, 'thumb', $selected);
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displayThumbnail()

	protected function displayThumbnail(StoreImage $image, $size = 'thumb',
		$selected = false)
	{
		$li_tag = new SwatHtmlTag('li');
		$li_tag->open();

		if (!$selected) {
			$anchor = new SwatHtmlTag('a');
			$anchor->href = sprintf('%s/image%s',
				$this->getProductPageSource(), $image->id);

			$anchor->title = Store::_('View Larger Image');
			$anchor->open();
		}

		$img_tag = $image->getImgTag($size);

		if ($selected) {
			$img_tag->class.= ' store-image-selected';
		}

		if (strlen($img_tag->alt) == 0)
			$img_tag->alt = sprintf(Store::_('Additional Image of %s'),
				$this->product->title);

		$img_tag->display();

		echo Store::_('<span>View Larger Image</span>');

		if (!$selected) {
			$anchor->close();
		}

		$li_tag->close();
	}

	// }}}
	// {{{ protected function displaySelectedImage()

	protected function displaySelectedImage(StoreImage $image)
	{
		$li_tag = new SwatHtmlTag('li');

		$img_tag = $image->getImgTag('thumb');
		if (strlen($img_tag->alt) == 0)
			$img_tag->alt = sprintf(Store::_('Additional Image of %s'),
				$this->product->title);

		$anchor = new SwatHtmlTag('a');
		$anchor->href = sprintf('%s/image%s',
			$this->getProductPageSource(), $image->id);

		$anchor->title = Store::_('View Larger Image');

		$li_tag->open();
		$anchor->open();
		$img_tag->display();
		echo Store::_('<span>View Larger Image</span>');
		$anchor->close();
		$li_tag->close();
	}

	// }}}
	// {{{ protected function getProductPageSource()

	protected function getProductPageSource()
	{
		$source_exp = explode('/', $this->source);
		array_pop($source_exp);
		$source = implode('/', $source_exp);

		return $source;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->back_link->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
