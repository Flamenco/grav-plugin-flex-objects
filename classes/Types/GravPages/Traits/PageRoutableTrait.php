<?php

namespace Grav\Plugin\FlexObjects\Types\GravPages\Traits;

use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageCollectionInterface;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Page\Pages;
use Grav\Common\Utils;

/**
 * Implements PageRoutableInterface.
 */
trait PageRoutableTrait
{
    /**
     * Gets the route for the page based on the route headers if available, else from
     * the parents route and the current Page's slug.
     *
     * @param  string $var Set new default route.
     *
     * @return string  The route for the Page.
     */
    public function route($var = null): string
    {
        if (null !== $var) {
            if ($var !== '/' && $var !== Grav::instance()['config']->get('system.home.alias')) {
                throw new \RuntimeException(__METHOD__ . '(\'' . $var . '\'): Not Implemented');
            }
        }

        if ($this->home()) {
            return '/';
        }

        // TODO: implement rest of the routing:
        return $this->rawRoute();
    }

    /**
     * Gets and Sets the parent object for this page
     *
     * @param  PageInterface $var the parent page object
     *
     * @return PageInterface|null the parent page object if it exists.
     */

    public function parent(PageInterface $var = null)
    {
        if (Utils::isAdminPlugin()) {
            return parent::parent();
        }

        if (null !== $var) {
            throw new \RuntimeException('Not Implemented');
        }

        /** @var Pages $pages */
        $pages = Grav::instance()['pages'];

        // FIXME: this does not work, needs to use $pages->get() with cached parent id!
        $key = $this->getKey();
        $parent_route = dirname('/' . $key);

        return $parent_route !== '/' ? $pages->find($parent_route) : $pages->root();
    }

    /**
     * Returns the item in the current position.
     *
     * @return int|null   the index of the current page.
     */
    public function currentPosition(): ?int
    {
        $parent = $this->parent();
        $collection = $parent ? $parent->collection('content', false) : null;
        if ($collection instanceof PageCollectionInterface) {
            return $collection->currentPosition($this->path()) ?? null;
        }

        return 1;
    }

    /**
     * Returns whether or not this page is the currently active page requested via the URL.
     *
     * @return bool True if it is active
     */
    public function active(): bool
    {
        $grav = Grav::instance();
        $uri_path = rtrim(urldecode($grav['uri']->path()), '/') ?: '/';
        $routes = $grav['pages']->routes();

        return isset($routes[$uri_path]) && $routes[$uri_path] === $this->path();
    }

    /**
     * Returns whether or not this URI's URL contains the URL of the active page.
     * Or in other words, is this page's URL in the current URL
     *
     * @return bool True if active child exists
     */
    public function activeChild(): bool
    {
        $grav = Grav::instance();
        $uri = $grav['uri'];
        $pages = $grav['pages'];
        $uri_path = rtrim(urldecode($uri->path()), '/');
        $routes = $pages->routes();

        if (isset($routes[$uri_path])) {
            /** @var PageInterface $child_page */
            $child_page = $pages->dispatch($uri->route(), false, false)->parent();
            if ($child_page) {
                while (!$child_page->root()) {
                    if ($this->path() === $child_page->path()) {
                        return true;
                    }
                    $child_page = $child_page->parent();
                }
            }
        }

        return false;
    }
}
