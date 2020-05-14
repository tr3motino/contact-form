<?php

namespace craft\orderform\controllers;

use Craft;
use craft\orderform\models\Submission;
use craft\orderform\Plugin;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\db\Exception;
use yii\web\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7;
use \DateTime;


class SendController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    private function getConfig() {
        #FIXME bitte die config irgendwie auslagern. je nach typ kannst du hier simulieren: local/dev/prod
       #  use local for local dev; dev for the dev-server; prod for the prod-server


    }


    /**
     * Sends a contact form submission.
     *
     * @return Response|null*/

    public function actionIndex()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $submission = new Submission();
        $submission->firstName = $request->getBodyParam('firstName');
        $submission->lastName = $request->getBodyParam('lastName');
        $gender = $request->getBodyParam('gender');
        $submission->gender = array_shift($gender);


        $date = $request->getBodyParam('birthday');
        $date2 = date("Y-m-d H:i:s", strtotime($date));
        $submission->birthday = $date2;


        $submission->email = $request->getBodyParam('email');
        $content = $submission->toArray();
        $causeType = $request->getBodyParam('causeType');
        $content['causeType'] = array_shift($causeType);

        $datetime = new DateTime();
        $content['dataProtection'] = [
            'agbData' => $request->getBodyParam('agbData') ? 'check':'',
            'agbEHealth' => $request->getBodyParam('agbEHealth') ? 'check':'',
            'agbPush' => $request->getBodyParam('agbPush') ? 'check':'',
            'timeStamp' => $datetime->format(DateTime::ATOM),
            'clientIp' => $this->getRealIpAddr()
        ];

        #$config = $this->getConfig();
        $config = include("/www/htdocs/w019bd37/sanecum-group/order-form/src/config/joo.php");
        $result = $this->postData($config['ssoUrl'], $config['ssoCredentials']);
        $token = $result['access_token'];

        $headers = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $result = $this->postData($config['pmApiRegistrationUrl'], ['json' => $content], $headers);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice($settings->successFlashMessage);
        return null;
 }

    private function postData($url, $data, $headers = [])
    {
        $client = new Client($headers);
        try {
            $response = $client->request('POST', $url, $data);
            $contentType = $response->getHeader('Content-Type');
            if (!is_bool(strpos($contentType[0], 'application/json'))) {
                $result = json_decode($response->getBody(), true);
                return $result;
            } else {
                echo "wrong server response",
                $response->getBody();
            }
        } catch (ClientException $e) {
            error_log (Psr7\str($e->getRequest()));
            error_log (Psr7\str($e->getResponse()));
        } catch (TransferException $e) {
            error_log (Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                error_log (Psr7\str($e->getResponse()));
            }
        } catch (RequestException $e) {
            error_log (Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                error_log (Psr7\str($e->getResponse()));
            }
        } catch (BadResponseException $e) {
            error_log (Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                error_log (Psr7\str($e->getResponse()));
            }
        }
    }

    private function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
