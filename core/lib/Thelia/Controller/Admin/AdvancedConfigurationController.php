<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Controller\Admin;

use Thelia\Core\Event\Cache\CacheEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Cache\AssetsFlushForm;
use Thelia\Form\Cache\CacheFlushForm;
use Thelia\Form\Cache\ImagesAndDocumentsCacheFlushForm;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;

/**
 * Class CacheController
 * @package Thelia\Controller\Admin
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class AdvancedConfigurationController extends BaseAdminController
{
    public function defaultAction()
    {
        if (null !== $result = $this->checkAuth(AdminResources::ADVANCED_CONFIGURATION, [], AccessManager::VIEW)) {
            return $result;
        }

        return $this->render('advanced-configuration');
    }

    public function flushCacheAction()
    {
        if (null !== $result = $this->checkAuth(AdminResources::ADVANCED_CONFIGURATION, [], AccessManager::UPDATE)) {
            return $result;
        }

        $form = new CacheFlushForm($this->getRequest());
        try {
            $this->validateForm($form);

            $event = new CacheEvent($this->container->getParameter("kernel.cache_dir"));
            $this->dispatch(TheliaEvents::CACHE_CLEAR, $event);
        } catch (\Exception $e) {
            Tlog::getInstance()->addError(sprintf("Flush cache error: %s", $e->getMessage()));
        }

        return $this->generateRedirectFromRoute('admin.configuration.advanced');
    }

    public function flushAssetsAction()
    {
        if (null !== $result = $this->checkAuth(AdminResources::ADVANCED_CONFIGURATION, [], AccessManager::UPDATE)) {
            return $result;
        }

        $form = new AssetsFlushForm($this->getRequest());
        try {
            $this->validateForm($form);

            $event = new CacheEvent(THELIA_WEB_DIR . "assets");
            $this->dispatch(TheliaEvents::CACHE_CLEAR, $event);
        } catch (\Exception $e) {
            Tlog::getInstance()->addError(sprintf("Flush assets error: %s", $e->getMessage()));
        }

        return $this->generateRedirectFromRoute('admin.configuration.advanced');
    }

    public function flushImagesAndDocumentsAction()
    {
        if (null !== $result = $this->checkAuth(AdminResources::ADVANCED_CONFIGURATION, [], AccessManager::UPDATE)) {
            return $result;
        }

        $form = new ImagesAndDocumentsCacheFlushForm($this->getRequest());
        try {
            $this->validateForm($form);

            $event = new CacheEvent(THELIA_WEB_DIR . ConfigQuery::read('image_cache_dir_from_web_root', 'cache'));
            $this->dispatch(TheliaEvents::CACHE_CLEAR, $event);

            $event = new CacheEvent(THELIA_WEB_DIR . ConfigQuery::read('document_cache_dir_from_web_root', 'cache'));
            $this->dispatch(TheliaEvents::CACHE_CLEAR, $event);
        } catch (\Exception $e) {
            Tlog::getInstance()->addError(sprintf("Flush images and document error: %s", $e->getMessage()));
        }

        return $this->generateRedirectFromRoute('admin.configuration.advanced');
    }
}
