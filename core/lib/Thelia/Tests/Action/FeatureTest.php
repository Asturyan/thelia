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

namespace Thelia\Tests\Action;

use Thelia\Action\Feature;
use Thelia\Core\Event\Feature\FeatureDeleteEvent;
use Thelia\Core\Event\Feature\FeatureUpdateEvent;
use Thelia\Model\Feature as FeatureModel;
use Thelia\Core\Event\Feature\FeatureCreateEvent;

/**
 * Class FeatureTest
 * @package Thelia\Tests\Action
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class FeatureTest extends \PHPUnit_Framework_TestCase
{
    protected $dispatcher;

    public function setUp()
    {
        $this->dispatcher = $this->getMock("Symfony\Component\EventDispatcher\EventDispatcherInterface");
    }

    public function testCreate()
    {
        $event = new FeatureCreateEvent();
        $event
            ->setLocale('en_US')
            ->setTitle('test feature')
            ->setDispatcher($this->dispatcher);

        $action = new Feature();
        $action->create($event);

        $createdFeature = $event->getFeature();

        $this->assertInstanceOf('Thelia\Model\Feature', $createdFeature);

        $this->assertFalse($createdFeature->isNew());

        $this->assertEquals("en_US", $createdFeature->getLocale());
        $this->assertEquals("test feature", $createdFeature->getTitle());

        return $createdFeature;
    }

    /**
     * @param FeatureModel $feature
     * @depends testCreate
     */
    public function testUpdate(FeatureModel $feature)
    {
        $event = new FeatureUpdateEvent($feature->getId());

        $event
            ->setLocale('en_US')
            ->setTitle('test update')
            ->setChapo('test chapo')
            ->setDescription('test description')
            ->setPostscriptum('test postscriptum')
            ->setDispatcher($this->dispatcher);

        $action = new Feature();
        $action->update($event);

        $updatedFeature = $event->getFeature();

        $this->assertInstanceOf('Thelia\Model\Feature', $updatedFeature);

        $this->assertEquals('test update', $updatedFeature->getTitle());
        $this->assertEquals('test chapo', $updatedFeature->getChapo());
        $this->assertEquals('test description', $updatedFeature->getDescription());
        $this->assertEquals('test postscriptum', $updatedFeature->getPostscriptum());
        $this->assertEquals('en_US', $updatedFeature->getLocale());

        return $updatedFeature;
    }

    /**
     * @param FeatureModel $feature
     * @depends testUpdate
     */
    public function testDelete(FeatureModel $feature)
    {
        $event = new FeatureDeleteEvent($feature->getId());
        $event->setDispatcher($this->dispatcher);

        $action = new Feature();
        $action->delete($event);

        $deletedFeature = $event->getFeature();

        $this->assertInstanceOf('Thelia\Model\Feature', $deletedFeature);

        $this->assertTrue($deletedFeature->isDeleted());
    }
}
