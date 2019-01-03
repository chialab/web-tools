<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2018 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\WebTools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\View\Helper\HtmlHelper as CakeHtmlHelper;
use Cake\View\View;

/**
 * Html helper.
 * It extends {@see \Cake\View\Helper\HtmlHelper} Cake Html Helper
 */
class HtmlHelper extends CakeHtmlHelper
{

    /**
     * Meta data for helper
     */
    protected $metadata = [
        'description' => '',
        'author' => '',
        'viewport' => '',
        'msapplication-TileColor' => '',
        'theme-color' => '',
        'docType' => '',
        'project' => [
            'name' => '',
            'version' => '',
        ],
    ];

    /**
     * Construct the meta data
     * Merge data to $this->metadata from configure 'Meta', if set
     * Merge data to $this->metadata from $config['meta'], if set
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        if ($meta = Configure::read('Meta')) {
            $this->metadata += $meta;
        }
        if (isset($config['meta'])) {
            $this->metadata = $config['meta'] + $this->metadata;
            unset($config['meta']);
        }
        parent::__construct($View, $config);
    }

    /**
     * Title for template pages
     * If `_title` view var is set, return it
     * Otherwise return controller name (and action name if set)
     *
     * @return string
     */
    public function title() : string
    {
        if (isset($this->getView()->viewVars['_title'])) {
            return $this->getView()->viewVars['_title'];
        }
        $title = Inflector::humanize($this->getView()->request->getParam('controller', ''));
        $suffix = Inflector::humanize($this->getView()->request->getParam('action', ''));
        if (empty($title)) {
            $title = $suffix;
        } elseif (!empty($suffix)) {
            $title .= sprintf(' - %s', $suffix);
        }

        return $title;
    }

    /**
     * Html meta
     * Possible meta data:
     *
     *  - description
     *  - author
     *  - viewport
     *  - msapplication-TileColor
     *  - theme-color
     *  - docType
     *  - project.name
     *  - project.version
     *
     * @param array $data Data for meta: 'description', 'author', 'viewport', 'msapplication-TileColor', 'theme-color', 'docType', 'project' (['name' => '...', 'version' => '...'])
     * @return string
     * @see HtmlHelper
     */
    public function metaAll(array $data) : string
    {
        $html = '';

        // description
        $description = $this->getMetaString($data, 'description', '');
        $html .= $this->metaDescription($description);

        // author
        $author = $this->getMetaString($data, 'author', '');
        $html .= $this->metaAuthor($author);

        // viewport, msapplication-TileColor, theme-color
        foreach (['viewport', 'msapplication-TileColor', 'theme-color'] as $attribute) {
            if (!empty($data[$attribute])) {
                $html .= $this->meta([
                    'name' => $attribute,
                    'content' => $data[$attribute],
                ]);
            }
        }

        // css
        $docType = $this->getMetaString($data, 'docType', 'xhtml-strict');
        $html .= $this->metaCss($docType);

        // generator
        $project = $this->getMetaArray($data, 'project', []);
        $html .= $this->metaGenerator($project);

        return $html;
    }

    /**
     * Return html meta description tag for passed description argument
     *
     * @param string|null $description The description
     * @return string
     */
    public function metaDescription($description) : string
    {
        if (empty($description)) {
            return '';
        }
        $html = $this->meta('description', h(strip_tags($description)));
        if ($html === null) {
            $html = '';
        }

        return $html;
    }

    /**
     * Return html meta author tag for passed creator argument
     *
     * @param string|null $creator The content creator
     * @return string
     */
    public function metaAuthor(?string $creator) : string
    {
        if (empty($creator)) {
            $creator = $this->getMetaString([], 'author', '');
            if (empty($creator)) {
                return '';
            }
        }
        $html = $this->meta([
            'name' => 'author',
            'content' => h($creator),
        ]);
        if ($html === null) {
            $html = '';
        }

        return $html;
    }

    /**
     * Return html meta css tag for passed doc type
     *
     * @param string $docType The doc type
     * @return string
     */
    public function metaCss(string $docType) : string
    {
        if ($docType === 'html5') {
            $docType = $this->getMetaString([], 'docType', '');
            if (empty($docType)) {
                return '';
            }
        }
        $html = $this->meta([
            'http-equiv' => 'Content-Style-Type',
            'content' => 'text/css',
        ]);
        if ($html === null) {
            $html = '';
        }

        return $html;
    }

