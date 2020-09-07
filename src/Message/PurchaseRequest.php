<?php

namespace Omnipay\Humm\Message;

use Omnipay\Common\Exception\InvalidRequestException;

class PurchaseRequest extends AbstractRequest
{
    
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        /** @var \Omnipay\Common\CreditCard $card */
        $card = $this->getCard();

        // Normalize consumer names as AfterPay will reject the request with a missing surname
        $givenNames = $card->getFirstName();
        $surname = $card->getLastName();

        if (empty($surname) && false !== $pos = strrpos($givenNames, ' ')) {
            $surname = substr($givenNames, $pos + 1);
            $givenNames = substr($givenNames, 0, $pos);
        }

        // Append fix query param to urls with existing query params as AfterPay appends their
        // data in a way that can break the base url
        $returnUrl = $this->getReturnUrl();
        $cancelUrl = $this->getCancelUrl();

        if (strpos($returnUrl, '?') !== false) {
            $returnUrl .= '&_fix=';
        }

        if (strpos($cancelUrl, '?') !== false) {
            $cancelUrl .= '&_fix=';
        }

        $data = array(
            'x_account_id'                      => $this->getMerchantId(), // Is the merchantID  
            'x_amount'                          => $this->getAmount(),
            'x_currency'                        => $this->getCurrency(),
            'x_customer_first_name'             => $givenNames,
            'x_customer_last_name'              => $surname,
            'x_customer_email'                  => $card->getEmail(),
            'x_customer_phone'                  => $card->getPhone(),
            'x_customer_billing_address1'       => $card->getBillingAddress1(),
            'x_customer_billing_address2'       => $card->getBillingAddress2(),
            'x_customer_billing_city'           => $card->getBillingCity(),
            'x_customer_billing_state'          => $card->getBillingState(),
            'x_customer_billing_postcode'       => $card->getBillingPostcode(),
            'x_customer_billing_country'        => $card->getBillingCountry(),
            'x_customer_phone'                  => $card->getBillingPhone(),
            'x_customer_shipping_first_name'    => $card->getShippingName(), // need to add last name
            'x_customer_shipping_address1'      => $card->getShippingAddress1(),
            'x_customer_shipping_address2'      => $card->getShippingAddress2(),
            'x_customer_shipping_city'          => $card->getShippingCity(),
            'x_customer_shipping_country'       => $card->getShippingState(),
            'x_customer_shipping_postcode'      => $card->getShippingPostcode(),
            'x_customer_shipping_country'       => $card->getShippingCountry(),
            'x_customer_shipping_phone'         => $card->getShippingPhone(),
            'x_reference'                       => $this->getTransactionId(),
            'x_shop_country'                    => 'NZ',
            'x_signature'                       => '300',
            'x_url_complete'                    => $returnUrl,
            'x_url_cancel'                      => $cancelUrl,
            'x_url_callback'                    => $this->getNotifyUrl(),
            'x_test'                            => true, //For now
            'items'                             => $this->getItemData(),
            'merchantReference'                 => $this->getTransactionReference(), //???
        );

        return $data;
    }

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getShippingAmount()
    {
        $items = $this->getItems();
        $itemArray = array();

        if ($items !== null) {
            /** @var \Omnipay\Common\ItemInterface $item */
            foreach ($items as $item) {
                $itemArray[] = array(
                    'name'     => $item->getName(),
                    'quantity' => $item->getQuantity(),
                    'price'    => array(
                        'amount'   => $this->formatPrice($item->getPrice()),
                        'currency' => $this->getCurrency(),
                    ),
                );
            }
        }

        return $itemArray;
    }

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getItemData()
    {
        $items = $this->getItems();
        $itemArray = array();

        if ($items !== null) {
            /** @var \Omnipay\Common\ItemInterface $item */
            foreach ($items as $item) {
                $itemArray[] = array(
                    'name'     => $item->getName(),
                    'quantity' => $item->getQuantity(),
                    'price'    => array(
                        'amount'   => $this->formatPrice($item->getPrice()),
                        'currency' => $this->getCurrency(),
                    ),
                );
            }
        }

        return $itemArray;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    /**
     * @param mixed $data
     * @return \Omnipay\AfterPay\Message\Response
     */
    protected function createResponse($data)
    {
        
        return new PurchaseResponse($this, $data);
    }

    /**
     * @param string|float|int $amount
     * @return null|string
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    protected function formatPrice($amount)
    {
        if ($amount) {
            if (!is_float($amount) &&
                $this->getCurrencyDecimalPlaces() > 0 &&
                false === strpos((string) $amount, '.')
            ) {
                throw new InvalidRequestException(
                    'Please specify amount as a string or float, ' .
                    'with decimal places (e.g. \'10.00\' to represent $10.00).'
                );
            }

            return $this->formatCurrency($amount);
        }

        return null;
    }
}
