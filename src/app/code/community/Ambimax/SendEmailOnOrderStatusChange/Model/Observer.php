<?php

class Ambimax_SendEmailOnOrderStatusChange_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function sendEmailOnOrderStatusChange(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        Mage::getModel('ambimax_sendemailonorderstatuschange/mail')
            ->setOrder($order)
            ->send();
    }
}