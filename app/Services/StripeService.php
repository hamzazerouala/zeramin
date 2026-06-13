<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('stripe.secret_key'));
    }

    public function client(): StripeClient
    {
        return $this->client;
    }

    /**
     * Devise (ISO minuscule) selon le pays de facturation.
     */
    public function currencyForCountry(string $country): string
    {
        $map = config('stripe.currency_by_country', []);

        return strtolower($map[strtoupper($country)] ?? config('stripe.default_currency', 'eur'));
    }

    /**
     * Moyens de paiement disponibles : base + spécifiques pays + paypal.
     */
    public function paymentMethodsForCountry(string $country): array
    {
        $methods = config('stripe.base_payment_methods', ['card']);
        $country = strtoupper($country);

        $specific = config('stripe.country_payment_methods', []);
        if (isset($specific[$country])) {
            $methods = array_merge($methods, $specific[$country]);
        }

        $methods[] = 'paypal';

        return array_values(array_unique($methods));
    }

    /**
     * Crée un PaymentIntent. Montant en centimes.
     */
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): \Stripe\PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => strtolower($currency),
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
        ]);
    }

    public function retrievePaymentIntent(string $id): \Stripe\PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($id);
    }

    /**
     * Remboursement total d'un PaymentIntent.
     */
    public function refund(string $paymentIntentId, string $reason = 'requested_by_customer'): \Stripe\Refund
    {
        return $this->client->refunds->create([
            'payment_intent' => $paymentIntentId,
            'reason' => $reason,
        ]);
    }

    /**
     * Construit et vérifie la signature d'un événement webhook.
     *
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $signature,
            config('stripe.webhook_secret'),
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Stripe Connect                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Crée un compte Express Stripe Connect pour un vendeur.
     * Retourne l'account_id.
     */
    public function createConnectAccount(\App\Models\User $user): string
    {
        $account = $this->client->accounts->create([
            'type'         => 'express',
            'email'        => $user->email,
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'metadata' => ['user_id' => $user->id],
        ]);

        return $account->id;
    }

    /**
     * Génère un lien d'onboarding Stripe Connect.
     */
    public function createOnboardingLink(string $accountId, string $returnUrl, string $refreshUrl): string
    {
        $link = $this->client->accountLinks->create([
            'account'     => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url'  => $returnUrl,
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * Retourne le statut du compte Connect (charges_enabled, payouts_enabled).
     */
    public function getAccountStatus(string $accountId): array
    {
        $account = $this->client->accounts->retrieve($accountId);

        return [
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
        ];
    }

    /**
     * Effectue un virement vers un compte Connect Stripe.
     * Montant en centimes.
     */
    public function transferToConnect(string $accountId, int $amountCents, string $currency = 'eur', array $metadata = []): \Stripe\Transfer
    {
        return $this->client->transfers->create([
            'amount'      => $amountCents,
            'currency'    => strtolower($currency),
            'destination' => $accountId,
            'metadata'    => $metadata,
        ]);
    }
}
