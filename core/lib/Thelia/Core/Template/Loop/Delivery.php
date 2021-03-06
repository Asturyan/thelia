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

namespace Thelia\Core\Template\Loop;

use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Model\AreaDeliveryModuleQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\Module;
use Thelia\Module\BaseModule;
use Thelia\Module\DeliveryModuleInterface;
use Thelia\Module\Exception\DeliveryException;

/**
 * Class Delivery
 * @package Thelia\Core\Template\Loop
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 * @author Etienne Roudeix <eroudeix@gmail.com>
 */
class Delivery extends BaseSpecificModule
{
    public function getArgDefinitions()
    {
        $collection = parent::getArgDefinitions();

        $collection->addArgument(
            Argument::createIntTypeArgument("country")
        );

        return $collection;
    }

    public function parseResults(LoopResult $loopResult)
    {
        $countryId = $this->getCountry();
        if (null !== $countryId) {
            $country = CountryQuery::create()->findPk($countryId);
            if (null === $country) {
                throw new \InvalidArgumentException('Cannot found country id: `' . $countryId . '` in delivery loop');
            }
        } else {
            $country = $this->container->get('thelia.taxEngine')->getDeliveryCountry();
        }

        /** @var Module $deliveryModule */
        foreach ($loopResult->getResultDataCollection() as $deliveryModule) {
            $areaDeliveryModule = AreaDeliveryModuleQuery::create()
                ->findByCountryAndModule($country, $deliveryModule);

            if (null === $areaDeliveryModule) {
                continue;
            }

            $loopResultRow = new LoopResultRow($deliveryModule);

            /** @var DeliveryModuleInterface $moduleInstance */
            $moduleInstance = $deliveryModule->getModuleInstance($this->container);

            if (false === $moduleInstance instanceof DeliveryModuleInterface) {
                throw new \RuntimeException(sprintf("delivery module %s is not a Thelia\Module\DeliveryModuleInterface", $deliveryModule->getCode()));
            }

            try {
                // Check if module is valid, by calling isValidDelivery(),
                // or catching a DeliveryException.

                if ($moduleInstance->isValidDelivery($country)) {
                    $postage = $moduleInstance->getPostage($country);

                    $loopResultRow
                        ->set('ID', $deliveryModule->getId())
                        ->set('TITLE', $deliveryModule->getVirtualColumn('i18n_TITLE'))
                        ->set('CHAPO', $deliveryModule->getVirtualColumn('i18n_CHAPO'))
                        ->set('DESCRIPTION', $deliveryModule->getVirtualColumn('i18n_DESCRIPTION'))
                        ->set('POSTSCRIPTUM', $deliveryModule->getVirtualColumn('i18n_POSTSCRIPTUM'))
                        ->set('POSTAGE', $postage)
                    ;

                    $loopResult->addRow($loopResultRow);
                }
            } catch (DeliveryException $ex) {
                // Module is not available
            }
        }

        return $loopResult;
    }

    protected function getModuleType()
    {
        return BaseModule::DELIVERY_MODULE_TYPE;
    }
}