    /**
     * Return html meta for generator by project name and version passed
     *
     * @param array $project The project data ('name', 'version')
     * @return string
     */
    public function metaGenerator(array $project) : string
    {
        if (empty($project) || empty($project['name'])) {
            $project = $this->getMetaArray([], 'project', []);
            if (empty($project) || empty($project['name'])) {
                return '';
            }
        }
        $version = '';
        if (!empty($project['version'])) {
            $version = $project['version'];
        }
        $html = $this->meta([
            'name' => 'generator',
            'content' => trim(sprintf('%s %s', $project['name'], $version)),
        ]);
        if ($html === null) {
            $html = '';
        }

        return $html;
    }

    /**
     * Return html meta for opengraph / facebook
     * OG fields:
     *
     *  - og:title
     *  - og:type
     *  - og:url
     *  - og:image
     *
     * OG optional fields:
     *
     *  - og:audio
     *  - og:description
     *  - og:determiner
     *  - og:locale
     *  - og:locale:alternate
     *  - og:site_name
     *  - og:video
     *
     * OG structured fields:
     *
     *  - og:image:url // identical to og:image
     *  - og:image:secure_url
     *  - og:image:type
     *  - og:image:width
     *  - og:image:height
     *  - og:image:alt
     *  - og:video:url // identical to og:video
     *  - og:video:secure_url
     *  - og:video:type
     *  - og:video:width
     *  - og:video:height
     *  - og:audio
     *  - og:secure_url
     *  - og:type
     *
     * For details @see http://ogp.me
     *
     * @param array $data The data ('title', 'type', 'image', 'url')
     * @return string
     */
    public function metaOpenGraph(array $data) : string
    {
        $html = '';
        foreach ($data as $attribute => $val) {
            $tmp = $this->meta([
                'property' => sprintf('og:%s', $attribute),
                'content' => $val,
            ]);
            if ($tmp !== null) {
                $html .= $tmp;
            }
        }

        return $html;
    }

    /**
     * Return html meta for twitter
     * twitter fields:
     *
     *  - twitter:card
     *  - twitter:site
     *  - twitter:site:id
     *  - twitter:creator
     *  - twitter:creator:id
     *  - twitter:description
     *  - twitter:title
     *  - twitter:image
     *  - twitter:image:alt
     *  - twitter:player
     *  - twitter:player:width
     *  - twitter:player:height
     *  - twitter:player:stream
     *  - twitter:app:name:iphone
     *  - twitter:app:id:iphone
     *  - twitter:app:url:iphone
     *  - twitter:app:name:ipad
     *  - twitter:app:id:ipad
     *  - twitter:app:url:ipad
     *  - twitter:app:name:googleplay
     *  - twitter:app:id:googleplay
     *  - twitter:app:url:googleplay
     *
     * For details @see https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/markup.html
     *
     * @param array $data The data ('card', 'site', 'creator', 'title', 'description', 'image')
     * @return string
     */
    public function metaTwitter(array $data) : string
    {
        $html = '';
        foreach ($data as $attribute => $val) {
            $tmp = $this->meta([
                'property' => sprintf('twitter:%s', $attribute),
                'content' => $val,
            ]);
            if ($tmp !== null) {
                $html .= $tmp;
            }
        }

        return $html;
    }

    /**
     * Return meta by data and field
     *
     * @param array $data The data
     * @param string $field The field
     * @param string $defaultVal The default val
     * @return string
     */
    public function getMetaString(array $data, string $field, ?string $defaultVal) : string
    {
        if (isset($data[$field])) {
            return $data[$field];
        }
        if (isset($this->metadata[$field])) {
            return $this->metadata[$field];
        }

        return (string)$defaultVal;
    }

    /**
     * Return meta by data and field
     *
     * @param array $data The data
     * @param string $field The field
     * @param array|null $defaultVal The default val
     * @return array
     */
    public function getMetaArray(array $data, string $field, ?array $defaultVal) : array
    {
        if (isset($data[$field])) {
            return $data[$field];
        }
        if (isset($this->metadata[$field])) {
            return $this->metadata[$field];
        }

        return (array)$defaultVal;
    }
}
