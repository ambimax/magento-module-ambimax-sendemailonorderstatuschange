<?php

class Ambimax_SendEmailOnOrderStatusChange_Model_Mail extends Zend_Mail
{
    /**
     * @var array|null
     */
    protected $_recipientsFromConfig;

    /**
     * @var Zend_Mail_Transport_Abstract
     */
    protected $_transport;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @var Varien_Filter_Template
     */
    protected $_templateProcessor;

    /**
     * @throws Exception
     * @throws Zend_Mail_Exception
     */
    protected function _prepareMail()
    {
        $this->setFrom(
            Mage::getStoreConfig('trans_email/ident_general/email'),
            Mage::getStoreConfig('trans_email/ident_general/name')
        );
        $this->addTo($this->getRecipientsFromConfig());
        $this->setSubject($this->getStoreConfig('mail_subject'));
        $this->setBodyHtml($this->getStoreConfig('mail_body'));
        $this->setBodyText($this->getStoreConfig('mail_body'));
    }

    /**
     * @param null $tranport
     * @return Zend_Mail
     * @throws Exception
     * @throws Zend_Mail_Exception
     */
    public function send($tranport = null)
    {
        if ( is_null($tranport) ) {
            $tranport = $this->getTransport();
        }

        $this->_prepareMail();

        if ( !$this->isEnabled() || !$this->hasRecipients() ) {
            $this->log('Module disabled or no valid recipient set');
            return $this;
        }

        if ( !$this->orderStatusIsValidForSending() ) {
            return $this;
        }

        $this->log(
            ['subject' => $this->getSubject(), 'to' => $this->getRecipients(), 'message' => $this->getBodyText()]
        );

        return parent::send($tranport);
    }

    public function isValid()
    {
        if ( !$this->hasRecipients() ) {
            return false;
        }

        $status = $this->getOrder()->getState();
        if ( !$this->orderStatusIsValidForSending($status) ) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getStoreConfig($key)
    {
        return Mage::getStoreConfig(
            'sales_email/sendemailonstatuschange/' . $key,
            $this->getOrder()->getStore()
        );
    }

    /**
     * @return array
     * @throws Zend_Validate_Exception
     */
    public function getRecipientsFromConfig()
    {
        if ( is_null($this->_recipientsFromConfig) ) {

            $this->_recipientsFromConfig = [];
            $recipients = array_map('trim', (array)explode(',', $this->getStoreConfig('recipient')));

            foreach ($recipients as $email) {
                if ( Zend_Validate::is($email, 'EmailAddress') ) {
                    $this->_recipients[$email] = '';
                }
            }
        }

        return $this->_recipientsFromConfig;
    }

    /**
     * Sets the subject of the message
     *
     * @param   string $subject
     * @return  Zend_Mail Provides fluent interface
     * @throws Exception
     * @throws Zend_Mail_Exception
     */
    public function setSubject($subject)
    {
        return parent::setSubject($this->parseVariables($subject));
    }

    /**
     * Sets the HTML body for the message
     *
     * @param  string $html
     * @param  string $charset
     * @param  string $encoding
     * @return Zend_Mail Provides fluent interface
     * @throws Exception
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        $parsedHtml = $this->parseVariables($html);
        return parent::setBodyHtml(nl2br($parsedHtml), $charset, $encoding);
    }

    /**
     * @param string $txt
     * @param null $charset
     * @param string $encoding
     * @return Zend_Mail
     * @throws Exception
     */
    public function setBodyText($txt, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        $parsedTxt = $this->parseVariables($txt);
        return parent::setBodyText($parsedTxt, $charset, $encoding);
    }

    /**
     * @param $string
     * @return string
     * @throws Exception
     */
    protected function parseVariables($string)
    {
        return $this->getTemplateProcessor()->filter($string);
    }

    /**
     * @return Varien_Filter_Template
     */
    public function getTemplateProcessor()
    {
        if ( !$this->_templateProcessor ) {
            $this->setTemplateProcessor(Mage::helper('cms')->getPageTemplateProcessor());
        }
        return $this->_templateProcessor;
    }

    /**
     * @param Varien_Filter_Template $templateProcessor
     */
    public function setTemplateProcessor(Varien_Filter_Template $templateProcessor)
    {
        $this->_templateProcessor = $templateProcessor;
    }


    /**
     * @return Zend_Mail_Transport_Abstract
     */
    public function getTransport()
    {
        if ( !$this->_transport ) {
            if ( Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro') ) {
                $this->_transport = Mage::helper('smtppro')->getTransport();
            }
        }
        return $this->_transport;
    }

    /**
     * @param Zend_Mail_Transport_Abstract $transport
     * @return $this
     */
    public function setTransport(Zend_Mail_Transport_Abstract $transport)
    {
        $this->_transport = $transport;
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return Ambimax_SendEmailOnOrderStatusChange_Model_Mail
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        $this->getTemplateProcessor()->setVariables(['order' => $order]);
        return $this;
    }

    /**
     * @param $message
     * @param int $level
     * @param string $file
     */
    public function log($message, $level = LOG_DEBUG, $file = 'ambimax_sendemailonorderstatuschange.log')
    {
        if ( !$this->getStoreConfig('enable_logging') ) {
            return;
        }

        Mage::log($message, $level, $file);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getStoreConfig('enabled');
    }

    /**
     * @return bool
     */
    public function hasRecipients()
    {
        return (bool)count($this->_recipients);
    }

    /**
     * @return array
     */
    protected function getValidStatus()
    {
        $validStatus = (array)explode(',', Mage::getStoreConfig('sales_email/sendemailonstatuschange/send_on_status'));
        return $validStatus;
    }

    /**
     * @return bool
     */
    protected function orderStatusIsValidForSending()
    {
        return in_array($this->getOrder()->getState(), $this->getValidStatus());
    }

}