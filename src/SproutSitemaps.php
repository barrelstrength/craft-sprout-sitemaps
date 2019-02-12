<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutsitemaps;

use barrelstrength\sproutbase\base\BaseSproutTrait;
use barrelstrength\sproutbase\SproutBaseHelper;
use barrelstrength\sproutbasefields\SproutBaseFieldsHelper;
use barrelstrength\sproutsitemaps\models\Settings;
use barrelstrength\sproutsitemaps\services\App;
use barrelstrength\sproutsitemaps\web\twig\variables\SproutSeoVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use yii\base\Event;

/**
 *
 * @property mixed $cpNavItem
 * @property array $cpUrlRules
 * @property array $siteUrlRules
 */
class SproutSitemaps extends Plugin
{
    use BaseSproutTrait;

    /**
     * Enable use of SproutSeo::$app-> in place of Craft::$app->
     *
     * @var \barrelstrength\sproutsitemaps\services\App
     */
    public static $app;

    /**
     * Identify our plugin for BaseSproutTrait
     *
     * @var string
     */
    public static $pluginHandle = 'sprout-sitemaps';

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();
        SproutBaseFieldsHelper::registerModule();

        $this->setComponents([
            'app' => App::class
        ]);

        self::$app = $this->get('app');

        Craft::setAlias('@sproutsitemaps', $this->getBasePath());

        /** @noinspection CascadingDirnameCallsInspection */
        Craft::setAlias('@sproutsitemapslib', dirname(__DIR__, 2).'/sprout-sitemaps/lib');

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        });

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getSiteUrlRules());
        });

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('sproutSitemap', SproutSeoVariable::class);
        });
    }

    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();

        // Allow user to override plugin name in sidebar
        if ($this->getSettings()->pluginNameOverride) {
            $parent['label'] = $this->getSettings()->pluginNameOverride;
        }

        return array_merge($parent, [
            'subnav' => [
                'sitemaps' => [
                    'label' => Craft::t('sprout-sitemaps', 'Sitemaps'),
                    'url' => 'sprout-sitemaps/sitemaps'
                ],
                'settings' => [
                    'label' => Craft::t('sprout-sitemaps', 'Settings'),
                    'url' => 'sprout-sitemaps/settings'
                ],
            ]
        ]);
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @return array
     */
    private function getCpUrlRules()
    {
        return [
            'sprout-sitemaps' => [
                'template' => 'sprout-sitemaps/index'
            ],

            // Sitemaps
            'sprout-sitemaps/sitemaps/edit/<sitemapSectionId:\d+>/<siteHandle:.*>' =>
                'sprout-sitemaps/sitemaps/sitemap-edit-template',

            'sprout-sitemaps/sitemaps/new/<siteHandle:.*>' =>
                'sprout-sitemaps/sitemaps/sitemap-edit-template',

            'sprout-sitemaps/sitemaps/<siteHandle:.*>' =>
                'sprout-sitemaps/sitemaps/sitemap-index-template',

            'sprout-sitemaps/sitemaps' =>
                'sprout-sitemaps/sitemaps/sitemap-index-template',

            // Settings
            'sprout-sitemaps/settings/<settingsSectionHandle:.*>' =>
                'sprout/settings/edit-settings',

            'sprout-sitemaps/settings' =>
                'sprout/settings/edit-settings',
        ];
    }

    /**
     * Match dynamic sitemap URLs
     *
     * Example matches include:
     *
     * Sitemap Index Page
     * - sitemap.xml
     *
     * URL-Enabled Sections
     * - sitemap-t6PLT5o43IFG-1.xml
     * - sitemap-t6PLT5o43IFG-2.xml
     *
     * Special Groupings
     * - sitemap-singles.xml
     * - sitemap-custom-pages.xml
     *
     * @return array
     */
    private function getSiteUrlRules()
    {
        if ($this->getSettings()->enableDynamicSitemaps) {
            return [
                'sitemap-<sitemapKey:.*>-<pageNumber:\d+>.xml' =>
                    'sprout-sitemaps/xml-sitemap/render-xml-sitemap',
                'sitemap-?<sitemapKey:.*>.xml' =>
                    'sprout-sitemaps/xml-sitemap/render-xml-sitemap',
            ];
        }

        return [];
    }
}
