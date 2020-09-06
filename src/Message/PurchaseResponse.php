<?php

namespace Omnipay\Humm\Message;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PurchaseResponse extends Response
{
    protected $script = 'https://portal.afterpay.com/afterpay.js';

    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return true;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRedirectResponse()
    {
        $output = <<<EOF
<html>
<head>
    <title>Redirecting...</title>
    <script src="%s" async></script>
</head>
<body>
    <script>
    window.onload = function() {
        AfterPay.initialize({countryCode: "%s"});
        AfterPay.redirect({token: "%s"});
    };
    </script>
</body>
</html>
EOF;

        $output = sprintf($output, $this->getScriptUrl(), $this->getCountryCode(), $this->getToken());

        return HttpResponse::create($output);
    }

    /**
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->script;
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->data['token'] ?? null;

        // return isset($this->data->token) ? $this->data->token : null;
    }

    /**
     * @return string
     */
    public function getTransactionReference()
    {
        return $this->getToken();
    }
}
