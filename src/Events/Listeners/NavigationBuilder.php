<?php

declare(strict_types=1);

namespace AbterPhp\Contact\Events\Listeners;

use AbterPhp\Contact\Constant\Resource;
use AbterPhp\Contact\Constant\Route;
use AbterPhp\Framework\Constant\Html5;
use AbterPhp\Framework\Events\NavigationReady;
use AbterPhp\Framework\Html\Component\ButtonFactory;
use AbterPhp\Framework\Html\ITag;
use AbterPhp\Framework\Navigation\Dropdown;
use AbterPhp\Framework\Navigation\Item;
use AbterPhp\Framework\Navigation\Navigation;

class NavigationBuilder
{
    const BASE_WEIGHT = 900;

    /** @var ButtonFactory */
    protected $buttonFactory;

    /**
     * NavigationRegistrar constructor.
     *
     * @param ButtonFactory $buttonFactory
     */
    public function __construct(ButtonFactory $buttonFactory)
    {
        $this->buttonFactory = $buttonFactory;
    }

    /**
     * @param NavigationReady $event
     */
    public function handle(NavigationReady $event)
    {
        $navigation = $event->getNavigation();

        if (!$navigation->hasIntent(Navigation::INTENT_PRIMARY)) {
            return;
        }

        $item   = $this->createContactItem();

        $navigation->addItem($item, static::BASE_WEIGHT);
    }

    /**
     * @return Item
     * @throws \Opulence\Routing\Urls\UrlException
     */
    protected function createFormsItem(): Item
    {
        $text = 'contact:forms';
        $icon = 'assignment';

        $button   = $this->buttonFactory->createFromName($text, Route::CONTACT_FORMS_LIST, [], $icon);
        $resource = $this->getAdminResource(Resource::CONTACT_FORMS);

        $item = new Item($button);
        $item->setResource($resource);

        return $item;
    }

    /**
     * @return Item
     * @throws \Opulence\Routing\Urls\UrlException
     */
    protected function createContactItem(): Item
    {
        $text = 'contact:contact';
        $icon = 'contacts';

        $button   = $this->buttonFactory->createFromName($text, Route::CONTACT_FORMS_LIST, [], $icon);
        $resource = $this->getAdminResource(Resource::CONTACT_FORMS);

        $item = new Item($button);
        $item->setResource($resource);

        $item->setIntent(Item::INTENT_DROPDOWN);
        $item->setAttribute(Html5::ATTR_ID, 'nav-contact');

        if (!empty($item[0]) && $item[0] instanceof ITag) {
            $item[0]->setAttribute(Html5::ATTR_HREF, 'javascript:void(0);');
        }

        $item[1] = $this->createDropdown();

        return $item;
    }

    /**
     * @return Dropdown
     * @throws \Opulence\Routing\Urls\UrlException
     */
    protected function createDropdown(): Dropdown
    {
        $dropdown = new Dropdown();
        $dropdown[] = $this->createFormsItem();

        return $dropdown;
    }

    /**
     * @param string $resource
     *
     * @return string
     */
    protected function getAdminResource(string $resource): string
    {
        return sprintf('admin_resource_%s', $resource);
    }
}
