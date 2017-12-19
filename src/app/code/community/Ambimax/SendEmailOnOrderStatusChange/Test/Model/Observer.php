<?php

class Ambimax_SendEmailOnOrderStatusChange_Test_Model_Observer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getAmbimaxSendEmailOnOrderStatusChangeMailMock()
    {
        $mailMock = $this->getMockBuilder('Ambimax_SendEmailOnOrderStatusChange_Model_Email')
            ->setMethods(['send', 'setOrder'])
            ->getMock();

        $this->replaceByMock('model', 'ambimax_sendemailonorderstatuschange/mail', $mailMock);

        return $mailMock;
    }

    /**
     * @throws Exception
     */
    protected function _changeOrderStatus()
    {
        $order = Mage::getModel('sales/order')->load(1);
        $this->assertSame(Mage_Sales_Model_Order::STATE_PROCESSING, $order->getState());
        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true);
        $order->save();
        $this->assertSame(Mage_Sales_Model_Order::STATE_HOLDED, $order->getState());
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getAmbimaxSendEmailOnOrderStatusChangeObserverMock()
    {
        $observerMock = $this->getMockBuilder('Ambimax_SendEmailOnOrderStatusChange_Model_Observer')
            ->setMethods(['sendEmailOnOrderStatusChange'])
            ->getMock();

        $this->replaceByMock('model', 'ambimax_sendemailonorderstatuschange/observer', $observerMock);
        return $observerMock;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getVarienEventObserverWithOrderMock()
    {
        $order = Mage::getModel('sales/order')->load(1);
        $varienEventObserverMock = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(['getOrder'])
            ->getMock();

        $varienEventObserverMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        return $varienEventObserverMock;
    }

    public function testObserverHandlesEmailSending()
    {
        $mailMock = $this->_getAmbimaxSendEmailOnOrderStatusChangeMailMock();
        $varienEventObserverMock = $this->_getVarienEventObserverWithOrderMock();

        $mailMock->expects($this->once())
            ->method('setOrder')
            ->will($this->returnSelf());

        $mailMock->expects($this->once())
            ->method('send')
            ->will($this->returnSelf());

        /** @var Ambimax_SendEmailOnOrderStatusChange_Model_Observer $observer */
        $observer = Mage::getSingleton('ambimax_sendemailonorderstatuschange/observer');
        $observer->sendEmailOnOrderStatusChange($varienEventObserverMock);
    }

    /**
     * @loadFixture ~Ambimax_SendEmailOnOrderStatusChange/emptyrecipient
     * @loadExpectation ~Ambimax_SendEmailOnOrderStatusChange/default
     */
    public function testObserverIsCalledOnOrderStatusChange()
    {
        $observerMock = $this->_getAmbimaxSendEmailOnOrderStatusChangeObserverMock();

        $observerMock
            ->expects($this->once())
            ->method('sendEmailOnOrderStatusChange')
            ->will($this->returnSelf());


        $this->_changeOrderStatus();

        $this->assertEventDispatched('sales_order_save_commit_after');
    }
}