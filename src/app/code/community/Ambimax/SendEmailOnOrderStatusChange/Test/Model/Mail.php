<?php

class Ambimax_SendEmailOnOrderStatusChange_Test_Model_Mail extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @var Ambimax_SendEmailOnOrderStatusChange_Model_Mail
     */
    protected $_mail;

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getTransportMock()
    {
        $transport = $this->getMockBuilder('Zend_Mail_Transport_Smtp')
            ->setMethods(['send'])
            ->getMock();

        return $transport;
    }

    public function setUp()
    {
        $this->_order = Mage::getModel('sales/order')->load(1);
        $this->_mail = Mage::getSingleton('ambimax_sendemailonorderstatuschange/mail');
        $this->_mail->setOrder($this->_order);
    }

    /**
     * @loadFixture ~Ambimax_SendEmailOnOrderStatusChange/default
     * @loadExpectation ~Ambimax_SendEmailOnOrderStatusChange/default
     */
    public function testSendMailReport()
    {
        $this->_mail->send($this->_getTransportMock());

        $this->assertSame(
            $this->expected('email')->getRecipients(),
            $this->_mail->getRecipients()
        );

        $this->assertSame(
            $this->expected('email')->getSubject(),
            $this->_mail->getSubject()
        );

        $this->assertSame(
            $this->expected('email')->getBodyHtml(),
            $this->_mail->getBodyHtml(true)
        );

        $this->assertSame(
            $this->expected('email')->getBodyTxt(),
            $this->_mail->getBodyText(true)
        );
    }

    /**
     * @loadFixture ~Ambimax_SendEmailOnOrderStatusChange/emptyrecipient
     * @loadExpectation ~Ambimax_SendEmailOnOrderStatusChange/default
     */
    public function testNoSendingWithEmptyRecipients()
    {
        $transport = $this->_getTransportMock();

        $transport
            ->expects($this->never())
            ->method('send');

        $this->_mail->send($transport);

        $this->assertSame(
            [],
            $this->_mail->getRecipients()
        );
    }

    /**
     * @loadFixture ~Ambimax_SendEmailOnOrderStatusChange/disabled
     * @loadExpectation ~Ambimax_SendEmailOnOrderStatusChange/default
     */
    public function testNoSendingWhenDisabled()
    {
        $transport = $this->_getTransportMock();

        $transport
            ->expects($this->never())
            ->method('send');

        $this->_mail->send($transport);
    }


}