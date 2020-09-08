<?php

namespace Omnipay\Humm\Message;

use Omnipay\Common\Exception\InvalidResponseException;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $liveEndpoint = 'https://secure.oxipay.co.nz/Checkout?platform=Default'; //calling oxipays endpoints for now
    protected $testEndpoint = 'https://securesandbox.oxipay.co.nz/Checkout?platform=Default';


    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * @param mixed $value
     * @return $this
     * @throws \Omnipay\Common\Exception\RuntimeException
     */
    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    /**
     * @return mixed
     */
    public function getMerchantSecret()
    {
        return $this->getParameter('merchantSecret');
    }

    /**
     * @param mixed $value
     * @return $this
     * @throws \Omnipay\Common\Exception\RuntimeException
     */
    public function setMerchantSecret($value)
    {
        return $this->setParameter('merchantSecret', $value);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        return $headers;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    public function sendData($data)
    {
        try {
        // don't throw exceptions for 4xx errors
        // $this->httpClient->getEventDispatcher()->addListener(
        //     'request.error',
        //     function ($event) {
        //         if ($event['response']->isClientError()) {
        //             $event->stopPropagation();
        //         }
        //     }
        // );

        // Guzzle HTTP Client createRequest does funny things when a GET request
        // has attached data, so don't send the data if the method is GET.
        if ($this->getHttpMethod() == 'GET') {
            $httpResponse = $this->httpClient->request(
                $this->getHttpMethod(),
                $this->getEndpoint() . '?' . http_build_query($data),
                array(
                    'User-Agent' => $this->getUserAgent(),
                    'Accept' => 'application/x-www-form-urlencoded',
                    'Authorization' => $this->buildAuthorizationHeader(),
                    'Content-type' => 'application/x-www-form-urlencoded',
                )
            );
        } else {
            $httpResponse = $this->httpClient->request(
                $this->getHttpMethod(),
                $this->getEndpoint(),
                array(
                    'User-Agent' => $this->getUserAgent(),
                    'Accept' => 'application/x-www-form-urlencoded',
                    'Authorization' => $this->buildAuthorizationHeader(),
                    'Content-type' => 'application/x-www-form-urlencoded',
                ),
                url(encode($this->toJSON($data)),
            ))
        }

            // $httpRequest->getCurlOptions()->set(CURLOPT_SSLVERSION, 6); // CURL_SSLVERSION_TLSv1_2 for libcurl < 7.35
            // $httpResponse = $httpRequest->send();
            // dd($httpResponse);
            $responseBody = (string) $httpResponse->getBody();
            // dd($responseBody);
            // $response = json_decode($responseBody, true) ?? [];
            // dd($response);
            $this->response = $this->createResponse($response);

            return $this->response;
        } catch (\Exception $e) {
            throw new InvalidResponseException(
                'Error communicating with payment gateway: ' . $e->getMessage(),
                $e->getCode()
            );
        }

    }

    public function toJSON($data, $options = 0)
    {
        if (version_compare(phpversion(), '5.4.0', '>=') === true) {
            return json_encode($data, $options | 64);
        }
        return str_replace('\\/', '/', json_encode($data, $options));
    }

    protected function createResponse($data)
    {
        return($data);
        
        return $this->response = new Response($this, $data);
    }

    protected function buildAuthorizationHeader()
    {
        $merchantId = $this->getMerchantId();
        $merchantSecret = $this->getMerchantSecret();

        return 'Basic ' . base64_encode($merchantId . ':' . $merchantSecret);
    }

    protected function getUserAgent() {
        return 'Omnipay (Omnipay-Afterpay/'.PHP_VERSION.' ; Millys NZ/'.$this->getMerchantId().') '.request()->getSchemeAndHttpHost();
    }
}
