<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;

trait MailTrap
{
    /**
     * Id of mailtrap inbox.
     *
     * @var int
     */
    protected $mailtrapInbox;

    /**
     * Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Get e-mails from Mailtrap.
     *
     * @return array
     */
    public function getEmails()
    {
        $response = $this->getClient()
            ->request('GET', "inboxes/$this->mailtrapInbox/messages");

        return json_decode((string) $response->getBody());
    }

    /**
     * Remove e-mails from Mailtrap.
     */
    protected function cleanEmails()
    {
        $this->getClient()
            ->request('PATCH', "inboxes/$this->mailtrapInbox/clean");
    }

    /**
     * Get client.
     *
     * @return Client
     */
    private function getClient()
    {
        if (! $this->client) {
            $this->client = new Client([
                'base_uri' => env('MAILTRAP_API_BASE_URI'),
                'headers' => [
                    'Api-Token' => env('MAILTRAP_API_TOKEN'),
                ],
            ]);
            $this->mailtrapInbox = env('MAILTRAP_API_INBOX');
        }

        return $this->client;
    }
}
