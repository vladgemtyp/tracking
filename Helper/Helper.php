<?php
namespace Stagem\OrderMapTracking\Helper;

use Magento\Framework\Exception\FileSystemException;
use Stagem\OrderMapTracking\Logger\Logger;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Helper
{
    public Logger $logger;

    public Json $json;

    public Filesystem $filesystem;

    public function __construct(
        Logger $logger,
        Json $json,
        Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->filesystem = $filesystem;
    }

    public function getTokens(): array
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        try {
            $tokens = $varDir->readFile(Data::TOKENS_TXT);
        } catch (FileSystemException $e) {
            $varDir->writeFile(Data::TOKENS_TXT, '');
        }

        if (empty($tokens)) {
            $quartixAuthData = [
                'CustomerID' => Data::CUSTOMER_ID,
                'UserName'  => Data::USER_NAME,
                'Password' => Data::PASSWORD,
                'Application' => Data::APPLICATION
            ];
            $quartixResponse = $this->sendAuthRequest(Data::BASE_URL_US . Data::URL_AUTH, $quartixAuthData);

            if ($quartixResponse) {
                $authData = $this->json->unserialize($quartixResponse->getBody())['Data'];
                if (
                    isset($authData['AccessToken']) &&
                    isset($authData['RefreshToken'])
                ) {
                    $tokens = [
                        'AccessToken'   => $authData['AccessToken'],
                        'RefreshToken'  => $authData['RefreshToken']
                    ];
                    $this->setTokens($tokens);
                }
            }
        } else {
            $tokens = [
                'AccessToken'   => preg_replace('#^AccessToken:(.*?);.*?$#', '${1}', $tokens),
                'RefreshToken'  => preg_replace('#^.*?;RefreshToken:(.*?)$#', '${1}', $tokens)
            ];
        }

        return $tokens;
    }

    private function setTokens($tokens): void
    {
        if (!empty($tokens['AccessToken']) && !empty($tokens['RefreshToken'])) {
            try {
                $tokensString = 'AccessToken:' . $tokens['AccessToken'] . ';RefreshToken:' . $tokens['RefreshToken'];
                $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
                $varDir->writeFile(Data::TOKENS_TXT, $tokensString);
            } catch (\Exception $e) {
                $this->logger->info('ORDER MAP TRACKING : SET TOKENS : ERROR : ' . $e->getMessage());
            }
        }
    }

    /**
     * @param string $url
     * @param array $data
     * @return false|\Zend_Http_Response
     */
    private function sendAuthRequest(string $url, array $data)
    {
        try {
            $client = $this->getHttpClient($url);
            $formattedData = '';
            foreach ($data as $key => $value) {
                $formattedData .= '&' . $key . '=' . urlencode($value);
            }
            $formattedData = substr($formattedData, 1);
            $client->setRawData($formattedData);
            $this->logger->info($formattedData);
            $response = $client->request(\Zend_Http_Client::POST);
            return $response;
        }catch (\Zend_Http_Client_Exception $exception){
            $this->logger->error('REQUEST ERROR : ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * @param string $url
     * @param string $vehicleIDList
     * @return false|\Zend_Http_Response
     */
    public function sendVehiclesLiveRequest(string $url, string $vehicleIDList = '')
    {
        try {
            $tokens = $this->getTokens();
            $client = $this->getHttpClient($url);
            $client->setMethod();
            $client->setHeaders('AccessToken', $tokens['AccessToken']);
            $client->setHeaders('RefreshToken', $tokens['RefreshToken']);
            if (!empty($vehicleIDList)) {
                $client->setParameterGet($vehicleIDList);
            }
            $response = $client->request(\Zend_Http_Client::GET);

            $this->setTokens([
                'AccessToken'   => $response->getHeader('Accesstoken'),
                'RefreshToken'  => $response->getHeader('Refreshtoken')
            ]);

            return $response;
        } catch (\Zend_Http_Client_Exception $exception){
            $this->logger->error('REQUEST ERROR : ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * @param string $url
     * @return false|\Zend_Http_Response
     */
    public function sendVehiclesRequest(string $url)
    {
        try {
            $tokens = $this->getTokens();
            $client = $this->getHttpClient($url);
            $client->setMethod();
            $client->setHeaders('AccessToken', $tokens['AccessToken']);
            $client->setHeaders('RefreshToken', $tokens['RefreshToken']);
            $response = $client->request(\Zend_Http_Client::GET);

            $this->setTokens([
                'AccessToken'   => $response->getHeader('Accesstoken'),
                'RefreshToken'  => $response->getHeader('Refreshtoken')
            ]);

            return $response;
        } catch (\Zend_Http_Client_Exception $exception) {
            $this->logger->error('REQUEST ERROR : ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * @param string $url
     * @return \Zend_Http_Client
     */
    private function getHttpClient(string $url): \Zend_Http_Client
    {
        return new \Zend_Http_Client($url);
    }

}
